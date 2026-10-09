<script setup>
import { ref } from 'vue'
import { Share2, Download } from '@lucide/vue'
import { baixarArquivo, compartilharPdf, gerarPdfDoElemento, podeCompartilharArquivo } from '@/utils/recibo'
import { useToastStore } from '@/stores/toast'
import BottomSheet from '@/components/mobile/BottomSheet.vue'
import ReciboVenda from '@/components/pdv/ReciboVenda.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  recibo: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue'])

const toast = useToastStore()
const papel = ref(null)
const gerando = ref(false)
const podeCompartilhar = podeCompartilharArquivo()

const nomeArquivo = () => `recibo-venda-${String(props.recibo.id).padStart(6, '0')}.pdf`

async function acao(tipo) {
  if (gerando.value || !papel.value) return
  gerando.value = true
  try {
    const pdf = await gerarPdfDoElemento(papel.value)
    if (tipo === 'compartilhar') await compartilharPdf(pdf, nomeArquivo(), `Recibo da venda #${props.recibo.id}`)
    else baixarArquivo(pdf, nomeArquivo())
  } catch (e) {
    toast.error('Não foi possível gerar o PDF', e?.message)
  } finally {
    gerando.value = false
  }
}
</script>

<template>
  <BottomSheet :model-value="modelValue" title="Recibo" full @update:model-value="emit('update:modelValue', $event)">
    <!-- Mesmo recibo do PC, sobre um "papel" branco; é este bloco que vira o PDF. -->
    <div class="-mx-1 rounded-2xl bg-surface-alt p-3">
      <div ref="papel" class="mx-auto w-fit bg-white px-5 py-6 shadow-soft">
        <ReciboVenda v-if="recibo" :venda="recibo" />
      </div>
    </div>
    <p class="mt-3 pb-2 text-center text-xs text-ink-faint">Documento sem valor fiscal</p>

    <template #footer>
      <div class="flex gap-3">
        <button class="flex h-14 items-center justify-center gap-2 rounded-2xl bg-surface font-medium text-ink active:bg-surface-alt disabled:opacity-60" :class="podeCompartilhar ? 'px-5' : 'flex-1'" :disabled="gerando" @click="acao('baixar')">
          <Download :size="19" />{{ gerando && !podeCompartilhar ? 'Gerando...' : 'Baixar PDF' }}
        </button>
        <button v-if="podeCompartilhar" class="flex h-14 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-600 font-semibold text-white active:bg-brand-700 disabled:opacity-60" :disabled="gerando" @click="acao('compartilhar')">
          <Share2 :size="19" />{{ gerando ? 'Gerando PDF...' : 'Compartilhar PDF' }}
        </button>
      </div>
    </template>
  </BottomSheet>
</template>
