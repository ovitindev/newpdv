<script setup>
import { computed, onMounted, ref } from 'vue'
import { getDashboardOverview } from '@/services/dashboardService'
import { getProdutos } from '@/services/produtosService'
import ChartCard from '@/components/ui/ChartCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import BarChart from '@/components/charts/BarChart.vue'
import { formatCurrency, formatNumber } from '@/utils/format'

const loading = ref(true)
const topSelling = ref([])
const produtos = ref([])

onMounted(async () => {
  const [dashboardData, produtosData] = await Promise.all([getDashboardOverview(), getProdutos()])
  topSelling.value = dashboardData.topSellingProducts
  produtos.value = produtosData
  loading.value = false
})

const rankingChart = computed(() => ({
  labels: topSelling.value.map((p) => p.name),
  values: topSelling.value.map((p) => p.revenue),
}))

const columns = [
  { key: 'nome', label: 'Produto' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'preco', label: 'Preço', align: 'right' },
  { key: 'estoque', label: 'Estoque', align: 'right' },
  { key: 'status', label: 'Status' },
]
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Relatório de Produtos</h1>
      <p class="text-sm text-ink-soft mt-1">Faturamento por produto e catálogo completo.</p>
    </div>

    <ChartCard title="Faturamento por produto" subtitle="Top 5 produtos por receita">
      <Skeleton v-if="loading" height="260px" rounded="12px" />
      <BarChart v-else :labels="rankingChart.labels" :data="rankingChart.values" horizontal height="260" />
    </ChartCard>

    <Card title="Catálogo de produtos" :padded="false" class="p-5 sm:p-6">
      <Table :columns="columns" :rows="produtos" :loading="loading" empty-title="Nenhum produto cadastrado">
        <template #cell-nome="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-preco="{ value }"><span class="text-ink-soft">{{ formatCurrency(value) }}</span></template>
        <template #cell-estoque="{ value }"><span class="text-ink-soft">{{ formatNumber(value) }} un.</span></template>
        <template #cell-status="{ value }">
          <Badge :variant="value === 'ativo' ? 'success' : 'neutral'" dot>{{ value === 'ativo' ? 'Ativo' : 'Inativo' }}</Badge>
        </template>
      </Table>
    </Card>
  </div>
</template>
