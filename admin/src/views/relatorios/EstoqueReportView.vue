<script setup>
import { computed, onMounted, ref } from 'vue'
import { getProdutos } from '@/services/produtosService'
import ChartCard from '@/components/ui/ChartCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import BarChart from '@/components/charts/BarChart.vue'
import { formatCurrency } from '@/utils/format'

const loading = ref(true)
const produtos = ref([])

onMounted(async () => {
  produtos.value = await getProdutos()
  loading.value = false
})

const valorTotalEstoque = computed(() => produtos.value.reduce((sum, p) => sum + p.preco * p.estoque, 0))

const porCategoria = computed(() => {
  const map = new Map()
  produtos.value.forEach((p) => {
    map.set(p.categoria, (map.get(p.categoria) ?? 0) + p.preco * p.estoque)
  })
  return { labels: [...map.keys()], values: [...map.values()] }
})

const criticos = computed(() => produtos.value.filter((p) => p.estoque < 10).sort((a, b) => a.estoque - b.estoque))

const columns = [
  { key: 'nome', label: 'Produto' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'estoque', label: 'Estoque', align: 'right' },
  { key: 'valorEstoque', label: 'Valor em estoque', align: 'right' },
]
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Relatório de Estoque</h1>
        <p class="text-sm text-ink-soft mt-1">Valor total imobilizado em estoque e itens críticos.</p>
      </div>
      <div v-if="!loading" class="text-right">
        <p class="text-xs text-ink-soft">Valor total em estoque</p>
        <p class="text-xl font-bold text-brand-700">{{ formatCurrency(valorTotalEstoque) }}</p>
      </div>
    </div>

    <ChartCard title="Valor em estoque por categoria">
      <Skeleton v-if="loading" height="260px" rounded="12px" />
      <BarChart v-else :labels="porCategoria.labels" :data="porCategoria.values" height="260" />
    </ChartCard>

    <Card title="Produtos com estoque crítico" subtitle="Menos de 10 unidades disponíveis" :padded="false" class="p-5 sm:p-6">
      <Table
        :columns="columns"
        :rows="criticos"
        :loading="loading"
        empty-icon="PackageCheck"
        empty-title="Nenhum item crítico"
        empty-description="Todos os produtos estão com estoque saudável."
      >
        <template #cell-nome="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-estoque="{ row }">
          <Badge :variant="row.estoque === 0 ? 'danger' : 'warning'" size="sm">{{ row.estoque === 0 ? 'Esgotado' : `${row.estoque} un.` }}</Badge>
        </template>
        <template #cell-valorEstoque="{ row }"><span class="font-semibold text-ink">{{ formatCurrency(row.preco * row.estoque) }}</span></template>
      </Table>
    </Card>
  </div>
</template>
