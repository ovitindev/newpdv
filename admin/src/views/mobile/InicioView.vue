<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { TrendingUp, Receipt, ShoppingBag, ChevronRight, PackageCheck, Hourglass } from '@lucide/vue'
import { getVendas } from '@/services/vendasService'
import { getPedidosCompra } from '@/services/pedidosCompraService'
import { useAuthStore } from '@/stores/auth'
import { formatCurrency, localISODate } from '@/utils/format'
import GanhosCard from '@/components/mobile/GanhosCard.vue'

const router = useRouter()
const auth = useAuthStore()

const periodos = [
  { id: 'hoje', label: 'Hoje' },
  { id: 'semana', label: 'Semana' },
  { id: 'mes', label: 'Mês' },
]
const periodo = ref('hoje')
const vendas = ref([])
const pedidos = ref([])
const loading = ref(true)

function intervalo(id) {
  const hoje = new Date()
  const ini = new Date(hoje)
  if (id === 'semana') {
    // Semana de domingo a sábado (getDay(): domingo = 0), igual ao cálculo da comissão.
    ini.setDate(ini.getDate() - ini.getDay())
  } else if (id === 'mes') {
    ini.setDate(1)
  }
  return { dataInicio: localISODate(ini), dataFim: localISODate(hoje) }
}

let requisicao = 0
async function carregar() {
  const minha = ++requisicao
  loading.value = true
  try {
    const lista = await getVendas(intervalo(periodo.value))
    if (minha === requisicao) vendas.value = lista
  } finally {
    if (minha === requisicao) loading.value = false
  }
}

function trocar(id) {
  periodo.value = id
  carregar()
}

onMounted(() => {
  carregar()
  getPedidosCompra().then((l) => (pedidos.value = l)).catch(() => {})
})

const concluidas = computed(() => vendas.value.filter((v) => v.status === 'concluida'))
const total = computed(() => concluidas.value.reduce((s, v) => s + v.total, 0))
const ticket = computed(() => (concluidas.value.length ? total.value / concluidas.value.length : 0))

// Barras por dia (semana e mês); no "hoje" não faz sentido.
const barras = computed(() => {
  if (periodo.value === 'hoje') return []
  const { dataInicio, dataFim } = intervalo(periodo.value)
  const porDia = new Map()
  for (const v of concluidas.value) {
    const dia = localISODate(new Date(v.data))
    porDia.set(dia, (porDia.get(dia) ?? 0) + v.total)
  }
  const dias = []
  for (let d = new Date(`${dataInicio}T12:00:00`); localISODate(d) <= dataFim; d.setDate(d.getDate() + 1)) {
    const iso = localISODate(d)
    dias.push({ iso, dia: d.getDate(), sem: d.toLocaleDateString('pt-BR', { weekday: 'narrow' }), valor: porDia.get(iso) ?? 0 })
  }
  const max = Math.max(...dias.map((d) => d.valor), 1)
  return dias.map((d) => ({ ...d, pct: Math.max((d.valor / max) * 100, d.valor ? 6 : 2) }))
})

const recentes = computed(() => vendas.value.slice(0, 4))
const pendentes = computed(() => pedidos.value.filter((p) => p.status === 'pendente').length)
const disponiveis = computed(() => pedidos.value.filter((p) => p.status === 'disponivel').length)

const hora = (d) => new Date(d).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
</script>

