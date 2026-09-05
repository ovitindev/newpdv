<script setup>
defineProps({
  modelValue: { type: String, required: true },
  tabs: { type: Array, required: true }, // [{ value, label, icon? }]
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="flex items-center gap-1 rounded-xl bg-surface p-1 w-fit overflow-x-auto">
    <button
      v-for="tab in tabs"
      :key="tab.value"
      type="button"
      class="whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium transition-colors duration-150"
      :class="modelValue === tab.value ? 'bg-white text-brand-700 shadow-sm' : 'text-ink-soft hover:text-ink'"
      @click="$emit('update:modelValue', tab.value)"
    >
      {{ tab.label }}
    </button>
  </div>
</template>
