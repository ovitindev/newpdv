<script setup>
import { computed, ref, useId } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: 0 },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '0,00' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
defineOptions({ inheritAttrs: false })

const inputId = useId()
const formatter = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const displayValue = computed(() => {
  const cents = Math.round(Math.abs(Number(props.modelValue) || 0) * 100)
  return formatter.format(cents / 100)
})

function handleInput(event) {
  const digits = event.target.value.replace(/\D/g, '')
  const cents = digits ? parseInt(digits, 10) : 0
  const value = cents / 100
  const formatted = formatter.format(value)
  event.target.value = formatted
  event.target.setSelectionRange(formatted.length, formatted.length)
  emit('update:modelValue', value)
}

// Todo o texto fica alinhado à direita e a digitação sempre lê da direita
// para a esquerda (estilo calculadora), então o cursor não pode ficar solto
// no meio/início do campo — senão Backspace ali não apaga nada.
function moveCaretToEnd(event) {
  const el = event.target
  el.setSelectionRange(el.value.length, el.value.length)
}

const inputEl = ref(null)
defineExpose({
  focus: () => inputEl.value?.focus(),
})
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="inputId" class="text-sm font-medium text-ink">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    <div class="relative">
      <span class="absolute inset-y-0 left-3 flex items-center text-sm text-ink-faint pointer-events-none">R$</span>
      <input
        ref="inputEl"
        :id="inputId"
        v-bind="$attrs"
        type="text"
        inputmode="decimal"
        autocomplete="off"
        :value="displayValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-invalid="Boolean(error)"
        class="h-10 w-full rounded-[var(--radius-control)] border bg-white py-2 pl-9 pr-3.5 text-right text-sm text-ink tabular-nums placeholder:text-ink-faint transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400 disabled:bg-surface disabled:text-ink-faint"
        :class="[error ? 'border-danger' : 'border-border hover:border-brand-300']"
        @input="handleInput"
        @focus="moveCaretToEnd"
        @click="moveCaretToEnd"
        @keydown.left.prevent
        @keydown.right.prevent
      />
    </div>
    <p v-if="error" class="text-xs text-danger">{{ error }}</p>
    <p v-else-if="hint" class="text-xs text-ink-faint">{{ hint }}</p>
  </div>
</template>
