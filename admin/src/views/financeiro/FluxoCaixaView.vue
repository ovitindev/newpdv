<script setup>
import { computed, onMounted, ref } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup'
import { getFinanceOverview } from '@/services/financeiroService'
import ChartCard from '@/components/ui/ChartCard.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import { formatCurrency } from '@/utils/format'

const loading = ref(true)
const summary = ref(null)
const cashFlow = ref(null)

onMounted(async () => {
  const data = await getFinanceOverview()
  summary.value = data.financeSummary
  cashFlow.value = data.cashFlow
  loading.value = false
})

const tiles = computed(() => [
  { key: 'saldoDisponivel', label: 'Saldo disponível', accent: 'brand' },
  { key: 'entradas', label: 'Entradas', accent: 'success' },
  { key: 'saidas', label: 'Saídas', accent: 'danger' },
  { key: 'contasReceber', label: 'Contas a receber', accent: 'info' },
  { key: 'contasPagar', label: 'Contas a pagar', accent: 'warning' },
])

const tileClasses = {
  brand: 'bg-brand-950 text-white',
  success: 'bg-white text-ink',
  danger: 'bg-white text-ink',
  info: 'bg-white text-ink',
  warning: 'bg-white text-ink',
}

const chartData = computed(() => ({
  labels: cashFlow.value.labels,
  datasets: [
    {
      label: 'Entradas',
      data: cashFlow.value.entradas,
      borderColor: '#16c43f',
      backgroundColor: 'rgba(22, 196, 63, 0.08)',
      tension: 0.38,
      fill: true,
      pointRadius: 0,
      pointHoverRadius: 5,
      borderWidth: 2.5,
    },
    {
      label: 'Saídas',
      data: cashFlow.value.saidas,
      borderColor: '#e5484d',
      backgroundColor: 'rgba(229, 72, 77, 0.06)',
      tension: 0.38,
      fill: true,
      pointRadius: 0,
      pointHoverRadius: 5,
      borderWidth: 2.5,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: true, position: 'top', align: 'end', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle' } },
  },
  scales: {
    x: { grid: { display: false }, border: { display: false } },
    y: { grid: { color: '#eaede9' }, border: { display: false }, ticks: { callback: (v) => `R$ ${v >= 1000 ? `${v / 1000}k` : v}` } },
  },
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Fluxo de Caixa</h1>
      <p class="text-sm text-ink-soft mt-1">Panorama financeiro consolidado da sua loja.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
      <template v-if="loading">
        <Skeleton v-for="n in 5" :key="n" height="5.5rem" rounded="16px" />
      </template>
      <template v-else>
        <div v-for="tile in tiles" :key="tile.key" class="card-surface p-5" :class="tileClasses[tile.accent]">
          <p class="text-sm" :class="tile.accent === 'brand' ? 'text-brand-100' : 'text-ink-soft'">{{ tile.label }}</p>
          <p class="text-xl font-bold mt-1.5">{{ formatCurrency(summary[tile.key]) }}</p>
        </div>
      </template>
    </div>

    <ChartCard title="Entradas vs. Saídas" subtitle="Últimos 7 dias">
      <Skeleton v-if="loading" height="320px" rounded="12px" />
      <div v-else style="height: 320px">
        <Line :data="chartData" :options="chartOptions" />
      </div>
    </ChartCard>
  </div>
</template>
