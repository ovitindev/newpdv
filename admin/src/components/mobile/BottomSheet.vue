<script>
// Contador compartilhado por TODAS as instâncias (escopo de módulo).
let abertos = 0
</script>

<script setup>
import { onBeforeUnmount, watch } from 'vue'
import { X } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  full: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

// Vários sheets podem estar abertos juntos (ex: cliente dentro do carrinho): só
// libera a rolagem da página quando o último fechar.
const contabilizar = (aberto) => {
  abertos = Math.max(abertos + (aberto ? 1 : -1), 0)
  document.body.style.overflow = abertos > 0 ? 'hidden' : ''
}

const close = () => emit('update:modelValue', false)
const onKey = (e) => e.key === 'Escape' && close()

// Trava a rolagem da página de fundo enquanto o sheet está aberto.
watch(
  () => props.modelValue,
  (aberto, antes) => {
    if (Boolean(aberto) !== Boolean(antes)) contabilizar(aberto)
    if (aberto) window.addEventListener('keydown', onKey)
    else window.removeEventListener('keydown', onKey)
  },
)
onBeforeUnmount(() => {
  if (props.modelValue) contabilizar(false)
  window.removeEventListener('keydown', onKey)
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" class="fixed inset-0 z-[60] bg-brand-950/55" @click="close" />
    </Transition>
    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-y-full"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-to-class="translate-y-full"
    >
      <div
        v-if="modelValue"
        role="dialog"
        aria-modal="true"
        class="fixed inset-x-0 bottom-0 z-[61] mx-auto flex w-full max-w-xl flex-col rounded-t-3xl bg-white shadow-soft-lg"
        :class="full ? 'h-[94dvh]' : 'max-h-[90dvh]'"
      >
        <div class="mx-auto mt-2.5 h-1 w-10 shrink-0 rounded-full bg-border" />
        <header class="flex shrink-0 items-center justify-between px-5 pb-2 pt-3">
          <h2 class="text-lg font-semibold text-ink">{{ title }}</h2>
          <button class="flex size-10 items-center justify-center rounded-full bg-surface text-ink-soft active:bg-surface-alt" aria-label="Fechar" @click="close">
            <X :size="18" />
          </button>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pb-4">
          <slot />
        </div>
        <footer v-if="$slots.footer" class="shrink-0 border-t border-border bg-white px-5 pb-[max(1rem,env(safe-area-inset-bottom))] pt-3">
          <slot name="footer" />
        </footer>
        <div v-else class="shrink-0 pb-[env(safe-area-inset-bottom)]" />
      </div>
    </Transition>
  </Teleport>
</template>
