<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="modelValue"
    :disabled="disabled"
    class="relative h-6 w-11 shrink-0 rounded-full transition-colors disabled:opacity-50"
    :class="modelValue ? 'bg-brand-500' : 'bg-border'"
    @click="$emit('update:modelValue', !modelValue)"
  >
    <span
      class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow-soft transition-transform"
      :class="modelValue ? 'translate-x-5' : ''"
    />
  </button>
</template>
