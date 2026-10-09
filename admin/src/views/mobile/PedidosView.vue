<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, Package, MessageCircle, ArrowRight, Ban, Trash2, Search } from '@lucide/vue'
import { getPedidosCompra, createPedidoCompra, updatePedidoCompra, deletePedidoCompra } from '@/services/pedidosCompraService'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { formatDateTime } from '@/utils/format'
import { whatsappLink } from '@/utils/whatsapp'
import BottomSheet from '@/components/mobile/BottomSheet.vue'
import ClientePicker from '@/components/mobile/ClientePicker.vue'

const auth = useAuthStore()
const toast = useToastStore()

const pedidos = ref([])
const loading = ref(true)
const busca = ref('')
const filtro = ref('ativos')

const statusLabel = { pendente: 'Pendente', comprado: 'Comprado', disponivel: 'Pode entregar', concluido: 'Entregue', cancelado: 'Cancelado' }
const statusCor = {
  pendente: 'bg-warning-soft text-warning',
  comprado: 'bg-info-soft text-info',
  disponivel: 'bg-brand-50 text-brand-700',
  concluido: 'bg-surface text-ink-soft',
  cancelado: 'bg-danger-soft text-danger',
}
const proximo = { pendente: 'comprado', comprado: 'disponivel', disponivel: 'concluido' }
const proximoLabel = { pendente: 'Marcar como comprado', comprado: 'Chegou — pode entregar', disponivel: 'Marcar como entregue' }

const filtros = [
  { id: 'ativos', label: 'Em aberto' },
  { id: 'pendente', label: 'Pendentes' },
  { id: 'disponivel', label: 'Prontos' },
  { id: 'concluido', label: 'Entregues' },
  { id: 'todos', label: 'Todos' },
]

onMounted(async () => {
  try {
    pedidos.value = await getPedidosCompra()
  } catch (e) {
    toast.error('Não foi possível carregar os pedidos', e.message)
  } finally {
    loading.value = false
  }
})

const nomeCliente = (p) => p.cliente?.nome ?? p.cliente_nome
const telefoneCliente = (p) => p.cliente?.telefone ?? p.cliente_contato

const aberto = (p) => !['concluido', 'cancelado'].includes(p.status)
const contagem = computed(() => ({
  ativos: pedidos.value.filter(aberto).length,
  pendente: pedidos.value.filter((p) => p.status === 'pendente').length,
  disponivel: pedidos.value.filter((p) => p.status === 'disponivel').length,
}))

const lista = computed(() => {
  const q = busca.value.trim().toLowerCase()
  return pedidos.value.filter((p) => {
    const okFiltro = filtro.value === 'todos' || (filtro.value === 'ativos' ? aberto(p) : p.status === filtro.value)
    return okFiltro && (!q || `${nomeCliente(p) ?? ''} ${p.descricao}`.toLowerCase().includes(q))
  })
})

async function avancar(p) {
  const novo = proximo[p.status]
  if (!novo) return
  try {
    Object.assign(p, await updatePedidoCompra(p.id, { status: novo }))
    toast.success('Status atualizado', statusLabel[novo])
  } catch (e) {
    toast.error('Não foi possível atualizar', e.message)
  }
}

function avisoCliente(p) {
  return `Olá${nomeCliente(p) ? `, ${nomeCliente(p).split(' ')[0]}` : ''}! Seu pedido (${p.descricao}) já chegou e está disponível para retirada na ${auth.empresa?.nome_fantasia ?? 'loja'}.`
}

// Formulário
const sheet = ref(false)
const editando = ref(null)
const salvando = ref(false)
const form = ref(vazio())

function vazio() {
  return { interno: false, cliente: null, descricao: '', quantidade: 1, observacoes: '', status: 'pendente' }
}

function abrirNovo() {
  editando.value = null
  form.value = vazio()
  sheet.value = true
}

function abrirEdicao(p) {
  editando.value = p
  form.value = {
    interno: !p.cliente_id && !p.cliente_nome,
    cliente: p.cliente ? { id: p.cliente.id, nome: p.cliente.nome, telefone: p.cliente.telefone } : null,
    descricao: p.descricao,
    quantidade: p.quantidade,
    observacoes: p.observacoes ?? '',
    status: p.status,
  }
  sheet.value = true
}

