<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { getVendas } from '@/services/vendasService'
import { getVendedores } from '@/services/configuracoesService'
import StatCard from '@/components/ui/StatCard.vue'
import ChartCard from '@/components/ui/ChartCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import Select from '@/components/ui/Select.vue'
import DatePicker from '@/components/ui/DatePicker.vue'
import LineChart from '@/components/charts/LineChart.vue'
import { formatCurrency, formatDateTime } from '@/utils/format'

const loading = ref(true)
const vendas = ref([])
const vendedores = ref([])

const vendedorId = ref('')
const dataInicio = ref('')
const dataFim = ref('')
const periodoAtivo = ref('semana')

function toISODate(date) {
  return date.toISOString().slice(0, 10)
}

// Segunda-feira como início da semana (padrão comercial no Brasil)
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
  } else if (preset === 'semana-passada') {
    const inicio = startOfWeek(hoje)
    inicio.setDate(inicio.getDate() - 7)
    const fim = new Date(inicio)
    fim.setDate(fim.getDate() + 6)
    dataInicio.value = toISODate(inicio)
    dataFim.value = toISODate(fim)
  } else if (preset === 'mes') {
    dataInicio.value = toISODate(new Date(hoje.getFullYear(), hoje.getMonth(), 1))
    dataFim.value = toISODate(hoje)
  }
  periodoAtivo.value = preset
}

function onDataManual() {
  periodoAtivo.value = 'custom'
}

async function carregar() {
  loading.value = true
  vendas.value = await getVendas({
    vendedorId: vendedorId.value || undefined,
    dataInicio: dataInicio.value,
    dataFim: dataFim.value,
  })
  loading.value = false
}

onMounted(async () => {
  vendedores.value = await getVendedores()
  aplicarPreset('semana')
  await carregar()
})

watch([vendedorId, dataInicio, dataFim], carregar)

const presets = [
  { value: 'semana', label: 'Esta semana' },
  { value: 'semana-passada', label: 'Semana passada' },
  { value: 'mes', label: 'Este mês' },
]

const vendedorOptions = computed(() => [
  { value: '', label: 'Todos os vendedores' },
  ...vendedores.value.map((v) => ({ value: v.id, label: v.nome })),
])

const vendedorSelecionado = computed(() => vendedores.value.find((v) => String(v.id) === String(vendedorId.value)) ?? null)

const vendasConcluidas = computed(() => vendas.value.filter((v) => v.status === 'concluida'))
const totalVendido = computed(() => vendasConcluidas.value.reduce((sum, v) => sum + v.total, 0))
const quantidadeVendas = computed(() => vendasConcluidas.value.length)
const ticketMedio = computed(() => (quantidadeVendas.value ? totalVendido.value / quantidadeVendas.value : 0))
const comissaoEstimada = computed(() =>
  vendedorSelecionado.value ? totalVendido.value * (Number(vendedorSelecionado.value.comissao) / 100) : 0,
)

function formatPeriodoISO(iso) {
  if (!iso) return ''
  const [ano, mes, dia] = iso.split('-')
  return `${dia}/${mes}/${ano}`
}

const periodoLabel = computed(() => `${formatPeriodoISO(dataInicio.value)} a ${formatPeriodoISO(dataFim.value)}`)

const vendasPorDia = computed(() => {
  const mapa = new Map()
  const ordenadas = [...vendasConcluidas.value].sort((a, b) => new Date(a.data) - new Date(b.data))
  for (const venda of ordenadas) {
    const dia = new Date(venda.data).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
    mapa.set(dia, (mapa.get(dia) ?? 0) + venda.total)
  }
  return mapa
})

const columns = [
  { key: 'id', label: 'Venda' },
  { key: 'vendedor', label: 'Vendedor' },
  { key: 'cliente', label: 'Cliente' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'pagamento', label: 'Pagamento' },
  { key: 'data', label: 'Data' },
  { key: 'status', label: 'Status' },
]
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Relatório de Vendas</h1>
      <p class="text-sm text-ink-soft mt-1">
        {{ vendedorSelecionado ? `${vendedorSelecionado.nome} · ` : '' }}{{ periodoLabel }}
      </p>
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
          <Select v-model="vendedorId" label="Vendedor" :options="vendedorOptions" class="w-56" />
        </div>
      </div>
    </Card>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <template v-if="loading"><Skeleton v-for="n in 4" :key="n" height="7rem" rounded="16px" /></template>
      <template v-else>
        <StatCard label="Total vendido" :value="totalVendido" type="currency" icon="Wallet" />
        <StatCard label="Vendas concluídas" :value="quantidadeVendas" icon="ShoppingBag" />
        <StatCard label="Ticket médio" :value="ticketMedio" type="currency" icon="Receipt" />
        <StatCard
          v-if="vendedorSelecionado"
          label="Comissão estimada"
          :value="comissaoEstimada"
          type="currency"
          icon="PiggyBank"
        />
      </template>
    </div>

    <ChartCard title="Vendas por dia" :subtitle="periodoLabel">
      <Skeleton v-if="loading" height="280px" rounded="12px" />
      <div v-else-if="!vendasPorDia.size" class="flex h-64 items-center justify-center text-sm text-ink-faint">
        Nenhuma venda concluída no período selecionado.
      </div>
      <LineChart v-else :labels="[...vendasPorDia.keys()]" :data="[...vendasPorDia.values()]" />
    </ChartCard>

    <Card title="Vendas detalhadas" :padded="false" class="p-5 sm:p-6">
      <Table :columns="columns" :rows="vendas" :loading="loading" empty-title="Nenhuma venda no período">
        <template #cell-id="{ value }"><span class="font-medium text-ink">#{{ value }}</span></template>
        <template #cell-total="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-data="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
        <template #cell-status="{ value }">
          <Badge :variant="value === 'concluida' ? 'success' : 'danger'" dot>{{ value === 'concluida' ? 'Concluída' : 'Cancelada' }}</Badge>
        </template>
      </Table>
    </Card>
  </div>
</template>
