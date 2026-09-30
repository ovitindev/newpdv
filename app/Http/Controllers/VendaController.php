<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Models\Venda;
use App\Models\VendaItem;
use App\Models\VendaPagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendaController extends Controller
{
    public function index(Request $request)
    {
        return Venda::with(['cliente', 'vendedor', 'itens', 'pagamentos'])
            ->when($request->vendedor_id, fn ($query, $vendedorId) => $query->where('vendedor_id', $vendedorId))
            ->when($request->data_inicio, fn ($query, $data) => $query->whereDate('created_at', '>=', $data))
            ->when($request->data_fim, fn ($query, $data) => $query->whereDate('created_at', '<=', $data))
            ->latest()
            ->get()
            ->map(fn ($venda) => $this->presentSummary($venda));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'vendedor_id' => ['required', 'exists:vendedores,id'],
            'desconto_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'entrega' => ['nullable', 'boolean'],
            'valor_frete' => ['nullable', 'numeric', 'min:0'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['nullable', 'exists:produtos,id'],
            'itens.*.nome' => ['required', 'string', 'max:255'],
            'itens.*.codigo' => ['nullable', 'string', 'max:100'],
            'itens.*.preco' => ['required', 'numeric', 'min:0'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:0.001'],
            'itens.*.avulso' => ['nullable', 'boolean'],
            'pagamentos' => ['required', 'array', 'min:1'],
            'pagamentos.*.metodo' => ['required', 'in:pix,dinheiro,debito,credito'],
            'pagamentos.*.valor' => ['required', 'numeric', 'min:0.01'],
            'pagamentos.*.parcelas' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $subtotal = collect($data['itens'])->sum(fn ($item) => $item['preco'] * $item['quantidade']);
        $descontoPercent = $data['desconto_percent'] ?? 0;
        $descontoValor = round($subtotal * ($descontoPercent / 100), 2);
        $entrega = (bool) ($data['entrega'] ?? false);
        $valorFrete = $entrega ? round($data['valor_frete'] ?? 0, 2) : 0;
        $total = round($subtotal - $descontoValor + $valorFrete, 2);

        $totalPago = round(collect($data['pagamentos'])->sum('valor'), 2);
        if (abs($totalPago - $total) > 0.02) {
            return response()->json([
                'message' => 'A soma dos pagamentos não confere com o total da venda.',
            ], 422);
        }

        $venda = DB::transaction(function () use ($data, $request, $subtotal, $descontoPercent, $descontoValor, $entrega, $valorFrete, $total) {
            $venda = Venda::create([
                'cliente_id' => $data['cliente_id'] ?? null,
                'vendedor_id' => $data['vendedor_id'],
                'user_id' => $request->user()->id,
                'subtotal' => $subtotal,
                'desconto_percent' => $descontoPercent,
                'desconto_valor' => $descontoValor,
                'entrega' => $entrega,
                'valor_frete' => $valorFrete,
                'total' => $total,
                'status' => 'concluida',
            ]);

            foreach ($data['itens'] as $item) {
                VendaItem::create([
                    'venda_id' => $venda->id,
                    'produto_id' => $item['produto_id'] ?? null,
                    'nome' => $item['nome'],
                    'codigo' => $item['codigo'] ?? null,
                    'preco' => $item['preco'],
                    'quantidade' => $item['quantidade'],
                    'avulso' => $item['avulso'] ?? false,
                ]);

                if (! empty($item['produto_id'])) {
                    $produto = Produto::find($item['produto_id']);
                    if ($produto) {
                        $produto->decrement('estoque', $item['quantidade']);
                        MovimentacaoEstoque::create([
                            'produto_id' => $produto->id,
                            'tipo' => 'saida',
                            'quantidade' => -$item['quantidade'],
                            'motivo' => "Venda #{$venda->id}",
                            'responsavel' => 'PDV',
                        ]);
                    }
                }
            }

            foreach ($data['pagamentos'] as $pagamento) {
                VendaPagamento::create([
                    'venda_id' => $venda->id,
                    'metodo' => $pagamento['metodo'],
                    'valor' => $pagamento['valor'],
                    'parcelas' => $pagamento['parcelas'] ?? 1,
                ]);
            }

            return $venda;
        });

        return response()->json(
            $this->presentDetail($venda->load(['cliente', 'vendedor', 'itens', 'pagamentos', 'operador'])),
            201
        );
    }

    public function show(Venda $venda)
    {
        return $this->presentDetail($venda->load(['cliente', 'vendedor', 'itens', 'pagamentos', 'operador']));
    }

    public function update(Request $request, Venda $venda)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'vendedor_id' => ['required', 'exists:vendedores,id'],
            'desconto_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'entrega' => ['nullable', 'boolean'],
            'valor_frete' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:concluida,cancelada'],
        ]);

        // Itens e pagamentos da venda não são editáveis aqui — só os dados
        // de cabeçalho. O subtotal (soma dos itens) permanece o mesmo.
        $descontoPercent = $data['desconto_percent'] ?? 0;
        $descontoValor = round((float) $venda->subtotal * ($descontoPercent / 100), 2);
        $entrega = (bool) ($data['entrega'] ?? false);
        $valorFrete = $entrega ? round($data['valor_frete'] ?? 0, 2) : 0;
        $total = round((float) $venda->subtotal - $descontoValor + $valorFrete, 2);

        $venda->update([
            'cliente_id' => $data['cliente_id'] ?? null,
            'vendedor_id' => $data['vendedor_id'],
            'desconto_percent' => $descontoPercent,
            'desconto_valor' => $descontoValor,
            'entrega' => $entrega,
            'valor_frete' => $valorFrete,
            'total' => $total,
            'status' => $data['status'],
        ]);

        return $this->presentDetail($venda->load(['cliente', 'vendedor', 'itens', 'pagamentos', 'operador']));
    }

    private function presentSummary(Venda $venda): array
    {
        return [
            'id' => $venda->id,
            'cliente' => $venda->cliente?->nome ?? 'Consumidor Final',
            'vendedor' => $venda->vendedor?->nome,
            'itens' => $venda->itens->sum('quantidade'),
            'total' => (float) $venda->total,
            'pagamento' => $this->paymentSummaryLabel($venda),
            'data' => $venda->created_at,
            'status' => $venda->status,
            'entrega' => (bool) $venda->entrega,
            'valorFrete' => (float) $venda->valor_frete,
        ];
    }

    private function presentDetail(Venda $venda): array
    {
        return [
            ...$this->presentSummary($venda),
            'clienteId' => $venda->cliente_id,
            'vendedorId' => $venda->vendedor_id,
            'subtotal' => (float) $venda->subtotal,
            'descontoPercent' => (float) $venda->desconto_percent,
            'descontoValor' => (float) $venda->desconto_valor,
            'operador' => $venda->operador?->name,
            'itensDetalhe' => $venda->itens->map(fn ($item) => [
                'id' => $item->id,
                'nome' => $item->nome,
                'codigo' => $item->codigo,
                'preco' => (float) $item->preco,
                'quantidade' => (float) $item->quantidade,
                'avulso' => $item->avulso,
            ]),
            'pagamentos' => $venda->pagamentos->map(fn ($pagamento) => [
                'id' => $pagamento->id,
                'metodo' => $pagamento->metodo,
                'valor' => (float) $pagamento->valor,
                'parcelas' => $pagamento->parcelas,
            ]),
        ];
    }

    private function paymentSummaryLabel(Venda $venda): string
    {
        $labels = ['pix' => 'PIX', 'dinheiro' => 'Dinheiro', 'debito' => 'Débito', 'credito' => 'Cartão de Crédito'];

        if ($venda->pagamentos->count() > 1) {
            return 'Múltiplas formas';
        }

        $primeiro = $venda->pagamentos->first();

        return $primeiro ? ($labels[$primeiro->metodo] ?? $primeiro->metodo) : '—';
    }
}
