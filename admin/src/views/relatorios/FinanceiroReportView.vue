<script setup>
import { computed, onMounted, ref } from 'vue'
import { getFinanceOverview, getContasPagar, getContasReceber } from '@/services/financeiroService'
import ChartCard from '@/components/ui/ChartCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import BarChart from '@/components/charts/BarChart.vue'
import { formatCurrency, formatDate } from '@/utils/format'

const loading = ref(true)
const summary = ref(null)
const pendencias = ref([])

onMounted(async () => {
  const [overview, pagar, receber] = await Promise.all([getFinanceOverview(), getContasPagar(), getContasReceber()])
  summary.value = overview.financeSummary
  pendencias.value = [
    ...pagar.filter((c) => c.status !== 'pago').map((c) => ({ ...c, tipo: 'Pagar' })),
    ...receber.filter((c) => c.status !== 'recebido').map((c) => ({ ...c, tipo: 'Receber' })),
  ].sort((a, b) => new Date(a.vencimento) - new Date(b.vencimento))
  loading.value = false
})

const balancoChart = computed(() => ({
  labels: ['Entradas', 'Saídas', 'Saldo'],
  values: [summary.value.entradas, summary.value.saidas, summary.value.saldoDisponivel],
}))

const columns = [
  { key: 'descricao', label: 'Descrição' },
  { key: 'tipo', label: 'Tipo' },
  { key: 'valor', label: 'Valor', align: 'right' },
  { key: 'vencimento', label: 'Vencimento' },
  { key: 'status', label: 'Status' },
]

const statusVariant = { pendente: 'warning', atrasado: 'danger' }
const statusLabel = { pendente: 'Pendente', atrasado: 'Atrasado' }
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Relatório Financeiro</h1>
      <p class="text-sm text-ink-soft mt-1">Balanço consolidado e pendências financeiras.</p>
    </div>

    <ChartCard title="Balanço do período">
      <Skeleton v-if="loading" height="240px" rounded="12px" />
      <BarChart v-else :labels="balancoChart.labels" :data="balancoChart.values" :colors="['#16c43f', '#e5484d', '#0a3d1e']" height="240" />
    </ChartCard>

    <Card title="Pendências financeiras" subtitle="Contas a pagar e a receber em aberto" :padded="false" class="p-5 sm:p-6">
      <Table :columns="columns" :rows="pendencias" :loading="loading" empty-title="Nenhuma pendência financeira" empty-description="Todas as contas estão em dia.">
        <template #cell-descricao="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-tipo="{ value }"><Badge :variant="value === 'Pagar' ? 'danger' : 'info'" size="sm">{{ value }}</Badge></template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-vencimento="{ value }"><span class="text-ink-soft">{{ formatDate(value) }}</span></template>
        <template #cell-status="{ value }"><Badge :variant="statusVariant[value]" dot>{{ statusLabel[value] }}</Badge></template>
      </Table>
    </Card>
  </div>
</template>
