<script setup>
import { computed, onMounted, ref } from 'vue'
import { CheckCircle2, Ban, AlertCircle } from '@lucide/vue'
import { getNotasFiscais } from '@/services/fiscalService'
import { usePagination } from '@/utils/usePagination'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import { formatCurrency, formatDateTime, formatNumber } from '@/utils/format'

const loading = ref(true)
const notas = ref([])
const search = ref('')

onMounted(async () => {
  const all = await getNotasFiscais()
  notas.value = all.filter((n) => n.tipo === 'NF-e')
  loading.value = false
})

const summary = computed(() => ({
  autorizadas: notas.value.filter((n) => n.status === 'autorizada').length,
  canceladas: notas.value.filter((n) => n.status === 'cancelada').length,
  rejeitadas: notas.value.filter((n) => n.status === 'rejeitada').length,
}))

const filtered = computed(() =>
  notas.value.filter((n) => !search.value || n.numero.includes(search.value) || n.cliente.toLowerCase().includes(search.value.toLowerCase())),
)

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'numero', label: 'Número' },
  { key: 'serie', label: 'Série' },
  { key: 'cliente', label: 'Cliente / Destinatário' },
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
      <h1 class="text-xl font-semibold text-ink">NF-e</h1>
      <p class="text-sm text-ink-soft mt-1">Notas fiscais eletrônicas emitidas para pessoa jurídica.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <template v-if="loading"><Skeleton v-for="n in 3" :key="n" height="5rem" rounded="16px" /></template>
      <template v-else>
        <div class="card-surface p-5 flex items-center gap-4">
          <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><CheckCircle2 :size="20" /></span>
          <div><p class="text-sm text-ink-soft">Autorizadas</p><p class="text-xl font-bold text-ink">{{ formatNumber(summary.autorizadas) }}</p></div>
        </div>
        <div class="card-surface p-5 flex items-center gap-4">
          <span class="flex size-11 items-center justify-center rounded-xl bg-surface text-ink-soft"><Ban :size="20" /></span>
          <div><p class="text-sm text-ink-soft">Canceladas</p><p class="text-xl font-bold text-ink">{{ formatNumber(summary.canceladas) }}</p></div>
        </div>
        <div class="card-surface p-5 flex items-center gap-4">
          <span class="flex size-11 items-center justify-center rounded-xl bg-danger-soft text-danger"><AlertCircle :size="20" /></span>
          <div><p class="text-sm text-ink-soft">Rejeitadas</p><p class="text-xl font-bold text-ink">{{ formatNumber(summary.rejeitadas) }}</p></div>
        </div>
      </template>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="mb-5 max-w-sm"><SearchInput v-model="search" placeholder="Buscar por número ou cliente..." /></div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="FileCheck2" empty-title="Nenhuma NF-e emitida ainda">
        <template #cell-numero="{ value }"><span class="font-mono text-xs text-ink">{{ value }}</span></template>
        <template #cell-cliente="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-status="{ value }"><Badge :variant="statusVariant[value]" dot>{{ statusLabel[value] }}</Badge></template>
        <template #cell-data="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>
  </div>
</template>