async function salvar() {
  if (!form.value.descricao.trim()) return toast.error('Descreva o que foi pedido')
  salvando.value = true
  try {
    const payload = {
      cliente_id: form.value.interno ? null : (form.value.cliente?.id ?? null),
      descricao: form.value.descricao.trim(),
      quantidade: Number(form.value.quantidade) || 1,
      observacoes: form.value.observacoes || null,
    }
    if (editando.value) {
      payload.status = form.value.status
      Object.assign(editando.value, await updatePedidoCompra(editando.value.id, payload))
      toast.success('Pedido atualizado')
    } else {
      pedidos.value.unshift(await createPedidoCompra(payload))
      toast.success('Pedido anotado')
    }
    sheet.value = false
  } catch (e) {
    toast.error('Não foi possível salvar', e.message)
  } finally {
    salvando.value = false
  }
}

async function cancelar() {
  try {
    Object.assign(editando.value, await updatePedidoCompra(editando.value.id, { status: 'cancelado' }))
    sheet.value = false
    toast.info('Pedido cancelado')
  } catch (e) {
    toast.error('Não foi possível cancelar', e.message)
  }
}

const confirmarExcluir = ref(false)
async function excluir() {
  if (!confirmarExcluir.value) {
    confirmarExcluir.value = true
    setTimeout(() => (confirmarExcluir.value = false), 3000)
    return
  }
  try {
    await deletePedidoCompra(editando.value.id)
    pedidos.value = pedidos.value.filter((p) => p.id !== editando.value.id)
    sheet.value = false
    confirmarExcluir.value = false
    toast.success('Pedido removido')
  } catch (e) {
    toast.error('Não foi possível remover', e.message)
  }
}
</script>

