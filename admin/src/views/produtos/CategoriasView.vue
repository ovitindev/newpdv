<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, Pencil, Trash2 } from '@lucide/vue'
import { getCategorias } from '@/services/produtosService'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import Input from '@/components/ui/Input.vue'
import { formatNumber } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const categorias = ref([])
const search = ref('')

onMounted(async () => {
  categorias.value = await getCategorias()
  loading.value = false
})

const filtered = computed(() => categorias.value.filter((c) => c.nome.toLowerCase().includes(search.value.toLowerCase())))

const columns = [
  { key: 'nome', label: 'Categoria' },
  { key: 'produtos', label: 'Produtos', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const modalOpen = ref(false)
const editingId = ref(null)
const form = ref({ nome: '' })

function openCreate() {
  editingId.value = null
  form.value = { nome: '' }
  modalOpen.value = true
}

function openEdit(categoria) {
  editingId.value = categoria.id
  form.value = { nome: categoria.nome }
  modalOpen.value = true
}

function submit() {
  if (!form.value.nome) return
  if (editingId.value) {
    const index = categorias.value.findIndex((c) => c.id === editingId.value)
    categorias.value[index].nome = form.value.nome
    toast.success('Categoria atualizada')
  } else {
    categorias.value.unshift({ id: Date.now(), nome: form.value.nome, produtos: 0, status: 'ativo' })
    toast.success('Categoria cadastrada')
  }
  modalOpen.value = false
}

function remove(categoria) {
  categorias.value = categorias.value.filter((c) => c.id !== categoria.id)
  toast.info('Categoria removida')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Categorias</h1>
        <p class="text-sm text-ink-soft mt-1">Organize seus produtos por categoria.</p>
      </div>
      <Button @click="openCreate"><template #icon-left><Plus :size="16" /></template>Nova Categoria</Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="mb-5 max-w-sm"><SearchInput v-model="search" placeholder="Buscar categoria..." /></div>

      <Table :columns="columns" :rows="filtered" :loading="loading" empty-icon="Tags" empty-title="Nenhuma categoria cadastrada">
        <template #cell-nome="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-produtos="{ value }"><span class="text-ink-soft">{{ formatNumber(value) }}</span></template>
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

    <Modal v-model="modalOpen" :title="editingId ? 'Editar categoria' : 'Nova categoria'" size="sm">
      <Input v-model="form.nome" label="Nome da categoria" required />
      <template #footer>
        <Button variant="ghost" @click="modalOpen = false">Cancelar</Button>
        <Button @click="submit">{{ editingId ? 'Salvar' : 'Cadastrar' }}</Button>
      </template>
    </Modal>
  </div>
</template>
