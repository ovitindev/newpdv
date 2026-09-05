<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, Pencil, Trash2 } from '@lucide/vue'
import { getVendedores, createVendedor, updateVendedor, deleteVendedor } from '@/services/configuracoesService'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import Input from '@/components/ui/Input.vue'

const toast = useToastStore()
const loading = ref(true)
const vendedores = ref([])
const search = ref('')

onMounted(async () => {
  vendedores.value = await getVendedores()
  loading.value = false
})

const filtered = computed(() => vendedores.value.filter((v) => v.nome.toLowerCase().includes(search.value.toLowerCase())))

const columns = [
  { key: 'nome', label: 'Vendedor' },
  { key: 'email', label: 'E-mail' },
  { key: 'comissao', label: 'Comissão', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const modalOpen = ref(false)
const editingId = ref(null)
const form = ref({ nome: '', email: '', comissao: '' })

function openCreate() {
  editingId.value = null
  form.value = { nome: '', email: '', comissao: '' }
  modalOpen.value = true
}

function openEdit(vendedor) {
  editingId.value = vendedor.id
  form.value = { nome: vendedor.nome, email: vendedor.email, comissao: vendedor.comissao }
  modalOpen.value = true
}

const saving = ref(false)

async function submit() {
  if (!form.value.nome) return
  saving.value = true
  const payload = {
    nome: form.value.nome,
    email: form.value.email || null,
    comissao: Number(form.value.comissao) || 0,
  }
  try {
    if (editingId.value) {
      const atualizado = await updateVendedor(editingId.value, payload)
      const index = vendedores.value.findIndex((v) => v.id === editingId.value)
      vendedores.value[index] = atualizado
      toast.success('Vendedor atualizado')
    } else {
      const criado = await createVendedor(payload)
      vendedores.value.unshift(criado)
      toast.success('Vendedor cadastrado')
    }
    modalOpen.value = false
  } catch (error) {
    toast.error('Não foi possível salvar', error.message)
  } finally {
    saving.value = false
  }
}

async function remove(vendedor) {
  try {
    await deleteVendedor(vendedor.id)
    vendedores.value = vendedores.value.filter((v) => v.id !== vendedor.id)
    toast.info('Vendedor removido')
  } catch (error) {
    toast.error('Não foi possível remover', error.message)
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Vendedores</h1>
        <p class="text-sm text-ink-soft mt-1">Cadastre quem pode ser selecionado como vendedor no PDV.</p>
      </div>
      <Button @click="openCreate"><template #icon-left><Plus :size="16" /></template>Novo Vendedor</Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="mb-5 max-w-sm"><SearchInput v-model="search" placeholder="Buscar vendedor..." /></div>

      <Table :columns="columns" :rows="filtered" :loading="loading" empty-icon="Users" empty-title="Nenhum vendedor cadastrado">
        <template #cell-nome="{ row }">
          <div class="flex items-center gap-2.5">
            <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600 text-xs font-semibold">
              {{ row.nome.slice(0, 2).toUpperCase() }}
            </span>
            <span class="font-medium text-ink">{{ row.nome }}</span>
          </div>
        </template>
        <template #cell-comissao="{ value }"><span class="text-ink-soft">{{ value }}%</span></template>
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
    </Card>

    <Modal v-model="modalOpen" :title="editingId ? 'Editar vendedor' : 'Novo vendedor'" size="sm">
      <div class="space-y-4">
        <Input v-model="form.nome" label="Nome completo" required />
        <Input v-model="form.email" type="email" label="E-mail" />
        <Input v-model="form.comissao" type="number" label="Comissão (%)" placeholder="Ex: 2.5" />
      </div>
      <template #footer>
        <Button variant="ghost" :disabled="saving" @click="modalOpen = false">Cancelar</Button>
        <Button :loading="saving" @click="submit">{{ editingId ? 'Salvar' : 'Cadastrar' }}</Button>
      </template>
    </Modal>
  </div>
</template>
