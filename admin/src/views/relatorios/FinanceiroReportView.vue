<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { getVendas } from '@/services/vendasService'
import { getContasPagar } from '@/services/financeiroService'
import StatCard from '@/components/ui/StatCard.vue'
import ChartCard from '@/components/ui/ChartCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import DatePicker from '@/components/ui/DatePicker.vue'
import BarChart from '@/components/charts/BarChart.vue'
import { formatCurrency, formatDateOnly } from '@/utils/format'

const loading = ref(true)
const vendas = ref([])
const contasPagar = ref([])

const dataInicio = ref('')
const dataFim = ref('')
const periodoAtivo = ref('mes')

function toISODate(date) {
  return date.toISOString().slice(0, 10)
}

function startOfWeek(date) {
  const d = new Date(date)
  const dia = d.getDay()
  const diff = dia === 0 ? -6 : 1 - dia
  d.setDate(d.getDate() + diff)
  return d
}

function aplicarPreset(preset) {
  const hoje = new Date()
  if (preset === 'semana') {
    dataInicio.value = toISODate(startOfWeek(hoje))
    dataFim.value = toISODate(hoje)
  } else if (preset === 'mes') {
    dataInicio.value = toISODate(new Date(hoje.getFullYear(), hoje.getMonth(), 1))
    dataFim.value = toISODate(hoje)
  } else if (preset === 'mes-passado') {
    const inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1)
    const fim = new Date(hoje.getFullYear(), hoje.getMonth(), 0)
    dataInicio.value = toISODate(inicio)
    dataFim.value = toISODate(fim)
  }
  periodoAtivo.value = preset
}

function onDataManual() {
  periodoAtivo.value = 'custom'
}

// Evita que uma resposta antiga (filtro anterior) sobrescreva uma mais recente.
let requisicaoAtual = 0

async function carregar() {
  const minhaRequisicao = ++requisicaoAtual
  loading.value = true
  const [vendasData, contasData] = await Promise.all([
    getVendas({ dataInicio: dataInicio.value, dataFim: dataFim.value }),
    getContasPagar(),
  ])
  if (minhaRequisicao !== requisicaoAtual) return
  vendas.value = vendasData
  contasPagar.value = contasData
  loading.value = false
}

onMounted(async () => {
  aplicarPreset('mes')
  await carregar()
})

watch([dataInicio, dataFim], carregar)

const presets = [
  { value: 'semana', label: 'Esta semana' },
  { value: 'mes', label: 'Este mês' },
  { value: 'mes-passado', label: 'Mês passado' },
]

function formatPeriodoISO(iso) {
  if (!iso) return ''
  const [ano, mes, dia] = iso.split('-')
  return `${dia}/${mes}/${ano}`
}

const periodoLabel = computed(() => `${formatPeriodoISO(dataInicio.value)} a ${formatPeriodoISO(dataFim.value)}`)

function noPeriodo(vencimento) {
  if (!dataInicio.value || !dataFim.value) return true
  // A API devolve vencimento como datetime ISO completo (ex: "2026-09-05T00:00:00.000000Z"),
  // então compara só a parte da data pra não excluir o próprio dia de início/fim do período.
  const data = vencimento.slice(0, 10)
  return data >= dataInicio.value && data <= dataFim.value
}

const receita = computed(() => vendas.value.filter((v) => v.status === 'concluida').reduce((sum, v) => sum + v.total, 0))

const despesasPagas = computed(() => contasPagar.value.filter((c) => c.status === 'pago' && noPeriodo(c.vencimento)))
const totalDespesasPagas = computed(() => despesasPagas.value.reduce((sum, c) => sum + Number(c.valor), 0))

const despesasEmAberto = computed(() => contasPagar.value.filter((c) => c.status !== 'pago' && noPeriodo(c.vencimento)))
const totalDespesasEmAberto = computed(() => despesasEmAberto.value.reduce((sum, c) => sum + Number(c.valor), 0))

const resultado = computed(() => receita.value - totalDespesasPagas.value)

