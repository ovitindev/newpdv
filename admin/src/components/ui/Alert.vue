<script setup>
import { CheckCircle2, AlertTriangle, AlertCircle, Info } from '@lucide/vue'

const props = defineProps({
  variant: { type: String, default: 'info' }, // 'success' | 'warning' | 'danger' | 'info'
  title: { type: String, default: '' },
})

const icons = { success: CheckCircle2, warning: AlertTriangle, danger: AlertCircle, info: Info }
const classes = {
  success: 'bg-brand-50 text-brand-800 border-brand-100',
  warning: 'bg-warning-soft text-[#8a5a10] border-warning/20',
  danger: 'bg-danger-soft text-[#9c2b2f] border-danger/20',
  info: 'bg-info-soft text-[#1d4ed8] border-info/20',
}
</script>

<template>
  <div class="flex items-start gap-3 rounded-xl border px-4 py-3.5 text-sm" :class="classes[variant]">
    <component :is="icons[variant]" :size="18" class="mt-0.5 shrink-0" />
    <div>
      <p v-if="title" class="font-semibold">{{ title }}</p>
      <div class="opacity-90"><slot /></div>
    </div>
  </div>
</template>
