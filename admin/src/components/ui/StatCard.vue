<script setup>
import { computed } from 'vue'
import { TrendingUp, TrendingDown } from '@lucide/vue'
import AppIcon from './AppIcon.vue'
import { formatCurrency, formatNumber, formatPercent } from '@/utils/format'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: Number, required: true },
  type: { type: String, default: 'number' }, // 'number' | 'currency'
  change: { type: Number, default: null },
  icon: { type: String, default: 'Activity' },
})

const formattedValue = computed(() => (props.type === 'currency' ? formatCurrency(props.value) : formatNumber(props.value)))
const isPositive = computed(() => (props.change ?? 0) >= 0)
</script>

<template>
  <div class="card-surface p-5 flex flex-col gap-4 transition-shadow duration-150 hover:shadow-soft-lg">
    <div class="flex items-center justify-between">
      <span class="flex items-center justify-center size-10 rounded-xl bg-brand-50 text-brand-600">
        <AppIcon :name="icon" :size="20" />
      </span>
      <span
        v-if="change !== null"
        class="inline-flex items-center gap-1 text-xs font-semibold"
        :class="isPositive ? 'text-brand-600' : 'text-danger'"
      >
        <component :is="isPositive ? TrendingUp : TrendingDown" :size="14" />
        {{ formatPercent(change) }}
      </span>
    </div>
    <div>
      <p class="text-sm text-ink-soft">{{ label }}</p>
      <p class="text-2xl font-bold text-ink mt-1 tabular-nums">{{ formattedValue }}</p>
    </div>
  </div>
</template>
