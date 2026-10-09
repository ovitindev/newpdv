<script setup>
import { computed, onMounted, ref } from 'vue'
import { Search, ShoppingBag, FileText, Truck, Pencil, Ban, Trash2, RotateCcw } from '@lucide/vue'
import { getVendas, getVenda, updateVenda, deleteVenda } from '@/services/vendasService'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { formatCurrency, localISODate } from '@/utils/format'
import { reciboDeVenda } from '@/utils/recibo'
import BottomSheet from '@/components/mobile/BottomSheet.vue'
import ClientePicker from '@/components/mobile/ClientePicker.vue'
import MoneyField from '@/components/mobile/MoneyField.vue'
import ReciboSheet from '@/components/mobile/ReciboSheet.vue'

const auth = useAuthStore()
const toast = useToastStore()

const periodos = [
  { id: 'hoje', label: 'Hoje' },
  { id: 'semana', label: 'Semana' },
  { id: 'mes', label: 'Mês' },
]
const periodo = ref('hoje')
// Vendedora vê as vendas de todos, mas só mexe nas dela. "Minhas" é o padrão.
const escopo = ref(auth.ehVendedor ? 'minhas' : 'todas')
const vendas = ref([])
const loading = ref(true)
const busca = ref('')

let requisicao = 0
async function carregar() {
  const minha = ++requisicao
  loading.value = true
  // Semana de domingo a sábado e mês do dia 1 até hoje (não são "últimos N dias").
  const ini = new Date()
  if (periodo.value === 'semana') ini.setDate(ini.getDate() - ini.getDay())
  else if (periodo.value === 'mes') ini.setDate(1)
  try {
    const lista = await getVendas({
      dataInicio: localISODate(ini),
      dataFim: localISODate(new Date()),
      vendedorId: escopo.value === 'minhas' ? auth.vendedor?.id : undefined,
    })
    if (minha === requisicao) vendas.value = lista
  } catch (e) {
    toast.error('Não foi possível carregar as vendas', e.message)
  } finally {
    if (minha === requisicao) loading.value = false
  }
}

function trocarPeriodo(id) {
  periodo.value = id
  carregar()
}
function trocarEscopo(id) {
  escopo.value = id
  carregar()
}
onMounted(carregar)

const filtradas = computed(() => {
  const q = busca.value.trim().toLowerCase()
  return q ? vendas.value.filter((v) => `${v.cliente} ${v.id} ${v.vendedor ?? ''}`.toLowerCase().includes(q)) : vendas.value
})

const total = computed(() => filtradas.value.filter((v) => v.status === 'concluida').reduce((s, v) => s + v.total, 0))

const grupos = computed(() => {
  const mapa = new Map()
  for (const v of filtradas.value) {
    const dia = localISODate(new Date(v.data))
    if (!mapa.has(dia)) mapa.set(dia, [])
    mapa.get(dia).push(v)
  }
  const hoje = localISODate(new Date())
  const ontem = localISODate(new Date(Date.now() - 86400000))
  return [...mapa.entries()].map(([dia, itens]) => ({
    dia,
    titulo: dia === hoje ? 'Hoje' : dia === ontem ? 'Ontem' : new Date(`${dia}T12:00:00`).toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' }),
    soma: itens.filter((v) => v.status === 'concluida').reduce((s, v) => s + v.total, 0),
    itens,
  }))
})

const hora = (d) => new Date(d).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
const metodoLabel = { pix: 'PIX', dinheiro: 'Dinheiro', debito: 'Débito', credito: 'Crédito' }

// Detalhe
const detalheAberto = ref(false)
const detalhe = ref(null)
const carregandoDetalhe = ref(false)
const confirmarExcluir = ref(false)

// A API também barra (403); aqui só decide se mostra os botões.
const ehMinha = computed(() => !auth.ehVendedor || detalhe.value?.vendedorId === auth.vendedor?.id)

async function abrir(v) {
  detalhe.value = null
  confirmarExcluir.value = false
  detalheAberto.value = true
  carregandoDetalhe.value = true
  try {
    detalhe.value = await getVenda(v.id)
  } catch (e) {
    detalheAberto.value = false
    toast.error('Não foi possível abrir a venda', e.message)
  } finally {
    carregandoDetalhe.value = false
  }
}

