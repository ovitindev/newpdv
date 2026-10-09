<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PedidoCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoCompraController extends Controller
{
    public function index()
    {
        return PedidoCompra::with('cliente:id,nome,telefone')->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'novo_cliente' => ['nullable', 'array'],
            'novo_cliente.nome' => ['required_with:novo_cliente', 'string', 'max:255'],
            'novo_cliente.telefone' => ['nullable', 'string', 'max:30'],
            'descricao' => ['required', 'string'],
            'quantidade' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:pendente,comprado,disponivel,concluido,cancelado'],
            'observacoes' => ['nullable', 'string'],
        ]);

        // Cliente novo e pedido nascem juntos: se o pedido falhar, não sobra
        // cliente órfão no cadastro.
        $pedido = DB::transaction(function () use ($data) {
            $data = $this->resolverCliente($data);

            return PedidoCompra::create($data);
        });

        // refresh() traz os defaults do banco (status 'pendente', quantidade 1)
        // que não vieram no request — senão a resposta sai sem esses campos.
        return response()->json($pedido->refresh()->load('cliente:id,nome,telefone'), 201);
    }

    public function show(PedidoCompra $pedidoCompra)
    {
        return $pedidoCompra->load('cliente:id,nome,telefone');
    }

    public function update(Request $request, PedidoCompra $pedidoCompra)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'novo_cliente' => ['nullable', 'array'],
            'novo_cliente.nome' => ['required_with:novo_cliente', 'string', 'max:255'],
            'novo_cliente.telefone' => ['nullable', 'string', 'max:30'],
            'descricao' => ['sometimes', 'required', 'string'],
            'quantidade' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:pendente,comprado,disponivel,concluido,cancelado'],
            'observacoes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($pedidoCompra, $data) {
            $pedidoCompra->update($this->resolverCliente($data));
        });

        return $pedidoCompra->load('cliente:id,nome,telefone');
    }

    public function destroy(PedidoCompra $pedidoCompra)
    {
        $pedidoCompra->delete();

        return response()->noContent();
    }

    /**
     * Se veio `novo_cliente`, cadastra e usa o id dele. `cliente_nome` e
     * `cliente_contato` ficam só nos pedidos antigos, anotados em texto livre.
     */
    private function resolverCliente(array $data): array
    {
        if (! empty($data['novo_cliente'])) {
            $data['cliente_id'] = Cliente::create([
                'nome' => $data['novo_cliente']['nome'],
                'telefone' => $data['novo_cliente']['telefone'] ?? null,
                'status' => 'ativo',
            ])->id;
        }
        unset($data['novo_cliente']);

        return $data;
    }
}
