<script setup>
import { ref, watch } from 'vue'
import { CheckCircle2, FileText, Receipt, Printer, Download, Loader2, ArrowLeft } from '@lucide/vue'
import { useEmpresaStore } from '@/stores/empresa'
import { useToastStore } from '@/stores/toast'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import { formatCurrency, formatDateTime } from '@/utils/format'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  venda: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'finish'])

const empresaStore = useEmpresaStore()
const toast = useToastStore()

const step = ref('resumo') // resumo | emitindo-nota | nota-emitida | recibo
const chaveAcesso = ref('')

const methodLabel = { pix: 'PIX', dinheiro: 'Dinheiro', debito: 'Débito', credito: 'Crédito' }

watch(
  () => props.modelValue,
  (open) => {
    if (open) step.value = 'resumo'
  },
)

function emitirNota() {
  step.value = 'emitindo-nota'
  setTimeout(() => {
    chaveAcesso.value = Array.from({ length: 44 }, () => Math.floor(Math.random() * 10)).join('')
    step.value = 'nota-emitida'
    toast.success(`NFC-e #${props.venda.id} autorizada`, 'Nota emitida com sucesso pela SEFAZ.')
  }, 1400)
}

function emitirRecibo() {
  step.value = 'recibo'
}

function imprimir() {
  window.print()
}

function baixarXml() {
  toast.info('Download simulado', 'Em produção, o XML da nota seria baixado aqui.')
}

function finish() {
  emit('update:modelValue', false)
  emit('finish')
}
</script>

