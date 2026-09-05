<script setup>
import { computed, onMounted, ref } from 'vue'
import { PackageCheck, PackageMinus, PackageX } from '@lucide/vue'
import { getProdutos } from '@/services/produtosService'
import { stockStatusOptions } from '@/data/mock/produtos'
import { usePagination } from '@/utils/usePagination'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Select from '@/components/ui/Select.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import { formatNumber, formatCurrency } from '@/utils/format'

const loading = ref(true)
const produtos = ref([])
const search = ref('')
const stockFilter = ref('')

onMounted(async () => {
  produtos.value = await getProdutos()
  loading.value = false
})

const summary = computed(() => ({
  emEstoque: produtos.value.filter((p) => p.estoque > 0).length,
  baixo: produtos.value.filter((p) => p.estoque > 0 && p.estoque < 10).length,
  esgotado: produtos.value.filter((p) => p.estoque === 0).length,
}))

function matchesStock(produto) {
  if (stockFilter.value === 'baixo') return produto.estoque > 0 && produto.estoque < 10
  if (stockFilter.value === 'esgotado') return produto.estoque === 0
  if (stockFilter.value === 'disponivel') return produto.estoque > 0
  return true
}

const filtered = computed(() =>
  produtos.value
    .filter((p) => !search.value || p.nome.toLowerCase().includes(search.value.toLowerCase()))
    .filter(matchesStock)
    .sort((a, b) => a.estoque - b.estoque),
)

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'nome', label: 'Produto' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'preco', label: 'Preço', align: 'right' },
  { key: 'estoque', label: 'Estoque', align: 'right' },
  { key: 'valorEstoque', label: 'Valor em estoque', align: 'right' },
]
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Estoque</h1>
      <p class="text-sm text-ink-soft mt-1">Visão consolidada do inventário da sua loja.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <template v-if="loading">
        <Skeleton v-for="n in 3" :key="n" height="5.5rem" rounded="16px" />
      </template>
      <template v-else>
        <div class="card-surface p-5 flex items-center gap-4">
          <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><PackageCheck :size="20" /></span>
          <div>
            <p class="text-sm text-ink-soft">Produtos em estoque</p>
            <p class="text-xl font-bold text-ink">{{ formatNumber(summary.emEstoque) }}</p>
          </div>
        </div>
        <div class="card-surface p-5 flex items-center gap-4">
          <span class="flex size-11 items-center justify-center rounded-xl bg-warning-soft text-warning"><PackageMinus :size="20" /></span>
          <div>
            <p class="text-sm text-ink-soft">Estoque baixo</p>
            <p class="text-xl font-bold text-ink">{{ formatNumber(summary.baixo) }}</p>
          </div>
        </div>
        <div class="card-surface p-5 flex items-center gap-4">
          <span class="flex size-11 items-center justify-center rounded-xl bg-danger-soft text-danger"><PackageX :size="20" /></span>
          <div>
            <p class="text-sm text-ink-soft">Sem estoque</p>
            <p class="text-xl font-bold text-ink">{{ formatNumber(summary.esgotado) }}</p>
          </div>
        </div>
      </template>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="flex-1"><SearchInput v-model="search" placeholder="Buscar produto..." /></div>
        <Select v-model="stockFilter" :options="stockStatusOptions" class="sm:w-56" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="Warehouse" empty-title="Nenhum produto encontrado">
        <template #cell-nome="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-preco="{ value }"><span class="text-ink-soft">{{ formatCurrency(value) }}</span></template>
        <template #cell-estoque="{ row }">
          <Badge v-if="row.estoque === 0" variant="danger" size="sm">Esgotado</Badge>
          <Badge v-else-if="row.estoque < 10" variant="warning" size="sm">{{ row.estoque }} un.</Badge>
          <span v-else class="text-ink-soft">{{ formatNumber(row.estoque) }} un.</span>
        </template>
        <template #cell-valorEstoque="{ row }"><span class="font-semibold text-ink">{{ formatCurrency(row.preco * row.estoque) }}</span></template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>
  </div>
</template>