// Recibo (o mesmo do PC, em PDF)
const reciboAberto = ref(false)
const recibo = ref(null)
function abrirRecibo() {
  recibo.value = reciboDeVenda(detalhe.value)
  reciboAberto.value = true
}

// Edição: cliente, desconto e frete (itens e pagamentos não mudam, igual ao painel)
const editarAberto = ref(false)
const salvando = ref(false)
const form = ref({ cliente: null, desconto: 0, entrega: false, frete: 0 })
const num = (v) => Number(String(v).replace(',', '.')) || 0

function abrirEdicao() {
  const d = detalhe.value
  form.value = {
    cliente: d.clienteId ? { id: d.clienteId, nome: d.cliente } : null,
    desconto: d.descontoPercent || 0,
    entrega: d.entrega,
    frete: d.valorFrete || 0,
  }
  editarAberto.value = true
}

const novoTotal = computed(() => {
  const d = detalhe.value
  if (!d) return 0
  return Math.max(d.subtotal - d.subtotal * (num(form.value.desconto) / 100), 0) + (form.value.entrega ? num(form.value.frete) : 0)
})

async function atualizar(payload, mensagem) {
  salvando.value = true
  try {
    detalhe.value = await updateVenda(detalhe.value.id, payload)
    const i = vendas.value.findIndex((v) => v.id === detalhe.value.id)
    if (i >= 0) vendas.value[i] = { ...vendas.value[i], ...detalhe.value }
    toast.success(mensagem)
    return true
  } catch (e) {
    toast.error('Não foi possível salvar', e.message)
    return false
  } finally {
    salvando.value = false
  }
}

// Mantém todo o cabeçalho como está; só troca o que for passado.
const cabecalho = (d, extra = {}) => ({
  cliente_id: d.clienteId ?? null,
  vendedor_id: d.vendedorId,
  desconto_percent: d.descontoPercent,
  entrega: d.entrega,
  valor_frete: d.valorFrete,
  status: d.status,
  ...extra,
})

async function salvarEdicao() {
  const d = detalhe.value
  const ok = await atualizar(
    cabecalho(d, {
      cliente_id: form.value.cliente?.id ?? null,
      desconto_percent: Math.min(num(form.value.desconto), 100),
      entrega: form.value.entrega,
      valor_frete: form.value.entrega ? num(form.value.frete) : 0,
    }),
    'Venda atualizada',
  )
  if (ok) editarAberto.value = false
}

function alternarCancelamento() {
  const cancelar = detalhe.value.status !== 'cancelada'
  return atualizar(cabecalho(detalhe.value, { status: cancelar ? 'cancelada' : 'concluida' }), cancelar ? 'Venda cancelada' : 'Venda reativada')
}

async function excluir() {
  if (!confirmarExcluir.value) {
    confirmarExcluir.value = true
    setTimeout(() => (confirmarExcluir.value = false), 3000)
    return
  }
  salvando.value = true
  try {
    await deleteVenda(detalhe.value.id)
    vendas.value = vendas.value.filter((v) => v.id !== detalhe.value.id)
    detalheAberto.value = false
    toast.success('Venda excluída')
  } catch (e) {
    toast.error('Não foi possível excluir', e.message)
  } finally {
    salvando.value = false
    confirmarExcluir.value = false
  }
}
</script>

