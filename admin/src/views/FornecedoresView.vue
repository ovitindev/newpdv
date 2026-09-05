<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, Pencil, Trash2 } from '@lucide/vue'
import { getFornecedores } from '@/services/fornecedoresService'
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

const toast = useToastStore()
const loading = ref(true)
const fornecedores = ref([])
const search = ref('')

onMounted(async () => {
  fornecedores.value = await getFornecedores()
  loading.value = false
})

const filtered = computed(() => fornecedores.value.filter((f) => f.nome.toLowerCase().includes(search.value.toLowerCase())))

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'nome', label: 'Fornecedor' },
  { key: 'documento', label: 'CNPJ' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'telefone', label: 'Telefone' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const modalOpen = ref(false)
const editingId = ref(null)
const form = ref({ nome: '', documento: '', categoria: '', telefone: '', email: '' })

function openCreate() {
  editingId.value = null
  form.value = { nome: '', documento: '', categoria: '', telefone: '', email: '' }
  modalOpen.value = true
}

function openEdit(fornecedor) {
  editingId.value = fornecedor.id
  form.value = { ...fornecedor }
  modalOpen.value = true
}

function submit() {
  if (!form.value.nome) return
  if (editingId.value) {
    const index = fornecedores.value.findIndex((f) => f.id === editingId.value)
    fornecedores.value[index] = { ...fornecedores.value[index], ...form.value }
    toast.success('Fornecedor atualizado')
  } else {
    fornecedores.value.unshift({ id: Date.now(), ...form.value, status: 'ativo' })
    toast.success('Fornecedor cadastrado')
  }
  modalOpen.value = false
}

function remove(fornecedor) {
  fornecedores.value = fornecedores.value.filter((f) => f.id !== fornecedor.id)
  toast.info('Fornecedor removido')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Fornecedores</h1>
        <p class="text-sm text-ink-soft mt-1">Empresas que fornecem produtos para sua loja.</p>
      </div>
      <Button @click="openCreate"><template #icon-left><Plus :size="16" /></template>Novo Fornecedor</Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="mb-5 max-w-sm"><SearchInput v-model="search" placeholder="Buscar fornecedor..." /></div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="Truck" empty-title="Nenhum fornecedor encontrado">
        <template #cell-nome="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-status="{ value }">
          <Badge :variant="value === 'ativo' ? 'success' : 'neutral'" dot>{{ value === 'ativo' ? 'Ativo' : 'Inativo' }}</Badge>
        </template>
        <template #cell-acoes="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-ink" @click="openEdit(row)">
              <Pencil :size="15" />
            </button>
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-danger-soft hover:text-danger" @click="remove(row)">
              <Trash2 :size="15" />
            </button>
          </div>
        </template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>

    <Modal v-model="modalOpen" :title="editingId ? 'Editar fornecedor' : 'Novo fornecedor'" size="md">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Input v-model="form.nome" label="Razão social" class="sm:col-span-2" required />
        <Input v-model="form.documento" label="CNPJ" />
        <Input v-model="form.categoria" label="Categoria fornecida" />
        <Input v-model="form.telefone" label="Telefone" />
        <Input v-model="form.email" label="E-mail" />
      </div>
      <template #footer>
        <Button variant="ghost" @click="modalOpen = false">Cancelar</Button>
        <Button @click="submit">{{ editingId ? 'Salvar' : 'Cadastrar' }}</Button>
      </template>
    </Modal>
  </div>
</template>
