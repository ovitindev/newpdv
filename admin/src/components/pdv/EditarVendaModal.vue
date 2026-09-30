<script setup>
import { computed, ref, watch } from 'vue'
import { Truck } from '@lucide/vue'
import { getClientes } from '@/services/clientesService'
import { getVendedores } from '@/services/configuracoesService'
import { updateVenda } from '@/services/vendasService'
import { useToastStore } from '@/stores/toast'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Select from '@/components/ui/Select.vue'
import MoneyInput from '@/components/ui/MoneyInput.vue'
import Switch from '@/components/ui/Switch.vue'
import { formatCurrency } from '@/utils/format'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  venda: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const toast = useToastStore()

const clientes = ref([])
const vendedores = ref([])
const carregandoOpcoes = ref(false)
const salvando = ref(false)

const form = ref({
  clienteId: '',
  vendedorId: '',
  descontoPercent: 0,
  entrega: false,
  valorFrete: 0,
  status: 'concluida',
})

const statusOptions = [
  { value: 'concluida', label: 'Concluída' },
  { value: 'cancelada', label: 'Cancelada' },
]

watch(
  () => props.modelValue,
  async (open) => {
    if (!open || !props.venda) return
    form.value = {
      clienteId: props.venda.clienteId ?? '',
      vendedorId: props.venda.vendedorId ?? '',
      descontoPercent: props.venda.descontoPercent ?? 0,
      entrega: props.venda.entrega ?? false,
      valorFrete: props.venda.valorFrete ?? 0,
      status: props.venda.status ?? 'concluida',
    }
    if (!clientes.value.length && !vendedores.value.length) {
      carregandoOpcoes.value = true
      try {
        const [clientesData, vendedoresData] = await Promise.all([getClientes(), getVendedores()])
        clientes.value = clientesData
        vendedores.value = vendedoresData
      } catch (error) {
        toast.error('Não foi possível carregar clientes/vendedores', error.message)
      } finally {
        carregandoOpcoes.value = false
      }
    }
  },
)

const clienteOptions = computed(() => [
  { value: '', label: 'Consumidor Final' },
  ...clientes.value.map((c) => ({ value: c.id, label: c.nome })),
])
const vendedorOptions = computed(() => vendedores.value.map((v) => ({ value: v.id, label: v.nome })))

const descontoValor = computed(() => (props.venda ? props.venda.subtotal * (Number(form.value.descontoPercent) / 100) : 0))
const freteValor = computed(() => (form.value.entrega ? Number(form.value.valorFrete) || 0 : 0))
const totalRecalculado = computed(() =>
  props.venda ? Math.max(props.venda.subtotal - descontoValor.value, 0) + freteValor.value : 0,
)

function close() {
  emit('update:modelValue', false)
}

async function salvar() {
  if (!form.value.vendedorId) {
    toast.error('Selecione o vendedor', 'A venda precisa ter um vendedor responsável.')
    return
  }
  salvando.value = true
  try {
    const atualizada = await updateVenda(props.venda.id, {
      cliente_id: form.value.clienteId || null,
      vendedor_id: form.value.vendedorId,
      desconto_percent: form.value.descontoPercent,
      entrega: form.value.entrega,
      valor_frete: form.value.valorFrete,
      status: form.value.status,
    })
    toast.success('Venda atualizada', `Venda #${atualizada.id} foi salva com sucesso.`)
    emit('saved', atualizada)
    close()
  } catch (error) {
    toast.error('Não foi possível salvar a venda', error.message)
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <Modal :model-value="modelValue" :title="`Editar venda #${venda?.id}`" size="sm" @update:model-value="close">
    <div v-if="venda" class="space-y-4">
      <p class="text-xs text-ink-faint">
        Itens e pagamentos da venda não são editáveis aqui — só cliente, vendedor, desconto, entrega/frete e status.
      </p>

      <Select v-model="form.clienteId" label="Cliente" :options="clienteOptions" :disabled="carregandoOpcoes" />
      <Select v-model="form.vendedorId" label="Vendedor" :options="vendedorOptions" :disabled="carregandoOpcoes" />

      <div class="flex items-center gap-2">
        <label class="text-sm text-ink-soft">Desconto</label>
        <input
          type="number"
          min="0"
          max="100"
          :value="form.descontoPercent"
          class="h-9 w-20 rounded-lg border border-border bg-white px-2 text-center text-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400"
          @input="form.descontoPercent = Math.min(Math.max(Number($event.target.value) || 0, 0), 100)"
        />
        <span class="text-sm text-ink-soft">%</span>
      </div>

      <div class="flex items-center justify-between">
        <label class="flex items-center gap-1.5 text-sm text-ink-soft"><Truck :size="14" /> Entrega</label>
        <Switch v-model="form.entrega" />
      </div>
      <MoneyInput v-if="form.entrega" v-model="form.valorFrete" label="Valor do frete" />

      <Select v-model="form.status" label="Status" :options="statusOptions" />

      <div class="space-y-1.5 rounded-xl bg-surface p-4 text-sm">
        <div class="flex items-center justify-between text-ink-soft">
          <span>Subtotal</span>
          <span class="tabular-nums">{{ formatCurrency(venda.subtotal) }}</span>
        </div>
        <div v-if="descontoValor > 0" class="flex items-center justify-between text-danger">
          <span>Desconto</span>
          <span class="tabular-nums">- {{ formatCurrency(descontoValor) }}</span>
        </div>
        <div v-if="freteValor > 0" class="flex items-center justify-between text-ink-soft">
          <span>Frete</span>
          <span class="tabular-nums">+ {{ formatCurrency(freteValor) }}</span>
        </div>
        <div class="flex items-center justify-between border-t border-border pt-2 text-base font-bold text-ink">
          <span>Novo total</span>
          <span class="tabular-nums text-brand-700">{{ formatCurrency(totalRecalculado) }}</span>
        </div>
      </div>
    </div>
    <template #footer>
      <Button variant="ghost" :disabled="salvando" @click="close">Cancelar</Button>
      <Button :loading="salvando" @click="salvar">Salvar alterações</Button>
    </template>
  </Modal>
</template>
