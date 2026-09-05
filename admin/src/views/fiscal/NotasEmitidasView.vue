<script setup>
import { computed, onMounted, ref } from 'vue'
import { Eye, Download, Ban } from '@lucide/vue'
import { getNotasFiscais } from '@/services/fiscalService'
import { fiscalStatusOptions, fiscalTypeOptions } from '@/data/mock/fiscal'
import { useToastStore } from '@/stores/toast'
import { usePagination } from '@/utils/usePagination'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Select from '@/components/ui/Select.vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { formatCurrency, formatDateTime } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const notas = ref([])
const search = ref('')
const typeFilter = ref('')
const statusFilter = ref('')

onMounted(async () => {
  notas.value = await getNotasFiscais()
  loading.value = false
})

const filtered = computed(() =>
  notas.value.filter((n) => {
    const matchesSearch =
      !search.value || n.numero.includes(search.value) || n.cliente.toLowerCase().includes(search.value.toLowerCase())
    const matchesType = !typeFilter.value || n.tipo === typeFilter.value
    const matchesStatus = !statusFilter.value || n.status === statusFilter.value
    return matchesSearch && matchesType && matchesStatus
  }),
)

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'numero', label: 'Número' },
  { key: 'serie', label: 'Série' },
  { key: 'tipo', label: 'Tipo' },
  { key: 'cliente', label: 'Cliente' },
  { key: 'valor', label: 'Valor', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'data', label: 'Data' },
  { key: 'acoes', label: '', align: 'right' },
]

const statusVariant = { autorizada: 'success', cancelada: 'neutral', rejeitada: 'danger' }
const statusLabel = { autorizada: 'Autorizada', cancelada: 'Cancelada', rejeitada: 'Rejeitada' }

const selectedNota = ref(null)

function cancelar(nota) {
  nota.status = 'cancelada'
  toast.info(`Nota ${nota.numero} cancelada`)
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Notas Emitidas</h1>
      <p class="text-sm text-ink-soft mt-1">Todas as notas fiscais emitidas pela sua loja.</p>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <SearchInput v-model="search" placeholder="Buscar por número ou cliente..." />
        <Select v-model="typeFilter" :options="fiscalTypeOptions" />
        <Select v-model="statusFilter" :options="fiscalStatusOptions" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="FileText" empty-title="Nenhuma nota encontrada">
        <template #cell-numero="{ value }"><span class="font-mono text-xs text-ink">{{ value }}</span></template>
        <template #cell-tipo="{ value }"><Badge variant="brand" size="sm">{{ value }}</Badge></template>
        <template #cell-cliente="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-status="{ value }"><Badge :variant="statusVariant[value]" dot>{{ statusLabel[value] }}</Badge></template>
        <template #cell-data="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
        <template #cell-acoes="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-ink" @click="selectedNota = row">
              <Eye :size="15" />
            </button>
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-ink">
              <Download :size="15" />
            </button>
            <button
              v-if="row.status === 'autorizada'"
              class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-danger-soft hover:text-danger"
              @click="cancelar(row)"
            >
              <Ban :size="15" />
            </button>
          </div>
        </template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>

    <Modal :model-value="Boolean(selectedNota)" :title="`Nota ${selectedNota?.numero}`" size="sm" @update:model-value="selectedNota = null">
      <dl v-if="selectedNota" class="space-y-3 text-sm">
        <div class="flex justify-between"><dt class="text-ink-soft">Tipo</dt><dd class="font-medium text-ink">{{ selectedNota.tipo }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Série</dt><dd class="font-medium text-ink">{{ selectedNota.serie }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Cliente</dt><dd class="font-medium text-ink">{{ selectedNota.cliente }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Data</dt><dd class="font-medium text-ink">{{ formatDateTime(selectedNota.data) }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Status</dt><dd><Badge :variant="statusVariant[selectedNota.status]" dot>{{ statusLabel[selectedNota.status] }}</Badge></dd></div>
        <div class="flex justify-between pt-3 border-t border-border text-base">
          <dt class="font-semibold text-ink">Valor</dt><dd class="font-bold text-brand-700">{{ formatCurrency(selectedNota.valor) }}</dd>
        </div>
      </dl>
      <template #footer>
        <Button variant="outline" @click="selectedNota = null">Fechar</Button>
        <Button><template #icon-left><Download :size="15" /></template>Baixar XML</Button>
      </template>
    </Modal>
  </div>
</template>
