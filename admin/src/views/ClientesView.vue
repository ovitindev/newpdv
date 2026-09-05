<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, Pencil, Trash2 } from '@lucide/vue'
import { getClientes, createCliente, updateCliente, deleteCliente } from '@/services/clientesService'
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

const toast = useToastStore()
const loading = ref(true)
const clientes = ref([])
const search = ref('')
const statusFilter = ref('')

const statusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'ativo', label: 'Ativo' },
  { value: 'inativo', label: 'Inativo' },
]

onMounted(async () => {
  clientes.value = await getClientes()
  loading.value = false
})

const filtered = computed(() =>
  clientes.value.filter((c) => {
    const matchesSearch = !search.value || c.nome.toLowerCase().includes(search.value.toLowerCase()) || c.documento.includes(search.value)
    const matchesStatus = !statusFilter.value || c.status === statusFilter.value
    return matchesSearch && matchesStatus
  }),
)

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'nome', label: 'Cliente' },
  { key: 'documento', label: 'CPF/CNPJ' },
  { key: 'telefone', label: 'Telefone' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const modalOpen = ref(false)
const editingId = ref(null)
const form = ref({ nome: '', documento: '', telefone: '', email: '' })

function openCreate() {
  editingId.value = null
  form.value = { nome: '', documento: '', telefone: '', email: '' }
  modalOpen.value = true
}

function openEdit(cliente) {
  editingId.value = cliente.id
  form.value = { ...cliente }
  modalOpen.value = true
}

const saving = ref(false)

async function submit() {
  if (!form.value.nome) return
  saving.value = true
  const payload = {
    nome: form.value.nome,
    documento: form.value.documento || null,
    telefone: form.value.telefone || null,
    email: form.value.email || null,
  }
  try {
    if (editingId.value) {
      const atualizado = await updateCliente(editingId.value, payload)
      const index = clientes.value.findIndex((c) => c.id === editingId.value)
      clientes.value[index] = atualizado
      toast.success('Cliente atualizado')
    } else {
      const criado = await createCliente(payload)
      clientes.value.unshift(criado)
      toast.success('Cliente cadastrado')
    }
    modalOpen.value = false
  } catch (error) {
    toast.error('Não foi possível salvar', error.message)
  } finally {
    saving.value = false
  }
}

async function remove(cliente) {
  try {
    await deleteCliente(cliente.id)
    clientes.value = clientes.value.filter((c) => c.id !== cliente.id)
    toast.info('Cliente removido')
  } catch (error) {
    toast.error('Não foi possível remover', error.message)
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Clientes</h1>
        <p class="text-sm text-ink-soft mt-1">Base de clientes cadastrados na sua loja.</p>
      </div>
      <Button @click="openCreate"><template #icon-left><Plus :size="16" /></template>Novo Cliente</Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="flex-1"><SearchInput v-model="search" placeholder="Buscar por nome ou documento..." /></div>
        <Select v-model="statusFilter" :options="statusOptions" class="sm:w-52" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="Users" empty-title="Nenhum cliente encontrado">
        <template #cell-nome="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-totalCompras="{ value }"><span class="text-ink-soft">{{ formatNumber(value) }}</span></template>
        <template #cell-ultimaCompra="{ value }"><span class="text-ink-soft">{{ formatDate(value) }}</span></template>
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

    <Modal v-model="modalOpen" :title="editingId ? 'Editar cliente' : 'Novo cliente'" size="md">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Input v-model="form.nome" label="Nome / Razão social" class="sm:col-span-2" required />
        <Input v-model="form.documento" label="CPF/CNPJ" />
        <Input v-model="form.telefone" label="Telefone" />
        <Input v-model="form.email" label="E-mail" class="sm:col-span-2" />
      </div>
      <template #footer>
        <Button variant="ghost" :disabled="saving" @click="modalOpen = false">Cancelar</Button>
        <Button :loading="saving" @click="submit">{{ editingId ? 'Salvar' : 'Cadastrar' }}</Button>
      </template>
    </Modal>
  </div>
</template>
