<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  icon: { type: String, default: 'Inbox' },
  title: { type: String, default: 'Nada por aqui' },
  description: { type: String, default: '' },
})
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-3 py-14 text-center">
    <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-ink-faint">
      <AppIcon :name="icon" :size="26" />
    </span>
    <div>
      <p class="text-sm font-semibold text-ink">{{ title }}</p>
      <p v-if="description" class="text-sm text-ink-soft mt-1 max-w-xs">{{ description }}</p>
    </div>
    <div v-if="$slots.action" class="mt-1">
      <slot name="action" />
    </div>
  </div>
</template>
