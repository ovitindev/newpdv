<script setup>
import { useId } from 'vue'
import { Calendar } from '@lucide/vue'

defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const inputId = useId()
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="inputId" class="text-sm font-medium text-ink">{{ label }}</label>
    <div class="relative">
      <Calendar :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-faint" />
      <input
        :id="inputId"
        type="date"
        :value="modelValue"
        class="h-10 w-full rounded-[var(--radius-control)] border border-border bg-white pl-9 pr-3 text-sm text-ink transition-colors duration-150 hover:border-brand-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400"
        @input="$emit('update:modelValue', $event.target.value)"
      />
    </div>
  </div>
</template>
