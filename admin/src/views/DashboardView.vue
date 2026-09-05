<script setup>
import { onMounted, ref } from 'vue'
import { ShoppingBag, Receipt } from '@lucide/vue'
import { useAuthStore } from '@/stores/auth'
import { getDashboardOverview } from '@/services/dashboardService'
import StatCard from '@/components/ui/StatCard.vue'
import ChartCard from '@/components/ui/ChartCard.vue'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import LineChart from '@/components/charts/LineChart.vue'
import DoughnutChart from '@/components/charts/DoughnutChart.vue'
import BarChart from '@/components/charts/BarChart.vue'
import { formatCurrency, formatNumber, formatRelativeTime, minutesAgo } from '@/utils/format'

const authStore = useAuthStore()
const loading = ref(true)
const data = ref(null)

const activityIcon = { sale: Receipt, return: Receipt, product: ShoppingBag }
const activityIconClasses = {
  sale: 'bg-brand-50 text-brand-600',
  return: 'bg-danger-soft text-danger',
  product: 'bg-info-soft text-info',
}

onMounted(async () => {
  data.value = await getDashboardOverview()
  loading.value = false
})
</script>

<template>
  <div class="space-y-6">
    <!-- Cabeçalho -->
    <div>
      <h1 class="text-2xl font-semibold text-ink">{{ authStore.greeting }}, {{ authStore.firstName }} 👋</h1>
      <p class="text-sm text-ink-soft mt-1">Acompanhe o desempenho da sua loja hoje.</p>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <template v-if="loading">
        <div v-for="n in 4" :key="n" class="card-surface p-5">
          <Skeleton height="2.5rem" width="2.5rem" rounded="12px" />
          <Skeleton height="0.8rem" width="60%" class="mt-4" />
          <Skeleton height="1.5rem" width="45%" class="mt-2" />
        </div>
      </template>
      <template v-else>
        <StatCard
          v-for="stat in data.stats"
          :key="stat.key"
          :label="stat.label"
          :value="stat.value"
          :type="stat.type"
          :change="stat.change"
          :icon="stat.icon"
        />
      </template>
    </div>

    <!-- Vendas por período + Formas de pagamento -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
      <ChartCard title="Vendas por período" subtitle="Últimos 7 dias" class="xl:col-span-2">
        <template #actions>
          <Badge variant="success" dot>Ao vivo</Badge>
        </template>
        <Skeleton v-if="loading" height="280px" rounded="12px" />
        <LineChart v-else :labels="data.salesByPeriod.labels" :data="data.salesByPeriod.values" />
      </ChartCard>

      <ChartCard title="Formas de pagamento" subtitle="Participação no período">
        <Skeleton v-if="loading" height="220px" rounded="12px" />
        <template v-else>
          <DoughnutChart :items="data.paymentMethodsBreakdown" />
          <ul class="space-y-2.5 mt-1">
            <li v-for="method in data.paymentMethodsBreakdown" :key="method.label" class="flex items-center justify-between text-sm">
              <span class="flex items-center gap-2 text-ink-soft">
                <span class="size-2.5 rounded-full" :style="{ backgroundColor: method.color }" />
                {{ method.label }}
              </span>
              <span class="font-semibold text-ink">{{ method.value }}%</span>
            </li>
          </ul>
        </template>
      </ChartCard>
    </div>

    <!-- Produtos mais vendidos + Faturamento -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
      <ChartCard title="Produtos mais vendidos" subtitle="Ranking por unidades vendidas" class="xl:col-span-2">
        <div v-if="loading" class="space-y-4">
          <Skeleton v-for="n in 5" :key="n" height="2.5rem" rounded="10px" />
        </div>
        <ul v-else class="space-y-4">
          <li v-for="(product, index) in data.topSellingProducts" :key="product.id" class="flex items-center gap-4">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-surface text-xs font-semibold text-ink-soft">
              {{ index + 1 }}
            </span>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-ink truncate">{{ product.name }}</p>
                <p class="text-sm font-semibold text-ink shrink-0">{{ formatNumber(product.sales) }} un.</p>
              </div>
              <div class="mt-1.5 h-1.5 w-full rounded-full bg-surface overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-brand-300 to-brand-500" :style="{ width: `${product.share}%` }" />
              </div>
            </div>
          </li>
        </ul>
      </ChartCard>

      <ChartCard title="Faturamento" subtitle="Hoje vs. semana vs. mês">
        <Skeleton v-if="loading" height="220px" rounded="12px" />
        <BarChart v-else :labels="data.revenueComparison.labels" :data="data.revenueComparison.values" height="220" />
      </ChartCard>
    </div>

    <!-- Atividades recentes + Status Fiscal -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
      <Card title="Atividades recentes" subtitle="O que aconteceu na sua loja agora há pouco" class="xl:col-span-2">
        <div v-if="loading" class="space-y-4">
          <Skeleton v-for="n in 4" :key="n" height="2.75rem" rounded="10px" />
        </div>
        <ul v-else class="divide-y divide-border">
          <li v-for="activity in data.recentActivity" :key="activity.id" class="flex items-center gap-3.5 py-3 first:pt-0 last:pb-0">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full" :class="activityIconClasses[activity.type]">
              <component :is="activityIcon[activity.type]" :size="16" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-ink truncate">{{ activity.title }}</p>
              <p class="text-xs text-ink-soft mt-0.5">
                <template v-if="activity.amount">{{ formatCurrency(activity.amount) }} · {{ activity.method }}</template>
                <template v-else>{{ activity.detail }}</template>
              </p>
            </div>
            <span class="text-xs text-ink-faint shrink-0">{{ formatRelativeTime(minutesAgo(activity.minutesAgo)) }}</span>
          </li>
        </ul>
      </Card>

      <Card title="Status Fiscal" subtitle="Conexão com a SEFAZ e certificado">
        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="n in 4" :key="n" height="2.25rem" rounded="10px" />
        </div>
        <ul v-else class="space-y-1">
          <li v-for="item in data.fiscalStatus" :key="item.key" class="flex items-center justify-between py-2">
            <span class="text-sm text-ink-soft">{{ item.label }}</span>
            <Badge variant="success" dot>{{ item.detail }}</Badge>
          </li>
        </ul>
      </Card>
    </div>

    <!-- Estoque -->
    <Card title="Estoque" subtitle="Visão geral do seu inventário">
      <template #actions>
        <router-link to="/produtos/estoque" class="text-sm font-medium text-brand-600 hover:text-brand-700">Ver tudo</router-link>
      </template>
      <div v-if="loading" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <Skeleton v-for="n in 3" :key="n" height="4rem" rounded="12px" />
        </div>
        <Skeleton height="10rem" rounded="12px" />
      </div>
      <template v-else>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
          <div class="rounded-xl bg-surface p-4">
            <p class="text-xs text-ink-soft">Produtos em estoque</p>
            <p class="text-xl font-bold text-ink mt-1">{{ formatNumber(data.stockSummary.inStock) }}</p>
          </div>
          <div class="rounded-xl bg-warning-soft p-4">
            <p class="text-xs text-[#8a5a10]">Estoque baixo</p>
            <p class="text-xl font-bold text-[#8a5a10] mt-1">{{ formatNumber(data.stockSummary.lowStock) }}</p>
          </div>
          <div class="rounded-xl bg-danger-soft p-4">
            <p class="text-xs text-[#9c2b2f]">Sem estoque</p>
            <p class="text-xl font-bold text-[#9c2b2f] mt-1">{{ formatNumber(data.stockSummary.outOfStock) }}</p>
          </div>
        </div>
        <div class="overflow-x-auto -mx-5 sm:-mx-6 px-5 sm:px-6">
          <table class="w-full min-w-[420px] text-sm">
            <thead>
              <tr class="border-b border-border text-xs uppercase tracking-wide text-ink-faint">
                <th class="py-2.5 px-3 first:pl-0 text-left font-medium">Produto</th>
                <th class="py-2.5 px-3 text-right font-medium">Vendas</th>
                <th class="py-2.5 px-3 last:pr-0 text-right font-medium">Estoque</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data.stockTopProducts" :key="item.id" class="border-b border-border last:border-0">
                <td class="py-3 px-3 first:pl-0 text-ink font-medium">{{ item.name }}</td>
                <td class="py-3 px-3 text-right text-ink-soft">{{ formatNumber(item.sales) }}</td>
                <td class="py-3 px-3 last:pr-0 text-right">
                  <Badge v-if="item.stock === 0" variant="danger" size="sm">Esgotado</Badge>
                  <Badge v-else-if="item.stock < 10" variant="warning" size="sm">{{ item.stock }} un.</Badge>
                  <Badge v-else variant="neutral" size="sm">{{ item.stock }} un.</Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </Card>
  </div>
</template>
