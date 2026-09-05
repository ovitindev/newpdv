<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, ArrowUpRight, ArrowDownRight, RefreshCcw } from '@lucide/vue'
import { getMovimentacoes } from '@/services/produtosService'
import { movementTypeOptions } from '@/data/mock/estoque'
import { useToastStore } from '@/stores/toast'
import { usePagination } from '@/utils/usePagination'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import Input from '@/components/ui/Input.vue'
import { formatDateTime } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const movimentacoes = ref([])
const search = ref('')
const typeFilter = ref('')

onMounted(async () => {
  movimentacoes.value = await getMovimentacoes()
  loading.value = false
})

const filtered = computed(() =>
  movimentacoes.value.filter((m) => {
    const matchesSearch = !search.value || m.produto.toLowerCase().includes(search.value.toLowerCase())
    const matchesType = !typeFilter.value || m.tipo === typeFilter.value
    return matchesSearch && matchesType
  }),
)

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'produto', label: 'Produto' },
  { key: 'tipo', label: 'Tipo' },
  { key: 'quantidade', label: 'Quantidade', align: 'right' },
  { key: 'motivo', label: 'Motivo' },
  { key: 'responsavel', label: 'Responsável' },
  { key: 'data', label: 'Data' },
]

const typeMeta = {
  entrada: { label: 'Entrada', icon: ArrowUpRight, variant: 'success' },
  saida: { label: 'Saída', icon: ArrowDownRight, variant: 'danger' },
  ajuste: { label: 'Ajuste', icon: RefreshCcw, variant: 'info' },
}

const modalOpen = ref(false)
const form = ref({ produto: '', tipo: 'entrada', quantidade: '', motivo: '' })

function submit() {
  if (!form.value.produto || !form.value.quantidade) return
  movimentacoes.value.unshift({
    id: Date.now(),
    produto: form.value.produto,
    tipo: form.value.tipo,
    quantidade: Number(form.value.quantidade),
    motivo: form.value.motivo || 'Ajuste manual',
    data: new Date().toISOString(),
    responsavel: 'Victor Hugo',
  })
  form.value = { produto: '', tipo: 'entrada', quantidade: '', motivo: '' }
  modalOpen.value = false
  toast.success('Movimentação registrada')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Movimentações</h1>
        <p class="text-sm text-ink-soft mt-1">Entradas, saídas e ajustes de estoque.</p>
      </div>
      <Button @click="modalOpen = true"><template #icon-left><Plus :size="16" /></template>Nova Movimentação</Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="flex-1"><SearchInput v-model="search" placeholder="Buscar produto..." /></div>
        <Select v-model="typeFilter" :options="movementTypeOptions" class="sm:w-52" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="ArrowLeftRight" empty-title="Nenhuma movimentação encontrada">
        <template #cell-produto="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-tipo="{ value }">
          <Badge :variant="typeMeta[value].variant" size="sm">
            <component :is="typeMeta[value].icon" :size="12" />
            {{ typeMeta[value].label }}
          </Badge>
        </template>
        <template #cell-quantidade="{ row }">
          <span :class="row.quantidade < 0 ? 'text-danger' : 'text-ink'" class="font-semibold">
            {{ row.quantidade > 0 ? '+' : '' }}{{ row.quantidade }}
          </span>
        </template>
        <template #cell-data="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>

    <Modal v-model="modalOpen" title="Nova movimentação" size="sm">
      <div class="space-y-4">
        <Input v-model="form.produto" label="Produto" placeholder="Nome do produto" />
        <Select v-model="form.tipo" label="Tipo" :options="[{ value: 'entrada', label: 'Entrada' }, { value: 'saida', label: 'Saída' }, { value: 'ajuste', label: 'Ajuste' }]" />
        <Input v-model="form.quantidade" type="number" label="Quantidade" />
        <Input v-model="form.motivo" label="Motivo" placeholder="Ex: Compra, avaria, contagem..." />
      </div>
      <template #footer>
        <Button variant="ghost" @click="modalOpen = false">Cancelar</Button>
        <Button @click="submit">Registrar</Button>
      </template>
    </Modal>
  </div>
</template>
