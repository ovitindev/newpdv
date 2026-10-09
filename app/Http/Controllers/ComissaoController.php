<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use App\Models\Venda;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComissaoController extends Controller
{
    /**
     * Quanto pagar a um vendedor num período.
     *
     * As regras (vale e meta) são semanais, de domingo a sábado. Uma semana
     * pertence ao período em que cai o seu domingo — assim cada semana é paga
     * uma única vez, sem repartir o vale entre dois meses.
     *
     *   ?tipo=semana&ref=2026-10-09          → a semana que contém a data
     *   ?tipo=mes&ref=2026-10-09             → semanas com domingo dentro do mês
     *   ?tipo=periodo&inicio=..&fim=..       → semanas com domingo dentro do intervalo
     *   ?vendedor_id=N                       → só vale para quem não é vendedor
     */
    public function show(Request $request)
    {
        $dados = $this->validar($request);

        $vendedor = $request->user()->vendedor
            ?? Vendedor::find($dados['vendedor_id'] ?? 0);
        abort_unless($vendedor, 422, 'Informe o vendedor.');

        return $this->calcular($vendedor, $this->domingos($dados), $dados['tipo'] ?? 'semana');
    }

    /** Todos os vendedores ativos no período (visão do dono). */
    public function index(Request $request)
    {
        $dados = $this->validar($request);
        $domingos = $this->domingos($dados);

        $linhas = Vendedor::where('status', 'ativo')->orderBy('nome')->get()
            ->map(fn (Vendedor $v) => $this->calcular($v, $domingos, $dados['tipo'] ?? 'semana'));

        return [
            'vendedores' => $linhas,
            'totais' => [
                'vendas' => round($linhas->sum('totais.vendas'), 2),
                'comissao' => round($linhas->sum('totais.comissao'), 2),
                'vale' => round($linhas->sum('totais.vale'), 2),
                'bonus' => round($linhas->sum('totais.bonus'), 2),
                'total' => round($linhas->sum('totais.total'), 2),
            ],
        ];
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'tipo' => ['nullable', 'in:semana,mes,periodo'],
            'ref' => ['nullable', 'date'],
            'inicio' => ['required_if:tipo,periodo', 'nullable', 'date'],
            'fim' => ['required_if:tipo,periodo', 'nullable', 'date', 'after_or_equal:inicio'],
            'vendedor_id' => ['nullable', 'integer'],
        ]);
    }

    /** Domingos (início de cada semana) que pertencem ao período pedido. */
    private function domingos(array $dados): array
    {
        $tipo = $dados['tipo'] ?? 'semana';
        $ref = isset($dados['ref']) ? Carbon::parse($dados['ref']) : Carbon::today();

        if ($tipo === 'semana') {
            return [$ref->copy()->startOfWeek(Carbon::SUNDAY)];
        }

        if ($tipo === 'mes') {
            $inicio = $ref->copy()->startOfMonth();
            $fim = $ref->copy()->endOfMonth();
        } else {
            $inicio = Carbon::parse($dados['inicio'])->startOfDay();
            $fim = Carbon::parse($dados['fim'])->endOfDay();
        }

        // Primeiro domingo dentro do intervalo.
        $domingo = $inicio->copy()->startOfWeek(Carbon::SUNDAY);
        if ($domingo->lt($inicio)) {
            $domingo->addWeek();
        }

        $lista = [];
        for ($d = $domingo; $d->lte($fim); $d = $d->copy()->addWeek()) {
            $lista[] = $d->copy();
        }

        return $lista;
    }

    private function calcular(Vendedor $vendedor, array $domingos, string $tipo): array
    {
        $pct = (float) $vendedor->comissao;
        $vale = (float) $vendedor->vale_semanal;
        $meta = (float) $vendedor->meta_semanal;
        $bonusMeta = (float) $vendedor->bonus_meta;
        $hoje = Carbon::today();

        $semanas = [];
        foreach ($domingos as $inicioSemana) {
            // Semana que ainda não começou não entra no pagamento.
            if ($inicioSemana->gt($hoje)) {
                continue;
            }
            $sabado = $inicioSemana->copy()->endOfWeek(Carbon::SATURDAY);

            $vendas = Venda::concluidas()
                ->where('vendedor_id', $vendedor->id)
                ->whereBetween('created_at', [$inicioSemana->copy()->startOfDay(), $sabado])
                // Frete é repassado ao entregador: não conta como venda do vendedor (nem
                // para comissão, nem para a meta) — mesma regra do relatório de vendas.
                ->selectRaw('COALESCE(SUM(total - COALESCE(valor_frete, 0)), 0) as soma, COUNT(*) as qtd')
                ->first();

            $totalVendas = round((float) $vendas->soma, 2);
            // "Superiores a" a meta: igual à meta ainda não bate.
            $metaAtingida = $meta > 0 && $totalVendas > $meta;
            $bonus = $metaAtingida ? $bonusMeta : 0.0;
            $comissao = round($totalVendas * $pct / 100, 2);

            $semanas[] = [
                'inicio' => $inicioSemana->toDateString(),
                'fim' => $sabado->toDateString(),
                'emAndamento' => $sabado->gte($hoje),
                'vendas' => $totalVendas,
                'quantidade' => (int) $vendas->qtd,
                'comissao' => $comissao,
                'vale' => $vale,
                'bonus' => $bonus,
                'metaAtingida' => $metaAtingida,
                'faltaParaMeta' => $meta > 0 && ! $metaAtingida ? round($meta - $totalVendas, 2) : 0,
                'total' => round($comissao + $vale + $bonus, 2),
            ];
        }

        $soma = fn (string $campo) => round(array_sum(array_column($semanas, $campo)), 2);

        return [
            'vendedor' => ['id' => $vendedor->id, 'nome' => $vendedor->nome],
            'regras' => [
                'percentual' => $pct,
                'valeSemanal' => $vale,
                'metaSemanal' => $meta,
                'bonusMeta' => $bonusMeta,
            ],
            'tipo' => $tipo,
            'semanas' => $semanas,
            'totais' => [
                'vendas' => $soma('vendas'),
                'quantidade' => (int) array_sum(array_column($semanas, 'quantidade')),
                'comissao' => $soma('comissao'),
                'vale' => $soma('vale'),
                'bonus' => $soma('bonus'),
                'total' => $soma('total'),
            ],
        ];
    }
}
