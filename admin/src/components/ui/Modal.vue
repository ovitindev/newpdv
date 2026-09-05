<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue'
import { X } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md' }, // 'sm' | 'md' | 'lg'
})

const emit = defineEmits(['update:modelValue'])

function close() {
  emit('update:modelValue', false)
}

function handleEscape(event) {
  if (event.key === 'Escape' && props.modelValue) close()
}

onMounted(() => document.addEventListener('keydown', handleEscape))
onBeforeUnmount(() => document.removeEventListener('keydown', handleEscape))

watch(
  () => props.modelValue,
  (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : ''
  },
)

const sizeClasses = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl' }
</script>

<template>
  <Teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center bg-brand-950/40 backdrop-blur-[2px] p-4" @click.self="close">
        <transition
          appear
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-2"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="modelValue" class="w-full rounded-2xl bg-white shadow-soft-lg" :class="sizeClasses[size]" role="dialog" aria-modal="true">
            <header v-if="title || $slots.header" class="flex items-center justify-between gap-4 border-b border-border px-6 py-4">
              <slot name="header">
                <h3 class="text-base font-semibold text-ink">{{ title }}</h3>
              </slot>
              <button
                type="button"
                class="flex size-8 items-center justify-center rounded-full text-ink-faint hover:bg-surface hover:text-ink"
                aria-label="Fechar"
                @click="close"
              >
                <X :size="16" />
              </button>
            </header>
            <div class="px-6 py-5">
              <slot />
            </div>
            <footer v-if="$slots.footer" class="flex items-center justify-end gap-3 border-t border-border px-6 py-4">
              <slot name="footer" />
            </footer>
          </div>
        </transition>
      </div>
    </transition>
  </Teleport>
</template>
