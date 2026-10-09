<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, ArrowRight, Trash2, Pencil, UserRound, Package, Ban, X, UserPlus } from '@lucide/vue'
import {
  getPedidosCompra,
  createPedidoCompra,
  updatePedidoCompra,
  deletePedidoCompra,
} from '@/services/pedidosCompraService'
import { getClientes } from '@/services/clientesService'
import { useToastStore } from '@/stores/toast'
import { usePagination } from '@/utils/usePagination'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import Input from '@/components/ui/Input.vue'
import Textarea from '@/components/ui/Textarea.vue'
import Switch from '@/components/ui/Switch.vue'
import { formatDateTime } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const pedidos = ref([])
const search = ref('')
const statusFilter = ref('')
const clientes = ref([])

const statusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'pendente', label: 'Pendente' },
  { value: 'comprado', label: 'Comprado' },
  { value: 'disponivel', label: 'Disponível' },
  { value: 'concluido', label: 'Concluído' },
  { value: 'cancelado', label: 'Cancelado' },
]

const statusLabel = { pendente: 'Pendente', comprado: 'Comprado', disponivel: 'Disponível', concluido: 'Concluído', cancelado: 'Cancelado' }
const statusVariant = { pendente: 'warning', comprado: 'info', disponivel: 'brand', concluido: 'success', cancelado: 'danger' }
const proximoStatus = { pendente: 'comprado', comprado: 'disponivel', disponivel: 'concluido' }
const proximoStatusLabel = { pendente: 'Marcar como comprado', comprado: 'Marcar como disponível', disponivel: 'Marcar como entregue' }

async function carregar() {
  loading.value = true
  pedidos.value = await getPedidosCompra()
  loading.value = false
}

onMounted(() => {
  carregar()
  getClientes().then((lista) => (clientes.value = lista))
})

const nomeCliente = (p) => p.cliente?.nome ?? p.cliente_nome

const filtered = computed(() =>
  pedidos.value.filter((p) => {
    const alvo = `${nomeCliente(p) ?? ''} ${p.descricao}`.toLowerCase()
    const matchesSearch = !search.value || alvo.includes(search.value.toLowerCase())
    const matchesStatus = !statusFilter.value || p.status === statusFilter.value
    return matchesSearch && matchesStatus
  }),
)

const contagemPendentes = computed(() => pedidos.value.filter((p) => p.status === 'pendente').length)
const contagemDisponiveis = computed(() => pedidos.value.filter((p) => p.status === 'disponivel').length)

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'cliente_nome', label: 'Cliente' },
  { key: 'descricao', label: 'O que precisa' },
  { key: 'quantidade', label: 'Qtd.', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Anotado em' },
  { key: 'acoes', label: '', align: 'right' },
]

async function avancarStatus(pedido) {
  const novo = proximoStatus[pedido.status]
  if (!novo) return
  try {
    const atualizado = await updatePedidoCompra(pedido.id, { status: novo })
    Object.assign(pedido, atualizado)
    toast.success('Status atualizado', `Pedido marcado como "${statusLabel[novo]}".`)
  } catch (error) {
    toast.error('Não foi possível atualizar o status', error.message)
  }
}

async function cancelar(pedido) {
  try {
    const atualizado = await updatePedidoCompra(pedido.id, { status: 'cancelado' })
    Object.assign(pedido, atualizado)
    toast.info('Pedido cancelado', `${nomeCliente(pedido) ?? 'Necessidade interna'} marcado como cancelado.`)
  } catch (error) {
    toast.error('Não foi possível cancelar o pedido', error.message)
  }
}

async function excluir(pedido) {
  try {
    await deletePedidoCompra(pedido.id)
    pedidos.value = pedidos.value.filter((p) => p.id !== pedido.id)
    toast.success('Pedido removido')
  } catch (error) {
    toast.error('Não foi possível remover o pedido', error.message)
  }
}

const resultadosBusca = computed(() => {
  const q = form.value.buscaCliente.trim().toLowerCase()
  if (!q) return []
  return clientes.value
    .filter((c) => `${c.nome} ${c.telefone ?? ''}`.toLowerCase().includes(q))
    .slice(0, 5)
})

