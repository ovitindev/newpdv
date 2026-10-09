<script setup>
import { computed, useId } from 'vue'

// Campo de valor em reais com máscara estilo calculadora: o teclado numérico só
// digita centavos da direita pra esquerda (1 → 0,01 · 12 → 0,12 · 1234 → 12,34).
// Evita o erro de digitar "1.234,56" num campo de texto livre. O valor emitido
// é um número em reais (ex: 1234.56).
const props = defineProps({
  modelValue: { type: [Number, String], default: 0 },
  label: { type: String, default: '' },
  large: { type: Boolean, default: false },
  placeholder: { type: String, default: '0,00' },
})
const emit = defineEmits(['update:modelValue'])
defineOptions({ inheritAttrs: false })

const id = useId()
const MAX_CENTAVOS = 99_999_999_99 // R$ 99.999.999,99
const formatter = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const centavos = computed(() => Math.round(Math.abs(Number(props.modelValue) || 0) * 100))
// Zerado mostra o placeholder em vez de "0,00" (campo parece vazio, pronto pra digitar).
const exibido = computed(() => (centavos.value ? formatter.format(centavos.value / 100) : ''))

function aoDigitar(event) {
  const digitos = event.target.value.replace(/\D/g, '').replace(/^0+/, '')
  const valor = Math.min(digitos ? parseInt(digitos, 10) : 0, MAX_CENTAVOS)
  const texto = valor ? formatter.format(valor / 100) : ''
  event.target.value = texto
  event.target.setSelectionRange(texto.length, texto.length)
  emit('update:modelValue', valor / 100)
}

const cursorNoFim = (event) => event.target.setSelectionRange(event.target.value.length, event.target.value.length)
</script>

<template>
  <label class="block">
    <span v-if="label" class="mb-1.5 block text-sm font-medium text-ink">{{ label }}</span>
    <span class="relative block">
      <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-ink-faint" :class="large ? 'text-lg' : 'text-base'">R$</span>
      <input
        :id="id"
        v-bind="$attrs"
        type="text"
        inputmode="numeric"
        autocomplete="off"
        :value="exibido"
        :placeholder="placeholder"
        class="w-full rounded-2xl border border-border bg-white pl-12 pr-4 text-right tabular-nums text-ink outline-none placeholder:text-ink-faint focus:border-brand-400"
        :class="large ? 'h-14 text-xl font-semibold' : 'h-12 text-base'"
        @input="aoDigitar"
        @focus="cursorNoFim"
        @click="cursorNoFim"
      />
    </span>
  </label>
</template>
