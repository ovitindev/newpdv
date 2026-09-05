<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  padded: { type: Boolean, default: true },
})
</script>

<template>
  <section class="card-surface" :class="padded ? 'p-5 sm:p-6' : ''">
    <header v-if="title || $slots.actions" class="flex items-start justify-between gap-4 mb-5">
      <div>
        <h3 v-if="title" class="text-base font-semibold text-ink">{{ title }}</h3>
        <p v-if="subtitle" class="text-sm text-ink-soft mt-0.5">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </header>
    <slot />
  </section>
</template>