function escolherCliente(c) {
  form.value.clienteSel = c
  form.value.buscaCliente = ''
}

const modalOpen = ref(false)
const salvando = ref(false)
const editando = ref(null)
const form = ref(formVazio())

function formVazio() {
  return {
    ehCliente: true,
    modoCliente: 'buscar', // buscar | novo
    clienteSel: null,
    buscaCliente: '',
    novoNome: '',
    novoTelefone: '',
    legado: null,
    descricao: '',
    quantidade: 1,
    status: 'pendente',
    observacoes: '',
  }
}

function abrirNovo() {
  editando.value = null
  form.value = formVazio()
  modalOpen.value = true
}

function abrirEdicao(pedido) {
  editando.value = pedido
  form.value = {
    ehCliente: Boolean(pedido.cliente_id || pedido.cliente_nome),
    modoCliente: 'buscar',
    clienteSel: pedido.cliente ?? null,
    buscaCliente: '',
    novoNome: '',
    novoTelefone: '',
    // Pedido antigo, anotado só com texto livre: ainda não tem cadastro.
    legado: !pedido.cliente && pedido.cliente_nome ? { nome: pedido.cliente_nome, contato: pedido.cliente_contato } : null,
    descricao: pedido.descricao,
    quantidade: pedido.quantidade,
    status: pedido.status,
    observacoes: pedido.observacoes ?? '',
  }
  modalOpen.value = true
}

