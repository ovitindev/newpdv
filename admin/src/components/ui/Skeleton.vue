<script setup>
defineProps({
  width: { type: String, default: '100%' },
  height: { type: String, default: '1rem' },
  rounded: { type: String, default: '8px' },
})
</script>

<template>
  <span
    class="block animate-skeleton bg-surface-alt"
    :style="{ width, height, borderRadius: rounded }"
  />
</template>
