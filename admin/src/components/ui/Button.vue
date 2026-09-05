<script setup>
import { computed } from 'vue'
import { Loader2 } from '@lucide/vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'outline', 'ghost', 'danger'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'icon'].includes(v),
  },
  type: { type: String, default: 'button' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
})

defineEmits(['click'])

const variantClasses = {
  primary:
    'bg-brand-500 text-white shadow-sm hover:bg-brand-600 active:bg-brand-700 disabled:bg-brand-300',
  secondary:
    'bg-brand-950 text-white hover:bg-brand-900 active:bg-brand-800 disabled:bg-brand-900/50',
  outline:
    'bg-white text-ink border border-border hover:border-brand-400 hover:text-brand-700 active:bg-brand-50',
  ghost: 'bg-transparent text-ink-soft hover:bg-surface hover:text-ink',
  danger: 'bg-danger text-white hover:brightness-95 active:brightness-90',
}

const sizeClasses = {
  sm: 'h-8 px-3 text-xs gap-1.5 rounded-[calc(var(--radius-control)-2px)]',
  md: 'h-10 px-4 text-sm gap-2 rounded-[var(--radius-control)]',
  lg: 'h-12 px-6 text-base gap-2 rounded-xl',
  icon: 'h-10 w-10 rounded-[var(--radius-control)]',
}

const classes = computed(() => [
  'inline-flex items-center justify-center font-medium transition-all duration-150 ease-out select-none',
  'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400',
  'disabled:cursor-not-allowed disabled:opacity-60',
  variantClasses[props.variant],
  sizeClasses[props.size],
  props.block ? 'w-full' : '',
])
</script>

<template>
  <button :type="type" :class="classes" :disabled="disabled || loading" @click="$emit('click', $event)">
    <Loader2 v-if="loading" class="size-4 animate-spin" />
    <slot v-else name="icon-left" />
    <slot v-if="$slots.default" />
    <slot name="icon-right" />
  </button>
</template>