async function submit() {
  if (!form.value.descricao.trim()) return
  salvando.value = true
  try {
    const payload = {
      cliente_id: form.value.ehCliente && form.value.modoCliente === 'buscar' ? form.value.clienteSel?.id ?? null : null,
      descricao: form.value.descricao,
      quantidade: form.value.quantidade || 1,
      status: form.value.status,
      observacoes: form.value.observacoes || null,
    }
    if (form.value.ehCliente && form.value.modoCliente === 'novo') {
      if (!form.value.novoNome.trim()) return toast.error('Informe o nome do cliente')
      payload.novo_cliente = { nome: form.value.novoNome, telefone: form.value.novoTelefone || null }
    }
    if (editando.value) {
      const atualizado = await updatePedidoCompra(editando.value.id, payload)
      Object.assign(editando.value, atualizado)
      toast.success('Pedido atualizado')
    } else {
      const novo = await createPedidoCompra(payload)
      pedidos.value.unshift(novo)
      toast.success('Pedido anotado')
    }
    modalOpen.value = false
    if (payload.novo_cliente) getClientes().then((lista) => (clientes.value = lista))
  } catch (error) {
    toast.error('Não foi possível salvar o pedido', error.message)
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Pedidos de Compra</h1>
        <p class="text-sm text-ink-soft mt-1">
          O que os clientes pediram e o que você precisa comprar — mesmo sem pedido.
          <span v-if="!loading" class="text-ink-faint">· {{ contagemPendentes }} pendente(s) · {{ contagemDisponiveis }} disponível(is) pra entregar</span>
        </p>
      </div>
      <Button @click="abrirNovo">
        <template #icon-left><Plus :size="16" /></template>
        Novo Pedido
      </Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="flex-1"><SearchInput v-model="search" placeholder="Buscar por cliente ou descrição..." /></div>
        <Select v-model="statusFilter" :options="statusOptions" class="sm:w-52" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="ClipboardList" empty-title="Nenhum pedido anotado">
        <template #cell-cliente_nome="{ row }">
          <span v-if="nomeCliente(row)" class="flex items-center gap-1.5 font-medium text-ink"><UserRound :size="14" class="text-ink-faint" />{{ nomeCliente(row) }}</span>
          <span v-else class="flex items-center gap-1.5 text-ink-soft"><Package :size="14" />Necessidade interna</span>
        </template>
        <template #cell-descricao="{ value }">
          <span class="line-clamp-2 max-w-xs text-ink-soft">{{ value }}</span>
        </template>
        <template #cell-status="{ value }"><Badge :variant="statusVariant[value]" dot>{{ statusLabel[value] }}</Badge></template>
        <template #cell-created_at="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
        <template #cell-acoes="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button
              v-if="proximoStatus[row.status]"
              class="whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
              @click="avancarStatus(row)"
            >
              <span class="inline-flex items-center gap-1"><ArrowRight :size="12" />{{ proximoStatusLabel[row.status] }}</span>
            </button>
            <button
              v-if="!['concluido', 'cancelado'].includes(row.status)"
              class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-danger"
              title="Cancelar pedido"
              @click="cancelar(row)"
            >
              <Ban :size="15" />
            </button>
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-ink" title="Editar" @click="abrirEdicao(row)">
              <Pencil :size="15" />
            </button>
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-danger" title="Excluir" @click="excluir(row)">
              <Trash2 :size="15" />
            </button>
          </div>
        </template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>

    <Modal v-model="modalOpen" :title="editando ? `Editar pedido #${editando.id}` : 'Novo pedido'" size="sm">
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-ink">Pedido de um cliente</p>
            <p class="text-xs text-ink-faint">Desligado = necessidade de compra sua, sem cliente</p>
          </div>
          <Switch v-model="form.ehCliente" />
        </div>

        <template v-if="form.ehCliente">
          <div v-if="form.modoCliente === 'buscar'" class="space-y-2">
            <p class="text-sm font-medium text-ink">Cliente</p>
            <div v-if="form.clienteSel" class="flex items-center justify-between rounded-lg border border-border bg-surface px-3 py-2">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-ink">{{ form.clienteSel.nome }}</p>
                <p v-if="form.clienteSel.telefone" class="text-xs text-ink-faint">{{ form.clienteSel.telefone }}</p>
              </div>
              <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:text-ink" title="Trocar cliente" @click="form.clienteSel = null"><X :size="15" /></button>
            </div>
            <template v-else>
              <p v-if="form.legado" class="text-xs text-ink-faint">Anotado antes como: {{ form.legado.nome }}{{ form.legado.contato ? ' · ' + form.legado.contato : '' }}</p>
              <SearchInput v-model="form.buscaCliente" placeholder="Buscar cliente por nome ou telefone..." />
              <div v-if="form.buscaCliente" class="overflow-hidden rounded-lg border border-border">
                <button v-for="c in resultadosBusca" :key="c.id" class="block w-full px-3 py-2 text-left text-sm hover:bg-surface" @click="escolherCliente(c)">
                  <span class="font-medium text-ink">{{ c.nome }}</span><span v-if="c.telefone" class="ml-2 text-xs text-ink-faint">{{ c.telefone }}</span>
                </button>
                <p v-if="!resultadosBusca.length" class="px-3 py-2 text-sm text-ink-faint">Nenhum cliente encontrado.</p>
              </div>
              <button class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:underline" @click="form.modoCliente = 'novo'; form.novoNome = form.buscaCliente">
                <UserPlus :size="14" />Cadastrar novo cliente
              </button>
            </template>
          </div>
          <div v-else class="space-y-3">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-ink">Novo cliente</p>
              <button class="text-sm text-ink-soft hover:underline" @click="form.modoCliente = 'buscar'">Buscar existente</button>
            </div>
            <Input v-model="form.novoNome" label="Nome" placeholder="Ex: Maria Silva" />
            <Input v-model="form.novoTelefone" label="Telefone / WhatsApp" placeholder="(00) 00000-0000" />
          </div>
        </template>

        <Textarea v-model="form.descricao" label="O que a pessoa quer / o que precisa comprar" placeholder="Ex: Tênis Nike Air, tamanho 42, na cor preta" :rows="3" required />
        <Input v-model="form.quantidade" type="number" label="Quantidade" placeholder="1" />

        <Select v-if="editando" v-model="form.status" label="Status" :options="statusOptions.filter((o) => o.value)" />

        <Textarea v-model="form.observacoes" label="Observações (opcional)" placeholder="Ex: já deu sinal de R$20, prefere entrega" :rows="2" />
      </div>
      <template #footer>
        <Button variant="ghost" :disabled="salvando" @click="modalOpen = false">Cancelar</Button>
        <Button :loading="salvando" @click="submit">{{ editando ? 'Salvar alterações' : 'Anotar pedido' }}</Button>
      </template>
    </Modal>
  </div>
</template>
