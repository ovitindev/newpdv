<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { QrCode, Banknote, CreditCard, Landmark, Trash2, CheckCircle2 } from '@lucide/vue'
import { useCartStore } from '@/stores/cart'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Select from '@/components/ui/Select.vue'
import MoneyInput from '@/components/ui/MoneyInput.vue'
import { formatCurrency } from '@/utils/format'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'confirmed'])

const cart = useCartStore()

const methods = [
  { value: 'pix', label: 'PIX', icon: QrCode },
  { value: 'dinheiro', label: 'Dinheiro', icon: Banknote },
  { value: 'debito', label: 'Débito', icon: Landmark },
  { value: 'credito', label: 'Crédito', icon: CreditCard },
]

const methodLabel = Object.fromEntries(methods.map((m) => [m.value, m.label]))
const parcelaOptions = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: `${i + 1}x${i === 0 ? ' à vista' : ''}` }))

const selectedMethod = ref('pix')
const valor = ref(0)
const parcelas = ref(1)
const valorInputRef = ref(null)

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      valor.value = cart.restante
      selectedMethod.value = 'pix'
      parcelas.value = 1
      await nextTick()
      valorInputRef.value?.focus()
    }
  },
)

watch(
  () => cart.restante,
  (restante) => {
    if (props.modelValue) valor.value = restante
  },
)

function addPayment() {
  if (!valor.value || valor.value <= 0) return
  cart.addPayment({ method: selectedMethod.value, valor: Math.min(Number(valor.value), cart.restante || Number(valor.value)), parcelas: parcelas.value })
}

function close() {
  cart.clearPayments()
  emit('update:modelValue', false)
}

// Caso comum (pagamento único cobrindo o total): não obriga clicar em
// "Adicionar pagamento" antes — confirmar já registra e finaliza numa tacada.
function confirm() {
  if (props.loading) return
  if (cart.restante > 0) {
    if (!valor.value || Number(valor.value) < cart.restante) return
    addPayment()
  }
  if (cart.payments.length > 0 && cart.restante === 0) {
    emit('confirmed')
  }
}

const cobreRestante = computed(() => cart.restante > 0 && Number(valor.value) >= cart.restante)
const podeConfirmar = computed(() => (cart.restante === 0 ? cart.payments.length > 0 : cobreRestante.value))
</script>

<template>
  <Modal :model-value="modelValue" title="Pagamento" size="md" @update:model-value="close">
    <div class="space-y-5">
      <div class="rounded-xl bg-surface p-4 flex items-center justify-between">
        <div>
          <p class="text-xs text-ink-soft">Total da venda</p>
          <p class="text-xl font-bold text-ink">{{ formatCurrency(cart.total) }}</p>
        </div>
        <div class="text-right">
          <p class="text-xs text-ink-soft">Restante</p>
          <p class="text-xl font-bold" :class="cart.restante > 0 ? 'text-danger' : 'text-brand-700'">{{ formatCurrency(cart.restante) }}</p>
        </div>
      </div>

      <ul v-if="cart.payments.length" class="space-y-2">
        <li v-for="payment in cart.payments" :key="payment.id" class="flex items-center justify-between rounded-lg border border-border px-3 py-2.5">
          <div>
            <p class="text-sm font-medium text-ink">
              {{ methodLabel[payment.method] }}
              <span v-if="payment.method === 'credito' && payment.parcelas > 1" class="text-ink-faint font-normal">· {{ payment.parcelas }}x</span>
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-ink">{{ formatCurrency(payment.valor) }}</span>
            <button class="text-ink-faint hover:text-danger" @click="cart.removePayment(payment.id)"><Trash2 :size="14" /></button>
          </div>
        </li>
      </ul>

      <div v-if="cart.restante > 0" class="space-y-3 rounded-xl border border-border p-4">
        <p class="text-xs font-medium text-ink-soft">Adicionar forma de pagamento</p>
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="method in methods"
            :key="method.value"
            type="button"
            class="flex flex-col items-center gap-1 rounded-xl border py-2.5 text-[11px] font-medium transition-colors"
            :class="selectedMethod === method.value ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-border text-ink-soft hover:border-brand-300'"
            @click="selectedMethod = method.value"
          >
            <component :is="method.icon" :size="17" />
            {{ method.label }}
          </button>
        </div>

        <div class="grid gap-3" :class="selectedMethod === 'credito' ? 'grid-cols-2' : 'grid-cols-1'">
          <MoneyInput ref="valorInputRef" v-model="valor" label="Valor" @keyup.enter="confirm" />
          <Select v-if="selectedMethod === 'credito'" v-model="parcelas" label="Parcelas" :options="parcelaOptions" />
        </div>

        <Button v-if="!cobreRestante" variant="outline" size="sm" block @click="addPayment">Adicionar pagamento</Button>
      </div>

      <div v-else class="flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">
        <CheckCircle2 :size="16" /> Valor total coberto pelas formas de pagamento adicionadas.
      </div>
    </div>

    <template #footer>
      <Button variant="ghost" :disabled="loading" @click="close">Cancelar</Button>
      <Button :disabled="!podeConfirmar" :loading="loading" @click="confirm">Confirmar Pagamento</Button>
    </template>
  </Modal>
</template>
