<script setup>
import { computed, onMounted, ref } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup'
import { getVendas } from '@/services/vendasService'
import { getContasPagar, getContasReceber } from '@/services/financeiroService'
import ChartCard from '@/components/ui/ChartCard.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import { formatCurrency, localISODate } from '@/utils/format'

const loading = ref(true)
const vendas = ref([])
const contasPagar = ref([])
const contasReceber = ref([])

onMounted(async () => {
  const [vendasData, pagarData, receberData] = await Promise.all([getVendas(), getContasPagar(), getContasReceber()])
  vendas.value = vendasData
  contasPagar.value = pagarData
  contasReceber.value = receberData
  loading.value = false
})

const vendasConcluidas = computed(() => vendas.value.filter((v) => v.status === 'concluida'))
const contasPagas = computed(() => contasPagar.value.filter((c) => c.status === 'pago'))

const totalEntradasHistorico = computed(() => vendasConcluidas.value.reduce((sum, v) => sum + v.total, 0))
const totalSaidasHistorico = computed(() => contasPagas.value.reduce((sum, c) => sum + Number(c.valor), 0))
const saldoDisponivel = computed(() => totalEntradasHistorico.value - totalSaidasHistorico.value)

// venda.data é um datetime de verdade (momento da venda) — comparar no fuso
// local é o certo.
function mesmoDia(isoDateTime, dia) {
  const d = new Date(isoDateTime)
  return d.getFullYear() === dia.getFullYear() && d.getMonth() === dia.getMonth() && d.getDate() === dia.getDate()
}

// vencimento é só uma data (cast "date" do Laravel, serializada como meia-
// noite UTC) — comparar via new Date(...).getDate() no fuso local pode cair
// no dia anterior. Comparar a string "YYYY-MM-DD" direto evita isso.
function mesmaDataVencimento(vencimentoISO, dia) {
  return vencimentoISO.slice(0, 10) === localISODate(dia)
}

const ultimosDias = computed(() => {
  const dias = []
  for (let i = 6; i >= 0; i--) {
    const dia = new Date()
    dia.setDate(dia.getDate() - i)
    dias.push(dia)
  }
  return dias
})

const entradasPeriodo = computed(() =>
  ultimosDias.value.map((dia) => vendasConcluidas.value.filter((v) => mesmoDia(v.data, dia)).reduce((sum, v) => sum + v.total, 0)),
)
// Vencimento é usado como aproximação da data de pagamento (não existe "data paga" separada ainda).
const saidasPeriodo = computed(() =>
  ultimosDias.value.map((dia) => contasPagas.value.filter((c) => mesmaDataVencimento(c.vencimento, dia)).reduce((sum, c) => sum + Number(c.valor), 0)),
)

const tiles = computed(() => [
  { label: 'Saldo disponível', value: saldoDisponivel.value, accent: 'brand' },
  { label: 'Entradas (7 dias)', value: entradasPeriodo.value.reduce((a, b) => a + b, 0), accent: 'success' },
  { label: 'Saídas (7 dias)', value: saidasPeriodo.value.reduce((a, b) => a + b, 0), accent: 'danger' },
  { label: 'Contas a receber', value: contasReceber.value.filter((c) => c.status !== 'recebido').reduce((sum, c) => sum + Number(c.valor), 0), accent: 'info' },
  { label: 'Contas a pagar', value: contasPagar.value.filter((c) => c.status !== 'pago').reduce((sum, c) => sum + Number(c.valor), 0), accent: 'warning' },
])

const tileClasses = {
  brand: 'bg-brand-950 text-white',
  success: 'bg-white text-ink',
  danger: 'bg-white text-ink',
  info: 'bg-white text-ink',
  warning: 'bg-white text-ink',
}

const chartData = computed(() => ({
  labels: ultimosDias.value.map((d) => d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })),
  datasets: [
    {
      label: 'Entradas',
      data: entradasPeriodo.value,
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
      data: saidasPeriodo.value,
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
        <div v-for="tile in tiles" :key="tile.label" class="card-surface p-5" :class="tileClasses[tile.accent]">
          <p class="text-sm" :class="tile.accent === 'brand' ? 'text-brand-100' : 'text-ink-soft'">{{ tile.label }}</p>
          <p class="text-xl font-bold mt-1.5">{{ formatCurrency(tile.value) }}</p>
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