<template>
  <Modal :model-value="modelValue" size="md" @update:model-value="finish">
    <template #header>
      <div class="flex items-center gap-2">
        <button v-if="step !== 'resumo' && step !== 'emitindo-nota'" class="text-ink-faint hover:text-ink" @click="step = 'resumo'">
          <ArrowLeft :size="16" />
        </button>
        <h3 class="text-base font-semibold text-ink">
          {{ step === 'resumo' ? `Venda #${venda?.id} concluída` : step === 'recibo' ? 'Recibo (sem valor fiscal)' : 'Nota Fiscal (NFC-e)' }}
        </h3>
      </div>
    </template>

    <!-- Resumo -->
    <div v-if="step === 'resumo' && venda" class="space-y-5">
      <div class="flex flex-col items-center gap-2 py-2 text-center">
        <span class="flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-600"><CheckCircle2 :size="28" /></span>
        <p class="text-2xl font-bold text-ink">{{ formatCurrency(venda.total) }}</p>
        <p class="text-sm text-ink-soft">{{ formatDateTime(venda.data) }}</p>
      </div>

      <dl class="space-y-2 text-sm rounded-xl bg-surface p-4">
        <div class="flex justify-between"><dt class="text-ink-soft">Vendedor</dt><dd class="font-medium text-ink">{{ venda.vendedor?.nome ?? 'Não informado' }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Cliente</dt><dd class="font-medium text-ink">{{ venda.cliente?.nome ?? 'Consumidor Final' }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-soft">Itens</dt><dd class="font-medium text-ink">{{ venda.itens.length }}</dd></div>
        <div class="flex justify-between">
          <dt class="text-ink-soft">Pagamento</dt>
          <dd class="font-medium text-ink text-right">
            <span v-for="payment in venda.payments" :key="payment.id" class="block">
              {{ methodLabel[payment.method] }}{{ payment.method === 'credito' && payment.parcelas > 1 ? ` ${payment.parcelas}x` : '' }} · {{ formatCurrency(payment.valor) }}
            </span>
          </dd>
        </div>
      </dl>

      <p class="text-xs text-ink-faint text-center">Escolha como deseja formalizar esta venda para o cliente.</p>

      <div class="grid grid-cols-2 gap-3">
        <button
          type="button"
          class="flex flex-col items-center gap-2 rounded-xl border border-border p-4 text-center transition-colors hover:border-brand-300 hover:bg-brand-50"
          @click="emitirNota"
        >
          <FileText :size="22" class="text-brand-600" />
          <span class="text-sm font-medium text-ink">Emitir Nota Fiscal</span>
          <span class="text-[11px] text-ink-faint">NFC-e com valor fiscal</span>
        </button>
        <button
          type="button"
          class="flex flex-col items-center gap-2 rounded-xl border border-border p-4 text-center transition-colors hover:border-brand-300 hover:bg-brand-50"
          @click="emitirRecibo"
        >
          <Receipt :size="22" class="text-brand-600" />
          <span class="text-sm font-medium text-ink">Emitir Recibo</span>
          <span class="text-[11px] text-ink-faint">Sem valor fiscal</span>
        </button>
      </div>
    </div>

    <!-- Emitindo nota -->
    <div v-else-if="step === 'emitindo-nota'" class="flex flex-col items-center justify-center gap-3 py-14">
      <Loader2 :size="28" class="animate-spin text-brand-600" />
      <p class="text-sm font-medium text-ink">Emitindo NFC-e junto à SEFAZ...</p>
    </div>

    <!-- Nota emitida -->
    <div v-else-if="step === 'nota-emitida' && venda" class="print-area space-y-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <img v-if="empresaStore.logoUrl" :src="empresaStore.logoUrl" alt="Logo" class="h-8 w-auto" />
          <span class="text-sm font-semibold text-ink">{{ empresaStore.dados.nomeFantasia }}</span>
        </div>
        <Badge variant="success" dot>Autorizada</Badge>
      </div>
      <div class="rounded-xl bg-surface p-4 space-y-2 text-sm">
        <div class="flex justify-between"><span class="text-ink-soft">Número</span><span class="font-mono text-ink">000001{{ venda.id }}</span></div>
        <div class="flex justify-between"><span class="text-ink-soft">Série</span><span class="text-ink">1</span></div>
        <div class="flex justify-between"><span class="text-ink-soft">Valor</span><span class="font-semibold text-ink">{{ formatCurrency(venda.total) }}</span></div>
        <div class="pt-2 border-t border-border">
          <p class="text-ink-soft text-xs mb-1">Chave de acesso</p>
          <p class="font-mono text-[11px] text-ink break-all leading-relaxed">{{ chaveAcesso }}</p>
        </div>
      </div>
      <p class="text-[11px] text-ink-faint text-center">Documento fiscal emitido em ambiente de demonstração.</p>
    </div>

    <!-- Recibo -->
    <div v-else-if="step === 'recibo' && venda" class="print-area space-y-4">
      <div class="flex flex-col items-center gap-1.5 text-center">
        <img v-if="empresaStore.logoUrl" :src="empresaStore.logoUrl" alt="Logo" class="h-10 w-auto" />
        <p class="text-sm font-semibold text-ink">{{ empresaStore.dados.nomeFantasia }}</p>
        <p class="text-xs text-ink-faint">{{ empresaStore.dados.cnpj }}</p>
      </div>
      <div class="rounded-xl border border-dashed border-border p-4 space-y-2 text-sm">
        <div v-for="item in venda.itens" :key="item.id" class="flex justify-between">
          <span class="text-ink-soft">{{ item.quantidade }}x {{ item.nome }}</span>
          <span class="text-ink">{{ formatCurrency(item.preco * item.quantidade) }}</span>
        </div>
        <div class="pt-2 border-t border-border flex justify-between font-bold text-ink">
          <span>Total</span><span>{{ formatCurrency(venda.total) }}</span>
        </div>
      </div>
      <p class="text-[11px] text-ink-faint text-center uppercase tracking-wide">Este documento não possui valor fiscal</p>
    </div>

    <template #footer>
      <template v-if="step === 'nota-emitida'">
        <Button variant="outline" @click="baixarXml"><template #icon-left><Download :size="14" /></template>Baixar XML</Button>
        <Button variant="outline" @click="imprimir"><template #icon-left><Printer :size="14" /></template>Imprimir</Button>
        <Button @click="finish">Concluir</Button>
      </template>
      <template v-else-if="step === 'recibo'">
        <Button variant="outline" @click="imprimir"><template #icon-left><Printer :size="14" /></template>Imprimir</Button>
        <Button @click="finish">Concluir</Button>
      </template>
      <template v-else-if="step === 'resumo'">
        <Button variant="ghost" @click="finish">Concluir sem emitir</Button>
      </template>
    </template>
  </Modal>
</template>
