<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, Undo2 } from '@lucide/vue'
import { getDevolucoes } from '@/services/vendasService'
import { useToastStore } from '@/stores/toast'
import { usePagination } from '@/utils/usePagination'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import Input from '@/components/ui/Input.vue'
import MoneyInput from '@/components/ui/MoneyInput.vue'
import { formatCurrency, formatDateTime } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const devolucoes = ref([])
const search = ref('')

onMounted(async () => {
  devolucoes.value = await getDevolucoes()
  loading.value = false
})

const filtered = computed(() =>
  devolucoes.value.filter(
    (dev) => !search.value || dev.produto.toLowerCase().includes(search.value.toLowerCase()) || String(dev.venda).includes(search.value),
  ),
)

const { page, pageSize, paginated } = usePagination(filtered, 6)

const columns = [
  { key: 'id', label: 'Devolução' },
  { key: 'venda', label: 'Venda' },
  { key: 'produto', label: 'Produto' },
  { key: 'motivo', label: 'Motivo' },
  { key: 'valor', label: 'Valor', align: 'right' },
  { key: 'data', label: 'Data' },
  { key: 'status', label: 'Status' },
]

const modalOpen = ref(false)
const form = ref({ venda: '', produto: '', motivo: '', valor: '' })

function submit() {
  if (!form.value.venda || !form.value.produto) return
  devolucoes.value.unshift({
    id: devolucoes.value.length + 33,
    venda: form.value.venda,
    produto: form.value.produto,
    motivo: form.value.motivo || 'Não informado',
    valor: Number(form.value.valor) || 0,
    data: new Date().toISOString(),
    status: 'pendente',
  })
  form.value = { venda: '', produto: '', motivo: '', valor: '' }
  modalOpen.value = false
  toast.success('Devolução registrada', 'A devolução foi enviada para aprovação.')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Devoluções</h1>
        <p class="text-sm text-ink-soft mt-1">Itens devolvidos ou trocados pelos clientes.</p>
      </div>
      <Button @click="modalOpen = true">
        <template #icon-left><Plus :size="16" /></template>
        Nova Devolução
      </Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="mb-5"><SearchInput v-model="search" placeholder="Buscar por produto ou nº da venda..." /></div>

      <Table
        :columns="columns"
        :rows="paginated"
        :loading="loading"
        empty-icon="Undo2"
        empty-title="Nenhuma devolução registrada"
        empty-description="Quando um cliente devolver um item, ele aparece aqui."
      >
        <template #cell-id="{ value }"><span class="font-medium text-ink">#{{ value }}</span></template>
        <template #cell-venda="{ value }"><span class="text-ink-soft">#{{ value }}</span></template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-data="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
        <template #cell-status="{ value }">
          <Badge :variant="value === 'aprovada' ? 'success' : 'warning'" dot>
            {{ value === 'aprovada' ? 'Aprovada' : 'Pendente' }}
          </Badge>
        </template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>

    <Modal v-model="modalOpen" title="Registrar devolução" size="sm">
      <div class="space-y-4">
        <Input v-model="form.venda" label="Número da venda" placeholder="Ex: 1048" />
        <Input v-model="form.produto" label="Produto" placeholder="Nome do produto devolvido" />
        <Input v-model="form.motivo" label="Motivo" placeholder="Ex: Produto com defeito" />
        <MoneyInput v-model="form.valor" label="Valor a devolver" />
      </div>
      <template #footer>
        <Button variant="ghost" @click="modalOpen = false">Cancelar</Button>
        <Button @click="submit"><template #icon-left><Undo2 :size="15" /></template>Registrar</Button>
      </template>
    </Modal>
  </div>
</template>
