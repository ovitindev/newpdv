<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['success', 'warning', 'danger', 'info', 'neutral', 'brand'].includes(v),
  },
  dot: { type: Boolean, default: false },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
})

const variantClasses = {
  success: 'bg-brand-50 text-brand-700',
  warning: 'bg-warning-soft text-warning',
  danger: 'bg-danger-soft text-danger',
  info: 'bg-info-soft text-info',
  neutral: 'bg-surface text-ink-soft',
  brand: 'bg-brand-950 text-white',
}

const dotClasses = {
  success: 'bg-brand-500',
  warning: 'bg-warning',
  danger: 'bg-danger',
  info: 'bg-info',
  neutral: 'bg-ink-faint',
  brand: 'bg-brand-300',
}

const classes = computed(() => [
  'inline-flex items-center gap-1.5 rounded-full font-medium whitespace-nowrap',
  props.size === 'sm' ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-1 text-xs',
  variantClasses[props.variant],
])
</script>

<template>
  <span :class="classes">
    <span v-if="dot" class="size-1.5 rounded-full" :class="dotClasses[variant]" />
    <slot />
  </span>
</template>
