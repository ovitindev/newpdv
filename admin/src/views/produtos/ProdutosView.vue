<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, Pencil, Trash2 } from '@lucide/vue'
import { getProdutos, getCategorias, createProduto, updateProduto, deleteProduto } from '@/services/produtosService'
import { productStatusOptions, stockStatusOptions } from '@/data/mock/produtos'
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
import MoneyInput from '@/components/ui/MoneyInput.vue'
import { formatCurrency, formatNumber } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const produtos = ref([])
const categorias = ref([])

const search = ref('')
const categoryFilter = ref('')
const statusFilter = ref('')
const stockFilter = ref('')

onMounted(async () => {
  const [produtosData, categoriasData] = await Promise.all([getProdutos(), getCategorias()])
  produtos.value = produtosData
  categorias.value = categoriasData
  loading.value = false
})

const categoryOptions = computed(() => [{ value: '', label: 'Todas as categorias' }, ...categorias.value.map((c) => ({ value: c.nome, label: c.nome }))])

function matchesStock(produto) {
  if (stockFilter.value === 'baixo') return produto.estoque > 0 && produto.estoque < 10
  if (stockFilter.value === 'esgotado') return produto.estoque === 0
  if (stockFilter.value === 'disponivel') return produto.estoque > 0
  return true
}

const filtered = computed(() =>
  produtos.value.filter((produto) => {
    const matchesSearch =
      !search.value ||
      produto.nome.toLowerCase().includes(search.value.toLowerCase()) ||
      produto.codigo.toLowerCase().includes(search.value.toLowerCase())
    const matchesCategory = !categoryFilter.value || produto.categoria === categoryFilter.value
    const matchesStatus = !statusFilter.value || produto.status === statusFilter.value
    return matchesSearch && matchesCategory && matchesStatus && matchesStock(produto)
  }),
)

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'codigo', label: 'Código' },
  { key: 'nome', label: 'Produto' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'preco', label: 'Preço', align: 'right' },
  { key: 'estoque', label: 'Estoque', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const modalOpen = ref(false)
const editingId = ref(null)
const form = ref({ nome: '', codigo: '', categoria: '', preco: '', estoque: '', status: 'ativo' })

function openCreate() {
  editingId.value = null
  form.value = { nome: '', codigo: '', categoria: categorias.value[0]?.nome ?? '', preco: '', estoque: '', status: 'ativo' }
  modalOpen.value = true
}

function openEdit(produto) {
  editingId.value = produto.id
  form.value = { ...produto }
  modalOpen.value = true
}

const saving = ref(false)

async function submit() {
  if (!form.value.nome || !form.value.codigo) return
  saving.value = true
  const payload = {
    nome: form.value.nome,
    codigo: form.value.codigo,
    categoria: form.value.categoria || null,
    preco: Number(form.value.preco) || 0,
    estoque: Number(form.value.estoque) || 0,
    status: form.value.status,
  }
  try {
    if (editingId.value) {
      const atualizado = await updateProduto(editingId.value, payload)
      const index = produtos.value.findIndex((p) => p.id === editingId.value)
      produtos.value[index] = atualizado
      toast.success('Produto atualizado', `${atualizado.nome} foi atualizado.`)
    } else {
      const criado = await createProduto(payload)
      produtos.value.unshift(criado)
      toast.success('Produto cadastrado', `${criado.nome} foi adicionado ao catálogo.`)
    }
    modalOpen.value = false
  } catch (error) {
    toast.error('Não foi possível salvar', error.message)
  } finally {
    saving.value = false
  }
}

async function remove(produto) {
  try {
    await deleteProduto(produto.id)
    produtos.value = produtos.value.filter((p) => p.id !== produto.id)
    toast.info('Produto removido', `${produto.nome} foi removido do catálogo.`)
  } catch (error) {
    toast.error('Não foi possível remover', error.message)
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Produtos</h1>
        <p class="text-sm text-ink-soft mt-1">Gerencie o catálogo de produtos da sua loja.</p>
      </div>
      <Button @click="openCreate">
        <template #icon-left><Plus :size="16" /></template>
        Novo Produto
      </Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
        <SearchInput v-model="search" placeholder="Buscar por nome ou código..." class="xl:col-span-2" />
        <Select v-model="categoryFilter" :options="categoryOptions" />
        <Select v-model="statusFilter" :options="productStatusOptions" />
      </div>
      <div class="mb-5 -mt-2">
        <Select v-model="stockFilter" :options="stockStatusOptions" class="w-full sm:w-64" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="PackageSearch" empty-title="Nenhum produto encontrado">
        <template #cell-codigo="{ value }"><span class="text-ink-faint font-mono text-xs">{{ value }}</span></template>
        <template #cell-nome="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-preco="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-estoque="{ value }">
          <Badge v-if="value === 0" variant="danger" size="sm">Esgotado</Badge>
          <Badge v-else-if="value < 10" variant="warning" size="sm">{{ formatNumber(value) }} un.</Badge>
          <span v-else class="text-ink-soft">{{ formatNumber(value) }} un.</span>
        </template>
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

    <Modal v-model="modalOpen" :title="editingId ? 'Editar produto' : 'Novo produto'" size="md">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Input v-model="form.nome" label="Nome do produto" class="sm:col-span-2" required />
        <Input v-model="form.codigo" label="Código / EAN" required />
        <Select v-model="form.categoria" label="Categoria" :options="categorias.map((c) => ({ value: c.nome, label: c.nome }))" />
        <MoneyInput v-model="form.preco" label="Preço" required />
        <Input v-model="form.estoque" type="number" label="Estoque inicial" />
        <Select v-model="form.status" label="Status" :options="[{ value: 'ativo', label: 'Ativo' }, { value: 'inativo', label: 'Inativo' }]" />
      </div>
      <template #footer>
        <Button variant="ghost" :disabled="saving" @click="modalOpen = false">Cancelar</Button>
        <Button :loading="saving" @click="submit">{{ editingId ? 'Salvar alterações' : 'Cadastrar produto' }}</Button>
      </template>
    </Modal>
  </div>
</template>
