<script setup>
import { useId } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
defineOptions({ inheritAttrs: false })

const inputId = useId()
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="inputId" class="text-sm font-medium text-ink">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    <div class="relative">
      <span v-if="$slots.icon" class="absolute inset-y-0 left-3 flex items-center text-ink-faint pointer-events-none">
        <slot name="icon" />
      </span>
      <input
        :id="inputId"
        v-bind="$attrs"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-invalid="Boolean(error)"
        class="h-10 w-full rounded-[var(--radius-control)] border bg-white px-3.5 text-sm text-ink placeholder:text-ink-faint transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400 disabled:bg-surface disabled:text-ink-faint"
        :class="[$slots.icon ? 'pl-9' : '', error ? 'border-danger' : 'border-border hover:border-brand-300']"
        @input="$emit('update:modelValue', $event.target.value)"
      />
    </div>
    <p v-if="error" class="text-xs text-danger">{{ error }}</p>
    <p v-else-if="hint" class="text-xs text-ink-faint">{{ hint }}</p>
  </div>
</template>
