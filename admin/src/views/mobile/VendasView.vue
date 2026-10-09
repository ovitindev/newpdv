<script setup>
import { computed, onMounted, ref } from 'vue'
import { Search, ShoppingBag, MessageCircle, Truck } from '@lucide/vue'
import { getVendas, getVenda } from '@/services/vendasService'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { formatCurrency, localISODate } from '@/utils/format'
import { whatsappLink } from '@/utils/whatsapp'
import BottomSheet from '@/components/mobile/BottomSheet.vue'

const auth = useAuthStore()
const toast = useToastStore()

const periodos = [
  { id: 'hoje', label: 'Hoje', dias: 0 },
  { id: '7', label: '7 dias', dias: 6 },
  { id: '30', label: '30 dias', dias: 29 },
]
const periodo = ref('hoje')
const vendas = ref([])
const loading = ref(true)
const busca = ref('')

let requisicao = 0
async function carregar() {
  const minha = ++requisicao
  loading.value = true
  const ini = new Date()
  ini.setDate(ini.getDate() - periodos.find((p) => p.id === periodo.value).dias)
  try {
    const lista = await getVendas({ dataInicio: localISODate(ini), dataFim: localISODate(new Date()) })
    if (minha === requisicao) vendas.value = lista
  } catch (e) {
    toast.error('Não foi possível carregar as vendas', e.message)
  } finally {
    if (minha === requisicao) loading.value = false
  }
}

function trocar(id) {
  periodo.value = id
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

const detalheAberto = ref(false)
const detalhe = ref(null)
const carregandoDetalhe = ref(false)
const metodoLabel = { pix: 'PIX', dinheiro: 'Dinheiro', debito: 'Débito', credito: 'Crédito' }

async function abrir(v) {
  detalhe.value = null
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

function recibo(v) {
  const linhas = v.itensDetalhe.map((i) => `• ${i.quantidade}x ${i.nome} — ${formatCurrency(i.preco * i.quantidade)}`)
  return [`*${auth.empresa?.nome_fantasia ?? 'NovaPDV'}*`, `Recibo da venda #${v.id}`, '', ...linhas, '', `*Total: ${formatCurrency(v.total)}*`, '', 'Obrigada pela preferência!'].join('\n')
}
</script>

<template>
  <div class="space-y-4 px-4 pb-8 pt-4">
    <div class="flex rounded-2xl bg-white p-1 shadow-soft">
      <button v-for="p in periodos" :key="p.id" class="h-11 flex-1 rounded-xl text-sm font-medium" :class="periodo === p.id ? 'bg-brand-600 text-white shadow-soft' : 'text-ink-soft'" @click="trocar(p.id)">{{ p.label }}</button>
    </div>

    <div class="relative">
      <Search :size="19" class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-faint" />
      <input v-model="busca" type="search" placeholder="Buscar por cliente ou nº da venda" class="h-[52px] w-full rounded-2xl border border-border bg-white pl-12 pr-4 text-base shadow-soft outline-none focus:border-brand-400" />
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
              <span class="text-xs text-ink-faint">#{{ v.id }} · {{ hora(v.data) }} · {{ v.pagamento }}</span>
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
        <a :href="whatsappLink('', recibo(detalhe))" target="_blank" rel="noopener" class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] font-semibold text-white active:opacity-90"><MessageCircle :size="20" />Enviar recibo no WhatsApp</a>
      </div>
    </BottomSheet>
  </div>
</template>
