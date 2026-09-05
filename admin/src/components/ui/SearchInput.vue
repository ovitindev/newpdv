<script setup>
import { Search, X } from '@lucide/vue'

defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Buscar...' },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="relative">
    <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-faint" />
    <input
      :value="modelValue"
      type="text"
      :placeholder="placeholder"
      class="h-10 w-full rounded-[var(--radius-control)] border border-border bg-white pl-9 pr-9 text-sm text-ink placeholder:text-ink-faint transition-colors duration-150 hover:border-brand-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <button
      v-if="modelValue"
      type="button"
      class="absolute right-2.5 top-1/2 flex size-5 -translate-y-1/2 items-center justify-center rounded-full text-ink-faint hover:bg-surface hover:text-ink"
      aria-label="Limpar busca"
      @click="$emit('update:modelValue', '')"
    >
      <X :size="13" />
    </button>
  </div>
</template>
