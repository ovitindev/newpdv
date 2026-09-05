<script setup>
import { computed, onMounted, ref } from 'vue'
import { getFiscalOverview } from '@/services/fiscalService'
import ChartCard from '@/components/ui/ChartCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import DoughnutChart from '@/components/charts/DoughnutChart.vue'
import { formatCurrency, formatDateTime, formatNumber } from '@/utils/format'

const loading = ref(true)
const summary = ref(null)
const notas = ref([])

onMounted(async () => {
  const data = await getFiscalOverview()
  summary.value = data.fiscalSummary
  notas.value = data.notasFiscais
  loading.value = false
})

const statusDistribution = computed(() => [
  { label: 'Autorizadas', value: notas.value.filter((n) => n.status === 'autorizada').length, color: 'var(--color-brand-500)' },
  { label: 'Canceladas', value: notas.value.filter((n) => n.status === 'cancelada').length, color: '#c7d1cb' },
  { label: 'Rejeitadas', value: notas.value.filter((n) => n.status === 'rejeitada').length, color: 'var(--color-danger)' },
])

const columns = [
  { key: 'numero', label: 'Número' },
  { key: 'tipo', label: 'Tipo' },
  { key: 'cliente', label: 'Cliente' },
  { key: 'valor', label: 'Valor', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'data', label: 'Data' },
]

const statusVariant = { autorizada: 'success', cancelada: 'neutral', rejeitada: 'danger' }
const statusLabel = { autorizada: 'Autorizada', cancelada: 'Cancelada', rejeitada: 'Rejeitada' }
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Relatório Fiscal</h1>
      <p class="text-sm text-ink-soft mt-1">Panorama das emissões fiscais da sua loja.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
      <div class="grid grid-cols-2 gap-4 xl:col-span-2">
        <template v-if="loading"><Skeleton v-for="n in 4" :key="n" height="6rem" rounded="16px" /></template>
        <template v-else>
          <div class="card-surface p-5"><p class="text-sm text-ink-soft">NFC-e emitidas</p><p class="text-xl font-bold text-ink mt-1">{{ formatNumber(summary.nfceEmitidas) }}</p></div>
          <div class="card-surface p-5"><p class="text-sm text-ink-soft">NF-e emitidas</p><p class="text-xl font-bold text-ink mt-1">{{ formatNumber(summary.nfeEmitidas) }}</p></div>
          <div class="card-surface p-5"><p class="text-sm text-ink-soft">Canceladas</p><p class="text-xl font-bold text-ink mt-1">{{ formatNumber(summary.canceladas) }}</p></div>
          <div class="card-surface p-5"><p class="text-sm text-ink-soft">Rejeitadas</p><p class="text-xl font-bold text-ink mt-1">{{ formatNumber(summary.rejeitadas) }}</p></div>
        </template>
      </div>
      <ChartCard title="Status das emissões">
        <Skeleton v-if="loading" height="180px" rounded="12px" />
        <DoughnutChart v-else :items="statusDistribution" height="180" />
      </ChartCard>
    </div>

    <Card title="Últimas emissões" :padded="false" class="p-5 sm:p-6">
      <Table :columns="columns" :rows="notas" :loading="loading" empty-title="Nenhuma nota emitida">
        <template #cell-numero="{ value }"><span class="font-mono text-xs text-ink">{{ value }}</span></template>
        <template #cell-tipo="{ value }"><Badge variant="brand" size="sm">{{ value }}</Badge></template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-status="{ value }"><Badge :variant="statusVariant[value]" dot>{{ statusLabel[value] }}</Badge></template>
        <template #cell-data="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
      </Table>
    </Card>
  </div>
</template>
