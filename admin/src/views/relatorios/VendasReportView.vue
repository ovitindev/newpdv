<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { getVendas, getComissoes } from '@/services/vendasService'
import { getVendedores } from '@/services/configuracoesService'
import { useVendasTotais } from '@/composables/useVendasTotais'
import StatCard from '@/components/ui/StatCard.vue'
import ChartCard from '@/components/ui/ChartCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import Select from '@/components/ui/Select.vue'
import DatePicker from '@/components/ui/DatePicker.vue'
import LineChart from '@/components/charts/LineChart.vue'
import { formatCurrency, formatDateTime, localISODate } from '@/utils/format'

const loading = ref(true)
const vendas = ref([])
const vendedores = ref([])

const remuneracao = ref(null)
const vendedorId = ref('')
const dataInicio = ref('')
const dataFim = ref('')
const periodoAtivo = ref('semana')

const toISODate = localISODate

// Semana de domingo a sábado (mesma regra do vale e da meta dos vendedores)
function startOfWeek(date) {
  const d = new Date(date)
  const dia = d.getDay()
  d.setDate(d.getDate() - dia)
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

// Evita que uma resposta antiga (filtro anterior) sobrescreva uma mais recente
// quando vendedor/datas mudam rápido e as requisições voltam fora de ordem.
let requisicaoAtual = 0

async function carregar() {
  const minhaRequisicao = ++requisicaoAtual
  loading.value = true
  const [resultado, pagamento] = await Promise.all([
    getVendas({ vendedorId: vendedorId.value || undefined, dataInicio: dataInicio.value, dataFim: dataFim.value }),
    getComissoes({ inicio: dataInicio.value, fim: dataFim.value }).catch(() => null),
  ])
  if (minhaRequisicao !== requisicaoAtual) return
  vendas.value = resultado
  remuneracao.value = pagamento
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

const { vendasConcluidas, totalVendido, totalFrete, quantidadeVendas, ticketMedio } = useVendasTotais(vendas)
// Remuneração calculada pelo servidor (comissão + vale semanal + bônus de meta).
// Frete é repassado ao entregador: não entra na comissão nem na meta.
const linhasRemuneracao = computed(() =>
  (remuneracao.value?.vendedores ?? [])
    .filter((l) => !vendedorId.value || String(l.vendedor.id) === String(vendedorId.value))
    .filter((l) => l.totais.vendas > 0 || l.totais.total > 0)
    .map((l) => ({ id: l.vendedor.id, vendedor: l.vendedor.nome, regras: l.regras, ...l.totais, semanas: l.semanas })),
)
const totalRemuneracao = computed(() => linhasRemuneracao.value.reduce((s, l) => s + l.total, 0))
const colunasRemuneracao = [
  { key: 'vendedor', label: 'Vendedor' },
  { key: 'vendas', label: 'Vendas (sem frete)', align: 'right' },
  { key: 'comissao', label: 'Comissão', align: 'right' },
  { key: 'vale', label: 'Vale semanal', align: 'right' },
  { key: 'bonus', label: 'Bônus de meta', align: 'right' },
  { key: 'total', label: 'A pagar', align: 'right' },
]

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
        <StatCard label="Total de frete (entrega)" :value="totalFrete" type="currency" icon="Truck" />
        <StatCard label="A pagar aos vendedores" :value="totalRemuneracao" type="currency" icon="PiggyBank" />
      </template>
    </div>

    <Card title="Remuneração dos vendedores" :padded="false" class="p-5 sm:p-6">
      <p class="mb-4 text-sm text-ink-soft">
        Comissão sobre as vendas (sem o frete) + vale fixo por semana + bônus quando a semana passa da meta.
        A semana vai de domingo a sábado e conta no período em que cai o domingo dela.
      </p>
      <Table :columns="colunasRemuneracao" :rows="linhasRemuneracao" :loading="loading" empty-title="Nenhuma remuneração no período">
        <template #cell-vendedor="{ row }">
          <span class="font-medium text-ink">{{ row.vendedor }}</span>
          <span class="block text-xs text-ink-faint">
            {{ row.regras.percentual }}%<template v-if="row.regras.valeSemanal"> + {{ formatCurrency(row.regras.valeSemanal) }}/sem</template><template v-if="row.regras.metaSemanal"> · meta {{ formatCurrency(row.regras.metaSemanal) }} = +{{ formatCurrency(row.regras.bonusMeta) }}</template>
          </span>
        </template>
        <template #cell-vendas="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-comissao="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-vale="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-bonus="{ value }"><span :class="value > 0 ? 'font-medium text-brand-700' : 'text-ink-faint'">{{ formatCurrency(value) }}</span></template>
        <template #cell-total="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
      </Table>
      <div v-if="linhasRemuneracao.length" class="mt-4 flex justify-end border-t border-border pt-3 text-sm">
        <span class="text-ink-soft">Total a pagar no período:&nbsp;</span><span class="font-semibold text-ink">{{ formatCurrency(totalRemuneracao) }}</span>
      </div>
    </Card>

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
