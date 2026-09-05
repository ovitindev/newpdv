<script setup>
import { useId } from 'vue'
import { ChevronDown } from '@lucide/vue'

defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  options: { type: Array, default: () => [] }, // [{ value, label }]
  disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const selectId = useId()
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="selectId" class="text-sm font-medium text-ink">{{ label }}</label>
    <div class="relative">
      <select
        :id="selectId"
        :value="modelValue"
        :disabled="disabled"
        class="h-10 w-full appearance-none rounded-[var(--radius-control)] border border-border bg-white pl-3.5 pr-9 text-sm text-ink transition-colors duration-150 hover:border-brand-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400 disabled:bg-surface disabled:text-ink-faint"
        @change="$emit('update:modelValue', $event.target.value)"
      >
        <option v-for="option in options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
      <ChevronDown :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-faint" />
    </div>
  </div>
</template>
