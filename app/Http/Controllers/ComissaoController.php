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
     * As regras (vale e meta) são semanais, de segunda a domingo. Uma semana
     * pertence ao período em que cai a sua segunda-feira — assim cada semana
     * é paga uma única vez, sem repartir o vale entre dois meses.
     *
     *   ?tipo=semana&ref=2026-10-09   → a semana que contém a data
     *   ?tipo=mes&ref=2026-10-09      → semanas com segunda-feira dentro do mês
     *   ?vendedor_id=N                → só vale para quem não é vendedor
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'tipo' => ['nullable', 'in:semana,mes'],
            'ref' => ['nullable', 'date'],
            'vendedor_id' => ['nullable', 'integer'],
        ]);

        $vendedor = $request->user()->vendedor
            ?? Vendedor::find($data['vendedor_id'] ?? 0);
        abort_unless($vendedor, 422, 'Informe o vendedor.');

        $tipo = $data['tipo'] ?? 'semana';
        $ref = isset($data['ref']) ? Carbon::parse($data['ref']) : Carbon::today();

        if ($tipo === 'mes') {
            $segundas = [];
            $inicioMes = $ref->copy()->startOfMonth();
            $primeira = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
            if ($primeira->lt($inicioMes)) {
                $primeira->addWeek();
            }
            for ($s = $primeira; $s->lte($ref->copy()->endOfMonth()); $s = $s->copy()->addWeek()) {
                $segundas[] = $s->copy();
            }
        } else {
            $segundas = [$ref->copy()->startOfWeek(Carbon::MONDAY)];
        }

        $pct = (float) $vendedor->comissao;
        $vale = (float) $vendedor->vale_semanal;
        $meta = (float) $vendedor->meta_semanal;
        $bonusMeta = (float) $vendedor->bonus_meta;
        $hoje = Carbon::today();

        $semanas = [];
        foreach ($segundas as $segunda) {
            // Semana que ainda não começou não entra no pagamento.
            if ($segunda->gt($hoje)) {
                continue;
            }
            $domingo = $segunda->copy()->endOfWeek(Carbon::SUNDAY);

            $vendas = Venda::concluidas()
                ->where('vendedor_id', $vendedor->id)
                ->whereBetween('created_at', [$segunda->copy()->startOfDay(), $domingo])
                ->selectRaw('COALESCE(SUM(total), 0) as soma, COUNT(*) as qtd')
                ->first();

            $totalVendas = round((float) $vendas->soma, 2);
            // "Superiores a" a meta: igual à meta ainda não bate.
            $metaAtingida = $meta > 0 && $totalVendas > $meta;
            $bonus = $metaAtingida ? $bonusMeta : 0.0;
            $comissao = round($totalVendas * $pct / 100, 2);

            $semanas[] = [
                'inicio' => $segunda->toDateString(),
                'fim' => $domingo->toDateString(),
                'emAndamento' => $domingo->gte($hoje),
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
                'comissao' => $soma('comissao'),
                'vale' => $soma('vale'),
                'bonus' => $soma('bonus'),
                'total' => $soma('total'),
            ],
        ];
    }
}
