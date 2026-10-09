<script setup>
import { computed, onMounted, ref } from 'vue'
import { Wallet, Fuel, Trophy, Percent, ChevronDown } from '@lucide/vue'
import { getComissao } from '@/services/vendasService'
import { formatCurrency, localISODate } from '@/utils/format'

const opcoes = [
  { id: 'semana', label: 'Esta semana' },
  { id: 'passada', label: 'Semana passada' },
  { id: 'mes', label: 'Este mês' },
]
const opcao = ref('semana')
const dados = ref(null)
const loading = ref(true)
const erro = ref(false)
const detalhe = ref(false)

let requisicao = 0
async function carregar() {
  const minha = ++requisicao
  loading.value = true
  erro.value = false
  const ref = new Date()
  if (opcao.value === 'passada') ref.setDate(ref.getDate() - 7)
  try {
    const r = await getComissao({ tipo: opcao.value === 'mes' ? 'mes' : 'semana', ref: localISODate(ref) })
    if (minha === requisicao) dados.value = r
  } catch {
    if (minha === requisicao) erro.value = true
  } finally {
    if (minha === requisicao) loading.value = false
  }
}

function trocar(id) {
  opcao.value = id
  carregar()
}
onMounted(carregar)

const t = computed(() => dados.value?.totais)
const regras = computed(() => dados.value?.regras)
const temMeta = computed(() => regras.value?.metaSemanal > 0)
// A barra de meta só faz sentido olhando uma semana.
const semanaUnica = computed(() => dados.value?.semanas.length === 1 ? dados.value.semanas[0] : null)
const progresso = computed(() => (semanaUnica.value && temMeta.value ? Math.min((semanaUnica.value.vendas / regras.value.metaSemanal) * 100, 100) : 0))

const dia = (iso) => new Date(`${iso}T12:00:00`).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
</script>

<template>
  <section class="overflow-hidden rounded-3xl bg-white shadow-soft">
    <div class="flex items-center gap-2 px-4 pt-4">
      <span class="flex size-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><Wallet :size="18" /></span>
      <h2 class="text-sm font-semibold text-ink">Quanto vou receber</h2>
    </div>

    <div class="flex gap-1.5 overflow-x-auto px-4 pt-3 [scrollbar-width:none]">
      <button v-for="o in opcoes" :key="o.id" class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-medium" :class="opcao === o.id ? 'bg-brand-600 text-white' : 'bg-surface text-ink-soft'" @click="trocar(o.id)">{{ o.label }}</button>
    </div>

    <div v-if="loading" class="space-y-2 p-4"><div class="h-10 w-40 animate-pulse rounded-lg bg-surface" /><div class="h-4 w-full animate-pulse rounded bg-surface" /></div>
    <p v-else-if="erro" class="p-4 text-sm text-ink-soft">Não foi possível calcular agora. Tente de novo em instantes.</p>

    <div v-else-if="dados" class="p-4">
      <p class="text-3xl font-semibold tracking-tight text-ink">{{ formatCurrency(t.total) }}</p>
      <p v-if="semanaUnica" class="mt-0.5 text-xs text-ink-faint">{{ dia(semanaUnica.inicio) }} a {{ dia(semanaUnica.fim) }}<template v-if="semanaUnica.emAndamento"> · semana em andamento</template></p>
      <p v-else class="mt-0.5 text-xs text-ink-faint">{{ dados.semanas.length }} {{ dados.semanas.length === 1 ? 'semana' : 'semanas' }} no mês</p>

      <ul class="mt-4 space-y-2.5 text-sm">
        <li class="flex items-center gap-3">
          <span class="flex size-8 items-center justify-center rounded-lg bg-info-soft text-info"><Percent :size="15" /></span>
          <span class="flex-1 text-ink-soft">Comissão {{ regras.percentual }}% <span class="text-ink-faint">de {{ formatCurrency(t.vendas) }}</span></span>
          <span class="font-semibold text-ink">{{ formatCurrency(t.comissao) }}</span>
        </li>
        <li v-if="regras.valeSemanal > 0" class="flex items-center gap-3">
          <span class="flex size-8 items-center justify-center rounded-lg bg-warning-soft text-warning"><Fuel :size="15" /></span>
          <span class="flex-1 text-ink-soft">Vale semanal</span>
          <span class="font-semibold text-ink">{{ formatCurrency(t.vale) }}</span>
        </li>
        <li v-if="temMeta" class="flex items-center gap-3">
          <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><Trophy :size="15" /></span>
          <span class="flex-1 text-ink-soft">Bônus da meta</span>
          <span class="font-semibold" :class="t.bonus > 0 ? 'text-brand-700' : 'text-ink-faint'">{{ formatCurrency(t.bonus) }}</span>
        </li>
      </ul>

      <div v-if="semanaUnica && temMeta" class="mt-4 rounded-2xl bg-surface p-3.5">
        <div class="mb-2 flex items-baseline justify-between text-xs">
          <span class="font-medium text-ink">Meta da semana</span>
          <span class="text-ink-faint">{{ formatCurrency(semanaUnica.vendas) }} / {{ formatCurrency(regras.metaSemanal) }}</span>
        </div>
        <div class="h-2.5 overflow-hidden rounded-full bg-white">
          <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-300 transition-all duration-500" :style="{ width: `${progresso}%` }" />
        </div>
        <p class="mt-2 text-xs" :class="semanaUnica.metaAtingida ? 'font-semibold text-brand-700' : 'text-ink-soft'">
          <template v-if="semanaUnica.metaAtingida">Meta batida! Bônus de {{ formatCurrency(regras.bonusMeta) }} garantido.</template>
          <template v-else>Faltam {{ formatCurrency(semanaUnica.faltaParaMeta) }} para ganhar {{ formatCurrency(regras.bonusMeta) }} de bônus.</template>
        </p>
      </div>

      <template v-if="!semanaUnica && dados.semanas.length">
        <button class="mt-3 flex w-full items-center justify-center gap-1 text-xs font-medium text-brand-700" @click="detalhe = !detalhe">
          {{ detalhe ? 'Esconder' : 'Ver' }} por semana<ChevronDown :size="14" :class="detalhe ? 'rotate-180' : ''" />
        </button>
        <ul v-if="detalhe" class="mt-2 divide-y divide-border rounded-2xl border border-border text-xs">
          <li v-for="s in dados.semanas" :key="s.inicio" class="flex items-center gap-2 px-3 py-2.5">
            <span class="flex-1"><span class="font-medium text-ink">{{ dia(s.inicio) }} a {{ dia(s.fim) }}</span><span class="block text-ink-faint">{{ formatCurrency(s.vendas) }} em vendas<template v-if="s.metaAtingida"> · meta batida</template></span></span>
            <span class="font-semibold text-ink">{{ formatCurrency(s.total) }}</span>
          </li>
        </ul>
      </template>
    </div>
  </section>
</template>
