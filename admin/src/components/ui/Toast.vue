<script setup>
import { CheckCircle2, AlertCircle, Info, X } from '@lucide/vue'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  variant: { type: String, default: 'success' },
})

defineEmits(['close'])

const icons = { success: CheckCircle2, danger: AlertCircle, info: Info }
const iconClasses = {
  success: 'text-brand-600 bg-brand-50',
  danger: 'text-danger bg-danger-soft',
  info: 'text-info bg-info-soft',
}
</script>

<template>
  <div class="flex w-80 items-start gap-3 rounded-xl border border-border bg-white p-4 shadow-soft-lg animate-fade-in">
    <span class="flex size-8 shrink-0 items-center justify-center rounded-full" :class="iconClasses[variant]">
      <component :is="icons[variant]" :size="16" />
    </span>
    <div class="min-w-0 flex-1">
      <p class="text-sm font-semibold text-ink">{{ title }}</p>
      <p v-if="description" class="text-xs text-ink-soft mt-0.5">{{ description }}</p>
    </div>
    <button
      type="button"
      class="flex size-6 shrink-0 items-center justify-center rounded-full text-ink-faint hover:bg-surface hover:text-ink"
      aria-label="Fechar notificação"
      @click="$emit('close')"
    >
      <X :size="13" />
    </button>
  </div>
</template>
