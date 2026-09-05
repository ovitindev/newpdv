<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <div class="card-surface p-5 sm:p-6 flex flex-col gap-5">
    <header class="flex items-start justify-between gap-4">
      <div>
        <h3 class="text-base font-semibold text-ink">{{ title }}</h3>
        <p v-if="subtitle" class="text-sm text-ink-soft mt-0.5">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </header>
    <slot />
    <footer v-if="$slots.footer" class="pt-4 border-t border-border">
      <slot name="footer" />
    </footer>
  </div>
</template>
