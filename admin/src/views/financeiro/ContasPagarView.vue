<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, CheckCircle2, Repeat, Trash2 } from '@lucide/vue'
import {
  getContasPagar,
  createContaPagar,
  updateContaPagar,
  deleteContaPagar,
  replicarContasPagar,
} from '@/services/financeiroService'
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
import MoneyInput from '@/components/ui/MoneyInput.vue'
import DatePicker from '@/components/ui/DatePicker.vue'
import Switch from '@/components/ui/Switch.vue'
import { formatCurrency, formatDateOnly } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const contas = ref([])
const search = ref('')
const statusFilter = ref('')

const statusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'pendente', label: 'Pendente' },
  { value: 'atrasado', label: 'Atrasado' },
  { value: 'pago', label: 'Pago' },
]

async function carregar() {
  loading.value = true
  contas.value = await getContasPagar()
  loading.value = false
}

onMounted(carregar)

const filtered = computed(() =>
  contas.value.filter((c) => {
    const matchesSearch = !search.value || c.descricao.toLowerCase().includes(search.value.toLowerCase())
    const matchesStatus = !statusFilter.value || c.status === statusFilter.value
    return matchesSearch && matchesStatus
  }),
)

const totalPendente = computed(() => contas.value.filter((c) => c.status !== 'pago').reduce((sum, c) => sum + Number(c.valor), 0))

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'descricao', label: 'Descrição' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'valor', label: 'Valor', align: 'right' },
  { key: 'vencimento', label: 'Vencimento' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const statusVariant = { pendente: 'warning', atrasado: 'danger', pago: 'success' }
const statusLabel = { pendente: 'Pendente', atrasado: 'Atrasado', pago: 'Pago' }

async function marcarPago(conta) {
  try {
    const atualizada = await updateContaPagar(conta.id, { ...conta, status: 'pago' })
    Object.assign(conta, atualizada)
    toast.success('Conta paga', `${conta.descricao} marcada como paga.`)
  } catch (error) {
    toast.error('Não foi possível marcar como paga', error.message)
  }
}

async function excluir(conta) {
  try {
    await deleteContaPagar(conta.id)
    contas.value = contas.value.filter((c) => c.id !== conta.id)
    toast.success('Conta removida')
  } catch (error) {
    toast.error('Não foi possível remover a conta', error.message)
  }
}

const replicando = ref(false)
async function replicar() {
  replicando.value = true
  try {
    const resultado = await replicarContasPagar()
    if (resultado.criadas > 0) {
      toast.success('Contas replicadas', `${resultado.criadas} conta(s) recorrente(s) gerada(s) para o próximo mês.`)
      await carregar()
    } else {
      toast.info('Nada para replicar', 'Todas as contas recorrentes já têm a próxima ocorrência lançada.')
    }
  } catch (error) {
    toast.error('Não foi possível replicar as contas', error.message)
  } finally {
    replicando.value = false
  }
}

const modalOpen = ref(false)
const salvando = ref(false)
const form = ref({ descricao: '', categoria: '', valor: '', vencimento: '', recorrente: false })

async function submit() {
  if (!form.value.descricao || !form.value.valor || !form.value.vencimento) return
  salvando.value = true
  try {
    const nova = await createContaPagar(form.value)
    contas.value.unshift(nova)
    form.value = { descricao: '', categoria: '', valor: '', vencimento: '', recorrente: false }
    modalOpen.value = false
    toast.success('Conta a pagar cadastrada')
  } catch (error) {
    toast.error('Não foi possível cadastrar a conta', error.message)
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Contas a Pagar</h1>
        <p class="text-sm text-ink-soft mt-1">
          Total em aberto: <span class="font-semibold text-ink">{{ formatCurrency(totalPendente) }}</span>
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button variant="outline" :loading="replicando" @click="replicar">
          <template #icon-left><Repeat :size="16" /></template>
          Replicar contas
        </Button>
        <Button @click="modalOpen = true"><template #icon-left><Plus :size="16" /></template>Nova Conta</Button>
      </div>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="flex-1"><SearchInput v-model="search" placeholder="Buscar conta..." /></div>
        <Select v-model="statusFilter" :options="statusOptions" class="sm:w-52" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="ArrowUpCircle" empty-title="Nenhuma conta a pagar">
        <template #cell-descricao="{ row, value }">
          <span class="font-medium text-ink">{{ value }}</span>
          <Badge v-if="row.recorrente" variant="info" size="sm" class="ml-2">Recorrente</Badge>
        </template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-vencimento="{ value }"><span class="text-ink-soft">{{ formatDateOnly(value) }}</span></template>
        <template #cell-status="{ value }"><Badge :variant="statusVariant[value]" dot>{{ statusLabel[value] }}</Badge></template>
        <template #cell-acoes="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button
              v-if="row.status !== 'pago'"
              class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
              @click="marcarPago(row)"
            >
              <CheckCircle2 :size="14" /> Marcar como pago
            </button>
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-danger" title="Excluir" @click="excluir(row)">
              <Trash2 :size="15" />
            </button>
          </div>
        </template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>

    <Modal v-model="modalOpen" title="Nova conta a pagar" size="sm">
      <div class="space-y-4">
        <Input v-model="form.descricao" label="Descrição" required />
        <Input v-model="form.categoria" label="Categoria" placeholder="Ex: Fornecedores, Fixas, Funcionários..." />
        <MoneyInput v-model="form.valor" label="Valor" required />
        <DatePicker v-model="form.vencimento" label="Vencimento" />
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-ink">Conta recorrente</p>
            <p class="text-xs text-ink-faint">Aparece no botão "Replicar contas" todo mês</p>
          </div>
          <Switch v-model="form.recorrente" />
        </div>
      </div>
      <template #footer>
        <Button variant="ghost" :disabled="salvando" @click="modalOpen = false">Cancelar</Button>
        <Button :loading="salvando" @click="submit">Cadastrar</Button>
      </template>
    </Modal>
  </div>
</template>
