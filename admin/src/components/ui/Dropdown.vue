<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  align: { type: String, default: 'right' }, // 'left' | 'right'
})

const open = ref(false)
const root = ref(null)

function toggle() {
  open.value = !open.value
}

function close() {
  open.value = false
}

function handleClickOutside(event) {
  if (root.value && !root.value.contains(event.target)) close()
}

function handleEscape(event) {
  if (event.key === 'Escape') close()
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
})

defineExpose({ close })
</script>

<template>
  <div ref="root" class="relative inline-block text-left">
    <div @click="toggle">
      <slot name="trigger" :open="open" />
    </div>
    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="open"
        class="absolute z-40 mt-2 min-w-[14rem] rounded-xl border border-border bg-white p-1.5 shadow-soft-lg"
        :class="props.align === 'right' ? 'right-0' : 'left-0'"
        @click="close"
      >
        <slot />
      </div>
    </transition>
  </div>
</template>
