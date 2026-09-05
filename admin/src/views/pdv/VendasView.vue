<script setup>
import { computed, onMounted, ref } from 'vue'
import { Eye, Printer, Plus } from '@lucide/vue'
import { useRouter } from 'vue-router'
import { getVendas } from '@/services/vendasService'
import { usePagination } from '@/utils/usePagination'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import { formatCurrency, formatDateTime } from '@/utils/format'

const router = useRouter()
const loading = ref(true)
const vendas = ref([])
const search = ref('')
const statusFilter = ref('')

const statusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'concluida', label: 'Concluída' },
  { value: 'cancelada', label: 'Cancelada' },
]

onMounted(async () => {
  vendas.value = await getVendas()
  loading.value = false
})

const filtered = computed(() =>
  vendas.value.filter((venda) => {
    const matchesSearch =
      !search.value ||
      venda.cliente.toLowerCase().includes(search.value.toLowerCase()) ||
      String(venda.id).includes(search.value)
    const matchesStatus = !statusFilter.value || venda.status === statusFilter.value
    return matchesSearch && matchesStatus
  }),
)

const { page, pageSize, paginated } = usePagination(filtered, 6)

const columns = [
  { key: 'id', label: 'Venda' },
  { key: 'cliente', label: 'Cliente' },
  { key: 'itens', label: 'Itens', align: 'right' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'pagamento', label: 'Pagamento' },
  { key: 'data', label: 'Data' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const selectedVenda = ref(null)
function openDetail(venda) {
  selectedVenda.value = venda
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Vendas</h1>
        <p class="text-sm text-ink-soft mt-1">Histórico de vendas realizadas no PDV.</p>
      </div>
      <Button @click="router.push('/pdv/nova-venda')">
        <template #icon-left><Plus :size="16" /></template>
        Nova Venda
      </Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="flex-1"><SearchInput v-model="search" placeholder="Buscar por cliente ou nº da venda..." /></div>
        <Select v-model="statusFilter" :options="statusOptions" class="sm:w-52" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-title="Nenhuma venda encontrada">
        <template #cell-id="{ value }">
          <span class="font-medium text-ink">#{{ value }}</span>
        </template>
        <template #cell-total="{ value }">
          <span class="font-semibold text-ink">{{ formatCurrency(value) }}</span>
        </template>
        <template #cell-data="{ value }">
          <span class="text-ink-soft">{{ formatDateTime(value) }}</span>
        </template>
        <template #cell-status="{ value }">
          <Badge :variant="value === 'concluida' ? 'success' : 'danger'" dot>
            {{ value === 'concluida' ? 'Concluída' : 'Cancelada' }}
          </Badge>
        </template>
        <template #cell-acoes="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-ink" @click="openDetail(row)">
              <Eye :size="15" />
            </button>
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-ink">
              <Printer :size="15" />
            </button>
          </div>
        </template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>

    <Modal :model-value="Boolean(selectedVenda)" :title="`Venda #${selectedVenda?.id}`" size="sm" @update:model-value="selectedVenda = null">
      <dl v-if="selectedVenda" class="space-y-3 text-sm">
        <div class="flex justify-between"><dt class="text-ink-soft">Cliente</dt><dd class="font-medium text-ink">{{ selectedVenda.cliente }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Itens</dt><dd class="font-medium text-ink">{{ selectedVenda.itens }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Forma de pagamento</dt><dd class="font-medium text-ink">{{ selectedVenda.pagamento }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Vendedor</dt><dd class="font-medium text-ink">{{ selectedVenda.vendedor }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Data</dt><dd class="font-medium text-ink">{{ formatDateTime(selectedVenda.data) }}</dd></div>
        <div class="flex justify-between pt-3 border-t border-border text-base">
          <dt class="font-semibold text-ink">Total</dt><dd class="font-bold text-brand-700">{{ formatCurrency(selectedVenda.total) }}</dd>
        </div>
      </dl>
      <template #footer>
        <Button variant="outline" @click="selectedVenda = null">Fechar</Button>
        <Button><template #icon-left><Printer :size="15" /></template>Imprimir</Button>
      </template>
    </Modal>
  </div>
</template>