<template>
  <div class="space-y-5 px-4 pb-8 pt-4">
    <div class="flex rounded-2xl bg-white p-1 shadow-soft">
      <button
        v-for="p in periodos"
        :key="p.id"
        class="h-11 flex-1 rounded-xl text-sm font-medium transition-colors"
        :class="periodo === p.id ? 'bg-brand-600 text-white shadow-soft' : 'text-ink-soft'"
        @click="trocar(p.id)"
      >
        {{ p.label }}
      </button>
    </div>

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 to-brand-950 p-5 text-white shadow-soft-lg">
      <div class="pointer-events-none absolute -right-8 -top-10 size-40 rounded-full bg-brand-300/20 blur-2xl" />
      <p class="relative text-sm text-brand-100">Total vendido</p>
      <p v-if="loading" class="relative mt-1 h-10 w-44 animate-pulse rounded-lg bg-white/20" />
      <p v-else class="relative mt-1 text-4xl font-semibold tracking-tight">{{ formatCurrency(total) }}</p>
      <p class="relative mt-1 text-sm text-brand-100">{{ concluidas.length }} {{ concluidas.length === 1 ? 'venda' : 'vendas' }}</p>
    </section>

    <div class="grid grid-cols-2 gap-3">
      <div class="rounded-2xl bg-white p-4 shadow-soft">
        <span class="flex size-9 items-center justify-center rounded-xl bg-info-soft text-info"><Receipt :size="18" /></span>
        <p class="mt-3 text-xs text-ink-soft">Ticket médio</p>
        <p class="text-lg font-semibold text-ink">{{ formatCurrency(ticket) }}</p>
      </div>
      <div class="rounded-2xl bg-white p-4 shadow-soft">
        <span class="flex size-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><TrendingUp :size="18" /></span>
        <p class="mt-3 text-xs text-ink-soft">Vendas</p>
        <p class="text-lg font-semibold text-ink">{{ concluidas.length }}</p>
      </div>
    </div>

    <GanhosCard v-if="auth.ehVendedor" />

    <section v-if="barras.length" class="rounded-3xl bg-white p-4 shadow-soft">
      <h2 class="mb-3 text-sm font-semibold text-ink">Vendas por dia</h2>
      <div class="flex h-32 items-end gap-1">
        <div v-for="b in barras" :key="b.iso" class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1">
          <div class="w-full rounded-t-md" :class="b.valor ? 'bg-gradient-to-t from-brand-600 to-brand-400' : 'bg-surface-alt'" :style="{ height: `${b.pct}%` }" :title="formatCurrency(b.valor)" />
          <span class="text-[10px] leading-none text-ink-faint">{{ barras.length > 10 ? (b.dia % 5 === 0 || b.dia === 1 ? b.dia : '') : b.sem }}</span>
        </div>
      </div>
    </section>

    <div class="grid grid-cols-2 gap-3">
      <button class="flex items-center gap-3 rounded-2xl bg-white p-4 text-left shadow-soft active:bg-surface" @click="router.push('/app/pedidos')">
        <span class="flex size-10 items-center justify-center rounded-xl bg-warning-soft text-warning"><Hourglass :size="20" /></span>
        <span><span class="block text-xl font-semibold text-ink">{{ pendentes }}</span><span class="text-xs text-ink-soft">pedidos a comprar</span></span>
      </button>
      <button class="flex items-center gap-3 rounded-2xl bg-white p-4 text-left shadow-soft active:bg-surface" @click="router.push('/app/pedidos')">
        <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><PackageCheck :size="20" /></span>
        <span><span class="block text-xl font-semibold text-ink">{{ disponiveis }}</span><span class="text-xs text-ink-soft">prontos p/ entregar</span></span>
      </button>
    </div>

    <section>
      <div class="mb-2 flex items-center justify-between px-1">
        <h2 class="text-sm font-semibold text-ink">Últimas vendas</h2>
        <router-link to="/app/vendas" class="text-sm font-medium text-brand-700">Ver todas</router-link>
      </div>
      <ul v-if="recentes.length" class="divide-y divide-border overflow-hidden rounded-2xl bg-white shadow-soft">
        <li v-for="v in recentes" :key="v.id" class="flex items-center gap-3 px-4 py-3">
          <span class="flex size-10 items-center justify-center rounded-full bg-brand-50 text-brand-600"><ShoppingBag :size="18" /></span>
          <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-ink">{{ v.cliente }}</p>
            <p class="text-xs text-ink-faint">{{ hora(v.data) }} · {{ v.pagamento }}</p>
          </div>
          <span class="font-semibold" :class="v.status === 'cancelada' ? 'text-ink-faint line-through' : 'text-ink'">{{ formatCurrency(v.total) }}</span>
        </li>
      </ul>
      <div v-else-if="!loading" class="rounded-2xl bg-white p-8 text-center shadow-soft">
        <p class="text-sm text-ink-soft">Nenhuma venda neste período.</p>
        <router-link to="/app/vender" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">Fazer uma venda <ChevronRight :size="16" /></router-link>
      </div>
    </section>
  </div>
</template>
