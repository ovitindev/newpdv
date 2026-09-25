<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\ConfigFiscal;
use App\Models\Devolucao;
use App\Models\Produto;
use App\Models\Venda;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function overview(Request $request)
    {
        $empresaId = $request->user()->empresa_id;
        $hoje = Carbon::today();
        $ontem = Carbon::yesterday();

        $totalHoje = (float) Venda::concluidas()->whereDate('created_at', $hoje)->sum('total');
        $totalOntem = (float) Venda::concluidas()->whereDate('created_at', $ontem)->sum('total');
        $qtdHoje = Venda::concluidas()->whereDate('created_at', $hoje)->count();
        $qtdOntem = Venda::concluidas()->whereDate('created_at', $ontem)->count();
        $ticketHoje = $qtdHoje > 0 ? $totalHoje / $qtdHoje : 0;
        $ticketOntem = $qtdOntem > 0 ? $totalOntem / $qtdOntem : 0;

        $produtosVendidosHoje = (float) $this->itensVendidosQuery($empresaId, $hoje, $hoje)->sum('venda_itens.quantidade');
        $produtosVendidosOntem = (float) $this->itensVendidosQuery($empresaId, $ontem, $ontem)->sum('venda_itens.quantidade');

        $stats = [
            [
                'key' => 'vendas-hoje',
                'label' => 'Vendas hoje',
                'value' => round($totalHoje, 2),
                'type' => 'currency',
                'change' => $this->pctChange($totalOntem, $totalHoje),
                'icon' => 'Wallet',
            ],
            [
                'key' => 'vendas-realizadas',
                'label' => 'Vendas realizadas',
                'value' => $qtdHoje,
                'type' => 'number',
                'change' => $this->pctChange($qtdOntem, $qtdHoje),
                'icon' => 'ShoppingCart',
            ],
            [
                'key' => 'ticket-medio',
                'label' => 'Ticket médio',
                'value' => round($ticketHoje, 2),
                'type' => 'currency',
                'change' => $this->pctChange($ticketOntem, $ticketHoje),
                'icon' => 'Receipt',
            ],
            [
                'key' => 'produtos-vendidos',
                'label' => 'Produtos vendidos',
                'value' => (int) round($produtosVendidosHoje),
                'type' => 'number',
                'change' => $this->pctChange($produtosVendidosOntem, $produtosVendidosHoje),
                'icon' => 'Package',
            ],
        ];

        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = Carbon::today()->subDays($i);
            $labels[] = $dia->format('d/m');
            $values[] = (float) Venda::concluidas()->whereDate('created_at', $dia)->sum('total');
        }

        $paymentMethodsBreakdown = $this->paymentMethodsBreakdown($empresaId);

        [$topSellingProducts, $stockTopProducts] = $this->topProdutos($empresaId);

        $totalSemana = (float) Venda::concluidas()
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->sum('total');
        $totalMes = (float) Venda::concluidas()
            ->whereYear('created_at', $hoje->year)
            ->whereMonth('created_at', $hoje->month)
            ->sum('total');

        return response()->json([
            'stats' => $stats,
            'salesByPeriod' => ['labels' => $labels, 'values' => $values],
            'paymentMethodsBreakdown' => $paymentMethodsBreakdown,
            'topSellingProducts' => $topSellingProducts,
            'revenueComparison' => [
                'labels' => ['Hoje', 'Semana', 'Mês'],
                'values' => [round($totalHoje, 2), round($totalSemana, 2), round($totalMes, 2)],
            ],
            'recentActivity' => $this->recentActivity(),
            'fiscalStatus' => $this->fiscalStatus(),
            'stockSummary' => $this->stockSummary(),
            'stockTopProducts' => $stockTopProducts,
        ]);
    }

    private function itensVendidosQuery(int $empresaId, Carbon $inicio, Carbon $fim)
    {
        return DB::table('venda_itens')
            ->join('vendas', 'vendas.id', '=', 'venda_itens.venda_id')
            ->where('vendas.empresa_id', $empresaId)
            ->where('vendas.status', 'concluida')
            ->whereDate('vendas.created_at', '>=', $inicio)
            ->whereDate('vendas.created_at', '<=', $fim);
    }

    private function paymentMethodsBreakdown(int $empresaId): array
    {
        $totais = DB::table('venda_pagamentos')
            ->join('vendas', 'vendas.id', '=', 'venda_pagamentos.venda_id')
            ->where('vendas.empresa_id', $empresaId)
            ->where('vendas.status', 'concluida')
            ->where('vendas.created_at', '>=', Carbon::now()->subDays(30))
            ->select('venda_pagamentos.metodo', DB::raw('SUM(venda_pagamentos.valor) as total'))
            ->groupBy('venda_pagamentos.metodo')
            ->pluck('total', 'metodo');

        $totalGeral = (float) $totais->sum();

        $metodos = [
            'pix' => ['label' => 'PIX', 'color' => 'var(--color-brand-500)'],
            'credito' => ['label' => 'Crédito', 'color' => 'var(--color-brand-700)'],
            'debito' => ['label' => 'Débito', 'color' => 'var(--color-brand-300)'],
            'dinheiro' => ['label' => 'Dinheiro', 'color' => '#c7d1cb'],
        ];

        return collect($metodos)->map(function ($info, $chave) use ($totais, $totalGeral) {
            $valor = (float) ($totais[$chave] ?? 0);

            return [
                'label' => $info['label'],
                'value' => $totalGeral > 0 ? (int) round($valor / $totalGeral * 100) : 0,
                'color' => $info['color'],
            ];
        })->values()->all();
    }

    private function topProdutos(int $empresaId): array
    {
        $produtos = DB::table('venda_itens')
            ->join('vendas', 'vendas.id', '=', 'venda_itens.venda_id')
            ->join('produtos', 'produtos.id', '=', 'venda_itens.produto_id')
            ->where('vendas.empresa_id', $empresaId)
            ->where('vendas.status', 'concluida')
            ->where('vendas.created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                'produtos.id',
                'produtos.nome',
                'produtos.estoque',
                DB::raw('SUM(venda_itens.quantidade) as sales'),
                DB::raw('SUM(venda_itens.quantidade * venda_itens.preco) as revenue')
            )
            ->groupBy('produtos.id', 'produtos.nome', 'produtos.estoque')
            ->orderByDesc('sales')
            ->limit(5)
            ->get();

        $maxSales = (float) ($produtos->max('sales') ?: 1);

        $topSellingProducts = $produtos->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->nome,
            'sales' => (int) round((float) $p->sales),
            'revenue' => round((float) $p->revenue, 2),
            'share' => (int) round((float) $p->sales / $maxSales * 100),
        ])->values()->all();

        $stockTopProducts = $produtos->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->nome,
            'sales' => (int) round((float) $p->sales),
            'stock' => (int) round((float) $p->estoque),
        ])->values()->all();

        return [$topSellingProducts, $stockTopProducts];
    }

    private function recentActivity(): array
    {
        $vendas = Venda::with('pagamentos')->latest()->limit(6)->get()->map(fn ($v) => [
            'id' => 'venda-'.$v->id,
            'type' => 'sale',
            'title' => "Venda #{$v->id}",
            'amount' => (float) $v->total,
            'method' => $this->paymentLabel($v),
            'detail' => null,
            'created_at' => $v->created_at,
        ]);

        $devolucoes = Devolucao::latest()->limit(6)->get()->map(fn ($d) => [
            'id' => 'devolucao-'.$d->id,
            'type' => 'return',
            'title' => 'Devolução #'.str_pad((string) $d->id, 4, '0', STR_PAD_LEFT),
            'amount' => (float) $d->valor,
            'method' => null,
            'detail' => $d->motivo,
            'created_at' => $d->created_at,
        ]);

        return $vendas->concat($devolucoes)
            ->sortByDesc('created_at')
            ->take(6)
            ->values()
            ->map(fn ($a) => [
                'id' => $a['id'],
                'type' => $a['type'],
                'title' => $a['title'],
                'amount' => $a['amount'],
                'method' => $a['method'],
                'detail' => $a['detail'],
                'minutesAgo' => max(0, $a['created_at']->diffInMinutes(now())),
            ])->all();
    }

    private function paymentLabel(Venda $venda): ?string
    {
        $labels = ['pix' => 'PIX', 'dinheiro' => 'Dinheiro', 'debito' => 'Débito', 'credito' => 'Cartão de Crédito'];

        if ($venda->pagamentos->count() > 1) {
            return 'Múltiplas formas';
        }

        $primeiro = $venda->pagamentos->first();

        return $primeiro ? ($labels[$primeiro->metodo] ?? $primeiro->metodo) : null;
    }

    private function fiscalStatus(): array
    {
        $configFiscal = ConfigFiscal::first();
        $certificado = Certificado::first();

        $ambiente = $configFiscal?->ambiente === 'producao' ? 'Produção' : 'Homologação';

        $certificadoValido = $certificado?->valido_ate && $certificado->valido_ate->isFuture();
        $certificadoDetail = match (true) {
            ! $certificado => 'Não cadastrado',
            ! $certificado->valido_ate => 'Aguardando validação',
            $certificadoValido => 'Válido até '.$certificado->valido_ate->format('d/m/Y'),
            default => 'Expirado em '.$certificado->valido_ate->format('d/m/Y'),
        };

        return [
            [
                'key' => 'nfce',
                'label' => 'NFC-e',
                'detail' => $configFiscal ? 'Operacional' : 'Não configurado',
                'variant' => $configFiscal ? 'success' : 'warning',
            ],
            [
                'key' => 'nfe',
                'label' => 'NF-e',
                'detail' => $configFiscal ? 'Operacional' : 'Não configurado',
                'variant' => $configFiscal ? 'success' : 'warning',
            ],
            [
                'key' => 'sefaz',
                'label' => 'SEFAZ',
                'detail' => $configFiscal ? "Conectado ({$ambiente})" : 'Não configurado',
                'variant' => $configFiscal ? 'success' : 'warning',
            ],
            [
                'key' => 'certificado',
                'label' => 'Certificado A1',
                'detail' => $certificadoDetail,
                'variant' => $certificadoValido ? 'success' : 'danger',
            ],
        ];
    }

    private function stockSummary(): array
    {
        $produtos = Produto::where('status', 'ativo');

        return [
            'inStock' => (clone $produtos)->where('estoque', '>=', 10)->count(),
            'lowStock' => (clone $produtos)->where('estoque', '>', 0)->where('estoque', '<', 10)->count(),
            'outOfStock' => (clone $produtos)->where('estoque', '<=', 0)->count(),
        ];
    }

    private function pctChange(float $anterior, float $atual): ?float
    {
        if ($anterior <= 0) {
            return $atual > 0 ? 100.0 : 0.0;
        }

        return round((($atual - $anterior) / $anterior) * 100, 1);
    }
}