<template>
  <div class="space-y-4 px-4 pb-8 pt-4">
    <div class="flex rounded-2xl bg-white p-1 shadow-soft">
      <button v-for="p in periodos" :key="p.id" class="h-11 flex-1 rounded-xl text-sm font-medium" :class="periodo === p.id ? 'bg-brand-600 text-white shadow-soft' : 'text-ink-soft'" @click="trocarPeriodo(p.id)">{{ p.label }}</button>
    </div>

    <div v-if="auth.ehVendedor" class="flex gap-2">
      <button class="h-10 flex-1 rounded-full text-sm font-medium" :class="escopo === 'minhas' ? 'bg-ink text-white' : 'bg-white text-ink-soft shadow-soft'" @click="trocarEscopo('minhas')">Minhas vendas</button>
      <button class="h-10 flex-1 rounded-full text-sm font-medium" :class="escopo === 'todas' ? 'bg-ink text-white' : 'bg-white text-ink-soft shadow-soft'" @click="trocarEscopo('todas')">Todas da loja</button>
    </div>

    <div class="relative">
      <Search :size="19" class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-faint" />
      <input v-model="busca" type="search" placeholder="Buscar por cliente, vendedor ou nº" class="h-[52px] w-full rounded-2xl border border-border bg-white pl-12 pr-4 text-base shadow-soft outline-none focus:border-brand-400" />
    </div>

    <p v-if="!loading" class="px-1 text-sm text-ink-soft">{{ filtradas.length }} {{ filtradas.length === 1 ? 'venda' : 'vendas' }} · <span class="font-semibold text-ink">{{ formatCurrency(total) }}</span></p>

    <div v-if="loading" class="space-y-2.5"><div v-for="n in 4" :key="n" class="h-[72px] animate-pulse rounded-2xl bg-white" /></div>

    <section v-for="g in grupos" v-else :key="g.dia">
      <div class="mb-1.5 flex items-baseline justify-between px-1">
        <h2 class="text-sm font-semibold capitalize text-ink">{{ g.titulo }}</h2>
        <span class="text-xs text-ink-faint">{{ formatCurrency(g.soma) }}</span>
      </div>
      <ul class="divide-y divide-border overflow-hidden rounded-2xl bg-white shadow-soft">
        <li v-for="v in g.itens" :key="v.id">
          <button class="flex w-full items-center gap-3 px-4 py-3.5 text-left active:bg-surface" @click="abrir(v)">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600"><ShoppingBag :size="19" /></span>
            <span class="min-w-0 flex-1">
              <span class="block truncate font-medium text-ink">{{ v.cliente }}</span>
              <span class="block truncate text-xs text-ink-faint">#{{ v.id }} · {{ hora(v.data) }} · {{ v.pagamento }}<template v-if="escopo === 'todas' && v.vendedor"> · {{ v.vendedor }}</template></span>
            </span>
            <span class="text-right">
              <span class="block font-semibold" :class="v.status === 'cancelada' ? 'text-ink-faint line-through' : 'text-ink'">{{ formatCurrency(v.total) }}</span>
              <span v-if="v.status === 'cancelada'" class="text-[11px] font-medium text-danger">Cancelada</span>
            </span>
          </button>
        </li>
      </ul>
    </section>

    <div v-if="!loading && !filtradas.length" class="rounded-2xl bg-white p-10 text-center shadow-soft">
      <p class="text-sm text-ink-soft">Nenhuma venda encontrada.</p>
    </div>

    <!-- Detalhe -->
    <BottomSheet v-model="detalheAberto" :title="detalhe ? `Venda #${detalhe.id}` : 'Venda'">
      <div v-if="carregandoDetalhe || !detalhe" class="space-y-3 py-2"><div v-for="n in 3" :key="n" class="h-12 animate-pulse rounded-xl bg-surface" /></div>
      <div v-else class="space-y-4 pb-2">
        <div class="rounded-2xl bg-surface p-4">
          <p class="text-sm text-ink-soft">{{ detalhe.cliente }}</p>
          <p class="text-3xl font-semibold text-ink">{{ formatCurrency(detalhe.total) }}</p>
          <p class="mt-1 text-xs text-ink-faint">{{ new Date(detalhe.data).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) }}<template v-if="detalhe.vendedor"> · {{ detalhe.vendedor }}</template></p>
          <p v-if="detalhe.status === 'cancelada'" class="mt-2 inline-block rounded-full bg-danger-soft px-3 py-1 text-xs font-medium text-danger">Venda cancelada</p>
        </div>
        <ul class="divide-y divide-border rounded-2xl border border-border">
          <li v-for="i in detalhe.itensDetalhe" :key="i.id" class="flex items-center gap-3 px-4 py-3">
            <span class="min-w-0 flex-1 text-sm text-ink"><span class="font-semibold">{{ i.quantidade }}x</span> {{ i.nome }}</span>
            <span class="text-sm font-medium">{{ formatCurrency(i.preco * i.quantidade) }}</span>
          </li>
          <li v-if="detalhe.entrega" class="flex items-center gap-3 px-4 py-3 text-sm text-ink-soft"><Truck :size="16" /><span class="flex-1">Frete</span>{{ formatCurrency(detalhe.valorFrete) }}</li>
        </ul>
        <dl class="space-y-1.5 text-sm">
          <div v-if="detalhe.descontoValor" class="flex justify-between text-ink-soft"><dt>Desconto ({{ detalhe.descontoPercent }}%)</dt><dd>-{{ formatCurrency(detalhe.descontoValor) }}</dd></div>
          <div v-for="p in detalhe.pagamentos" :key="p.id" class="flex justify-between text-ink-soft"><dt>{{ metodoLabel[p.metodo] }}<template v-if="p.metodo === 'credito' && p.parcelas > 1"> · {{ p.parcelas }}x</template></dt><dd class="font-medium text-ink">{{ formatCurrency(p.valor) }}</dd></div>
        </dl>

        <button class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 font-semibold text-white active:bg-brand-700" @click="abrirRecibo"><FileText :size="19" />Recibo em PDF</button>

        <div v-if="ehMinha" class="grid grid-cols-3 gap-2">
          <button class="flex h-12 items-center justify-center gap-1.5 rounded-xl bg-surface text-sm font-medium text-ink active:bg-surface-alt disabled:opacity-60" :disabled="salvando" @click="abrirEdicao"><Pencil :size="15" />Editar</button>
          <button class="flex h-12 items-center justify-center gap-1.5 rounded-xl bg-surface text-sm font-medium text-ink active:bg-surface-alt disabled:opacity-60" :disabled="salvando" @click="alternarCancelamento">
            <component :is="detalhe.status === 'cancelada' ? RotateCcw : Ban" :size="15" />{{ detalhe.status === 'cancelada' ? 'Reativar' : 'Cancelar' }}
          </button>
          <button class="flex h-12 items-center justify-center gap-1.5 rounded-xl text-sm font-medium disabled:opacity-60" :class="confirmarExcluir ? 'bg-danger text-white' : 'bg-danger-soft text-danger'" :disabled="salvando" @click="excluir"><Trash2 :size="15" />{{ confirmarExcluir ? 'Confirmar' : 'Excluir' }}</button>
        </div>
        <p v-else class="text-center text-xs text-ink-faint">Venda de outro vendedor: só ele pode alterar.</p>
      </div>
    </BottomSheet>

    <!-- Edição -->
    <BottomSheet v-model="editarAberto" :title="detalhe ? `Editar venda #${detalhe.id}` : 'Editar venda'">
      <div v-if="detalhe" class="space-y-5 pt-1">
        <ClientePicker v-model="form.cliente" />

        <div>
          <p class="mb-1.5 text-sm font-medium text-ink">Desconto</p>
          <div class="flex gap-2">
            <button v-for="d in [0, 5, 10, 15]" :key="d" class="h-11 flex-1 rounded-xl text-sm font-medium" :class="Number(form.desconto) === d ? 'bg-brand-600 text-white' : 'bg-surface text-ink-soft'" @click="form.desconto = d">{{ d ? `${d}%` : 'Sem' }}</button>
          </div>
        </div>

        <div>
          <button class="flex h-14 w-full items-center gap-3 rounded-2xl border px-4 text-left" :class="form.entrega ? 'border-brand-400 bg-brand-50' : 'border-border'" @click="form.entrega = !form.entrega">
            <Truck :size="20" :class="form.entrega ? 'text-brand-600' : 'text-ink-faint'" />
            <span class="flex-1 font-medium text-ink">Entrega / frete</span>
            <span class="relative h-7 w-12 rounded-full transition-colors" :class="form.entrega ? 'bg-brand-600' : 'bg-border'"><span class="absolute top-0.5 size-6 rounded-full bg-white shadow transition-all" :class="form.entrega ? 'left-[22px]' : 'left-0.5'" /></span>
          </button>
          <div v-if="form.entrega" class="mt-2"><MoneyField v-model="form.frete" label="Valor do frete" /></div>
        </div>

        <div class="rounded-2xl bg-surface p-4 text-sm">
          <div class="flex justify-between font-semibold text-ink"><span>Novo total</span><span>{{ formatCurrency(novoTotal) }}</span></div>
          <p v-if="Math.abs(novoTotal - detalhe.total) > 0.005" class="mt-1.5 text-xs text-warning">O total mudou, mas os pagamentos registrados continuam os mesmos.</p>
        </div>
      </div>
      <template #footer>
        <button :disabled="salvando" class="h-14 w-full rounded-2xl bg-brand-600 text-base font-semibold text-white active:bg-brand-700 disabled:opacity-60" @click="salvarEdicao">{{ salvando ? 'Salvando...' : 'Salvar alterações' }}</button>
      </template>
    </BottomSheet>

    <ReciboSheet v-model="reciboAberto" :recibo="recibo" />
  </div>
</template>