<template>
  <div class="space-y-3 px-4 pb-28 pt-4">
    <div class="relative">
      <Search :size="19" class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-faint" />
      <input v-model="busca" type="search" placeholder="Buscar por cliente ou produto" class="h-[52px] w-full rounded-2xl border border-border bg-white pl-12 pr-4 text-base shadow-soft outline-none focus:border-brand-400" />
    </div>

    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]">
      <button v-for="f in filtros" :key="f.id" class="shrink-0 rounded-full px-4 py-2 text-sm font-medium" :class="filtro === f.id ? 'bg-brand-600 text-white' : 'bg-white text-ink-soft shadow-soft'" @click="filtro = f.id">
        {{ f.label }}<span v-if="contagem[f.id]" class="ml-1.5 rounded-full px-1.5 text-xs" :class="filtro === f.id ? 'bg-white/25' : 'bg-surface'">{{ contagem[f.id] }}</span>
      </button>
    </div>

    <div v-if="loading" class="space-y-3"><div v-for="n in 3" :key="n" class="h-32 animate-pulse rounded-2xl bg-white" /></div>

    <article v-for="p in lista" v-else :key="p.id" class="rounded-2xl bg-white p-4 shadow-soft">
      <button class="block w-full text-left" @click="abrirEdicao(p)">
        <div class="flex items-start justify-between gap-3">
          <p class="flex min-w-0 items-center gap-1.5 font-semibold text-ink">
            <Package v-if="!nomeCliente(p)" :size="15" class="shrink-0 text-ink-faint" />
            <span class="truncate">{{ nomeCliente(p) ?? 'Necessidade interna' }}</span>
          </p>
          <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusCor[p.status]">{{ statusLabel[p.status] }}</span>
        </div>
        <p class="mt-1.5 text-sm leading-snug text-ink-soft">{{ p.quantidade > 1 ? `${p.quantidade}x ` : '' }}{{ p.descricao }}</p>
        <p v-if="p.observacoes" class="mt-1 text-xs italic text-ink-faint">{{ p.observacoes }}</p>
        <p class="mt-2 text-xs text-ink-faint">{{ formatDateTime(p.created_at) }}</p>
      </button>
      <div v-if="proximo[p.status] || (p.status === 'disponivel' && telefoneCliente(p))" class="mt-3 flex gap-2">
        <button v-if="proximo[p.status]" class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-semibold text-white active:bg-brand-700" @click="avancar(p)">
          <ArrowRight :size="16" />{{ proximoLabel[p.status] }}
        </button>
        <a v-if="p.status === 'disponivel' && telefoneCliente(p)" :href="whatsappLink(telefoneCliente(p), avisoCliente(p))" target="_blank" rel="noopener" class="flex h-12 items-center justify-center gap-2 rounded-xl bg-[#25D366]/15 px-4 text-sm font-semibold text-[#128C7E] active:bg-[#25D366]/25">
          <MessageCircle :size="18" />Avisar
        </a>
      </div>
    </article>

    <div v-if="!loading && !lista.length" class="rounded-2xl bg-white p-10 text-center shadow-soft">
      <p class="text-sm text-ink-soft">Nenhum pedido por aqui.</p>
    </div>

    <button class="fixed right-4 z-30 flex h-14 items-center gap-2 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 px-5 font-semibold text-white shadow-soft-lg active:scale-95" style="bottom: calc(6rem + env(safe-area-inset-bottom))" @click="abrirNovo">
      <Plus :size="20" />Novo pedido
    </button>

    <BottomSheet v-model="sheet" :title="editando ? `Pedido #${editando.id}` : 'Novo pedido'" full>
      <form id="form-pedido" class="space-y-5 pt-1" @submit.prevent="salvar">
        <button type="button" class="flex w-full items-center justify-between rounded-2xl bg-surface px-4 py-3 text-left" @click="form.interno = !form.interno">
          <span><span class="block text-sm font-medium text-ink">Pedido de um cliente</span><span class="text-xs text-ink-faint">Desligado = compra sua, sem cliente</span></span>
          <span class="relative h-7 w-12 shrink-0 rounded-full transition-colors" :class="!form.interno ? 'bg-brand-600' : 'bg-border'"><span class="absolute top-0.5 size-6 rounded-full bg-white shadow transition-all" :class="!form.interno ? 'left-[22px]' : 'left-0.5'" /></span>
        </button>

        <div v-if="!form.interno">
          <ClientePicker v-model="form.cliente" />
          <p v-if="editando && !form.cliente && nomeCliente(editando)" class="mt-1.5 text-xs text-ink-faint">Anotado antes como: {{ nomeCliente(editando) }}{{ telefoneCliente(editando) ? ` · ${telefoneCliente(editando)}` : '' }}</p>
        </div>

        <label class="block"><span class="mb-1.5 block text-sm font-medium">O que foi pedido</span>
          <textarea v-model="form.descricao" rows="3" placeholder="Ex: Tênis Nike Air, tamanho 42, preto" class="w-full rounded-2xl border border-border px-4 py-3 text-base outline-none focus:border-brand-400" />
        </label>

        <div class="flex items-center justify-between rounded-2xl bg-surface px-4 py-3">
          <span class="text-sm font-medium text-ink">Quantidade</span>
          <div class="flex items-center gap-2">
            <button type="button" class="flex size-10 items-center justify-center rounded-full bg-white shadow-soft active:bg-surface-alt" @click="form.quantidade = Math.max((Number(form.quantidade) || 1) - 1, 1)">−</button>
            <span class="w-8 text-center font-semibold">{{ form.quantidade }}</span>
            <button type="button" class="flex size-10 items-center justify-center rounded-full bg-brand-600 text-white active:bg-brand-700" @click="form.quantidade = (Number(form.quantidade) || 1) + 1">+</button>
          </div>
        </div>

        <label v-if="editando" class="block"><span class="mb-1.5 block text-sm font-medium">Status</span>
          <select v-model="form.status" class="h-12 w-full rounded-2xl border border-border bg-white px-4 text-base outline-none focus:border-brand-400">
            <option v-for="(label, k) in statusLabel" :key="k" :value="k">{{ label }}</option>
          </select>
        </label>

        <label class="block"><span class="mb-1.5 block text-sm font-medium">Observações (opcional)</span>
          <textarea v-model="form.observacoes" rows="2" placeholder="Ex: deu sinal de R$ 20, prefere entrega" class="w-full rounded-2xl border border-border px-4 py-3 text-base outline-none focus:border-brand-400" />
        </label>

        <div v-if="editando" class="flex gap-2 pt-1">
          <button v-if="!['concluido', 'cancelado'].includes(editando.status)" type="button" class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-surface text-sm font-medium text-ink-soft active:bg-surface-alt" @click="cancelar"><Ban :size="16" />Cancelar pedido</button>
          <button type="button" class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl text-sm font-medium" :class="confirmarExcluir ? 'bg-danger text-white' : 'bg-danger-soft text-danger'" @click="excluir"><Trash2 :size="16" />{{ confirmarExcluir ? 'Toque p/ confirmar' : 'Excluir' }}</button>
        </div>
      </form>
      <template #footer>
        <button type="submit" form="form-pedido" :disabled="salvando" class="h-14 w-full rounded-2xl bg-brand-600 text-base font-semibold text-white active:bg-brand-700 disabled:opacity-60">{{ salvando ? 'Salvando...' : editando ? 'Salvar alterações' : 'Anotar pedido' }}</button>
      </template>
    </BottomSheet>
  </div>
</template>
