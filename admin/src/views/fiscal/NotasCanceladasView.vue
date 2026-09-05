<script setup>
import { computed, onMounted, ref } from 'vue'
import { getNotasFiscais } from '@/services/fiscalService'
import { usePagination } from '@/utils/usePagination'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import { formatCurrency, formatDateTime } from '@/utils/format'

const loading = ref(true)
const notas = ref([])
const search = ref('')

onMounted(async () => {
  const all = await getNotasFiscais()
  notas.value = all.filter((n) => n.status === 'cancelada')
  loading.value = false
})

const filtered = computed(() =>
  notas.value.filter((n) => !search.value || n.numero.includes(search.value) || n.cliente.toLowerCase().includes(search.value.toLowerCase())),
)

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'numero', label: 'Número' },
  { key: 'tipo', label: 'Tipo' },
  { key: 'cliente', label: 'Cliente' },
  { key: 'valor', label: 'Valor', align: 'right' },
  { key: 'data', label: 'Cancelada em' },
]
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Notas Canceladas</h1>
      <p class="text-sm text-ink-soft mt-1">Histórico de notas fiscais canceladas.</p>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="mb-5 max-w-sm"><SearchInput v-model="search" placeholder="Buscar por número ou cliente..." /></div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="FileX2" empty-title="Nenhuma nota cancelada" empty-description="Ótimo sinal — nenhuma nota foi cancelada até agora.">
        <template #cell-numero="{ value }"><span class="font-mono text-xs text-ink">{{ value }}</span></template>
        <template #cell-tipo="{ value }"><Badge variant="neutral" size="sm">{{ value }}</Badge></template>
        <template #cell-cliente="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink line-through decoration-danger/50">{{ formatCurrency(value) }}</span></template>
        <template #cell-data="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>
  </div>
</template>