const despesasPorCategoria = computed(() => {
  const mapa = new Map()
  for (const conta of despesasPagas.value) {
    const categoria = conta.categoria || 'Sem categoria'
    mapa.set(categoria, (mapa.get(categoria) ?? 0) + Number(conta.valor))
  }
  return [...mapa.entries()].sort((a, b) => b[1] - a[1])
})

const balancoChart = computed(() => ({
  labels: ['Receita', 'Despesas pagas', 'Resultado'],
  values: [receita.value, totalDespesasPagas.value, resultado.value],
}))

const columns = [
  { key: 'descricao', label: 'Descrição' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'valor', label: 'Valor', align: 'right' },
  { key: 'vencimento', label: 'Data' },
]
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Fechamento Financeiro</h1>
      <p class="text-sm text-ink-soft mt-1">{{ periodoLabel }}</p>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="preset in presets"
            :key="preset.value"
            type="button"
            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors"
            :class="periodoAtivo === preset.value ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-border text-ink-soft hover:border-brand-300'"
            @click="aplicarPreset(preset.value)"
          >
            {{ preset.label }}
          </button>
        </div>
        <div class="flex flex-wrap items-end gap-3">
          <DatePicker v-model="dataInicio" label="De" class="w-40" @update:model-value="onDataManual" />
          <DatePicker v-model="dataFim" label="Até" class="w-40" @update:model-value="onDataManual" />
        </div>
      </div>
    </Card>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <template v-if="loading"><Skeleton v-for="n in 4" :key="n" height="7rem" rounded="16px" /></template>
      <template v-else>
        <StatCard label="Receita (vendas)" :value="receita" type="currency" icon="Wallet" />
        <StatCard label="Despesas pagas" :value="totalDespesasPagas" type="currency" icon="ArrowUpCircle" />
        <StatCard
          :label="resultado >= 0 ? 'Lucro do período' : 'Prejuízo do período'"
          :value="Math.abs(resultado)"
          type="currency"
          :icon="resultado >= 0 ? 'TrendingUp' : 'TrendingDown'"
        />
        <StatCard label="Despesas em aberto" :value="totalDespesasEmAberto" type="currency" icon="Clock" />
      </template>
    </div>

    <ChartCard title="Receita x Despesas" :subtitle="periodoLabel">
      <Skeleton v-if="loading" height="240px" rounded="12px" />
      <BarChart v-else :labels="balancoChart.labels" :data="balancoChart.values" :colors="['#16c43f', '#e5484d', resultado >= 0 ? '#0a3d1e' : '#e5484d']" height="240" />
    </ChartCard>

    <Card title="Para onde foi o dinheiro" subtitle="Despesas pagas no período, por categoria" :padded="false" class="p-5 sm:p-6">
      <div v-if="loading" class="space-y-3"><Skeleton v-for="n in 4" :key="n" height="1.5rem" rounded="8px" /></div>
      <div v-else-if="!despesasPorCategoria.length" class="py-8 text-center text-sm text-ink-faint">Nenhuma despesa paga no período.</div>
      <ul v-else class="space-y-3">
        <li v-for="[categoria, valor] in despesasPorCategoria" :key="categoria" class="flex items-center justify-between">
          <span class="text-sm text-ink-soft">{{ categoria }}</span>
          <div class="flex items-center gap-3">
            <div class="h-1.5 w-32 overflow-hidden rounded-full bg-surface">
              <div class="h-full rounded-full bg-brand-500" :style="{ width: `${(valor / (despesasPorCategoria[0]?.[1] || 1)) * 100}%` }" />
            </div>
            <span class="w-24 text-right text-sm font-semibold text-ink">{{ formatCurrency(valor) }}</span>
          </div>
        </li>
      </ul>
    </Card>

    <Card title="Despesas pagas no período" :padded="false" class="p-5 sm:p-6">
      <Table :columns="columns" :rows="despesasPagas" :loading="loading" empty-title="Nenhuma despesa paga no período">
        <template #cell-descricao="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-categoria="{ value }"><span class="text-ink-soft">{{ value || '—' }}</span></template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-vencimento="{ value }"><span class="text-ink-soft">{{ formatDateOnly(value) }}</span></template>
      </Table>
    </Card>
  </div>
</template>
