<script setup>
import { computed } from 'vue'
import * as icons from '@lucide/vue'
import { HelpCircle } from '@lucide/vue'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 18 },
  strokeWidth: { type: [Number, String], default: 1.75 },
})

const IconComponent = computed(() => icons[props.name] ?? HelpCircle)
</script>

<template>
  <component :is="IconComponent" :size="size" :stroke-width="strokeWidth" />
</template>
