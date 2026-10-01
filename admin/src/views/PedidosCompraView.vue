<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, ArrowRight, Trash2, Pencil, UserRound, Package, Ban } from '@lucide/vue'
import {
  getPedidosCompra,
  createPedidoCompra,
  updatePedidoCompra,
  deletePedidoCompra,
} from '@/services/pedidosCompraService'
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

onMounted(carregar)

const filtered = computed(() =>
  pedidos.value.filter((p) => {
    const alvo = `${p.cliente_nome ?? ''} ${p.descricao}`.toLowerCase()
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
    toast.info('Pedido cancelado', `${pedido.cliente_nome ?? 'Necessidade interna'} marcado como cancelado.`)
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

const modalOpen = ref(false)
const salvando = ref(false)
const editando = ref(null)
const form = ref(formVazio())

function formVazio() {
  return {
    ehCliente: true,
    cliente_nome: '',
    cliente_contato: '',
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
    ehCliente: Boolean(pedido.cliente_nome),
    cliente_nome: pedido.cliente_nome ?? '',
    cliente_contato: pedido.cliente_contato ?? '',
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
      cliente_nome: form.value.ehCliente ? form.value.cliente_nome || null : null,
      cliente_contato: form.value.ehCliente ? form.value.cliente_contato || null : null,
      descricao: form.value.descricao,
      quantidade: form.value.quantidade || 1,
      status: form.value.status,
      observacoes: form.value.observacoes || null,
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
        <template #cell-cliente_nome="{ value }">
          <span v-if="value" class="flex items-center gap-1.5 font-medium text-ink"><UserRound :size="14" class="text-ink-faint" />{{ value }}</span>
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
          <Input v-model="form.cliente_nome" label="Nome do cliente" placeholder="Ex: Maria Silva" />
          <Input v-model="form.cliente_contato" label="Contato" placeholder="Telefone, WhatsApp..." />
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
