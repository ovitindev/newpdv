<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, CheckCircle2 } from '@lucide/vue'
import { getContasPagar } from '@/services/financeiroService'
import { financeStatusOptions } from '@/data/mock/financeiro'
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
import { formatCurrency, formatDate } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const contas = ref([])
const search = ref('')
const statusFilter = ref('')

onMounted(async () => {
  contas.value = await getContasPagar()
  loading.value = false
})

const filtered = computed(() =>
  contas.value.filter((c) => {
    const matchesSearch = !search.value || c.descricao.toLowerCase().includes(search.value.toLowerCase())
    const matchesStatus = !statusFilter.value || c.status === statusFilter.value
    return matchesSearch && matchesStatus
  }),
)

const totalPendente = computed(() => contas.value.filter((c) => c.status !== 'pago').reduce((sum, c) => sum + c.valor, 0))

const { page, pageSize, paginated } = usePagination(filtered, 8)

const columns = [
  { key: 'descricao', label: 'Descrição' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'valor', label: 'Valor', align: 'right' },
  { key: 'vencimento', label: 'Vencimento' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const statusVariant = { pendente: 'warning', atrasado: 'danger', pago: 'success', recebido: 'success' }
const statusLabel = { pendente: 'Pendente', atrasado: 'Atrasado', pago: 'Pago', recebido: 'Recebido' }

function marcarPago(conta) {
  conta.status = 'pago'
  toast.success('Conta paga', `${conta.descricao} marcada como paga.`)
}

const modalOpen = ref(false)
const form = ref({ descricao: '', categoria: '', valor: '', vencimento: '' })

function submit() {
  if (!form.value.descricao || !form.value.valor) return
  contas.value.unshift({ id: Date.now(), ...form.value, valor: Number(form.value.valor), status: 'pendente' })
  form.value = { descricao: '', categoria: '', valor: '', vencimento: '' }
  modalOpen.value = false
  toast.success('Conta a pagar cadastrada')
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
      <Button @click="modalOpen = true"><template #icon-left><Plus :size="16" /></template>Nova Conta</Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="flex-1"><SearchInput v-model="search" placeholder="Buscar conta..." /></div>
        <Select v-model="statusFilter" :options="financeStatusOptions" class="sm:w-52" />
      </div>

      <Table :columns="columns" :rows="paginated" :loading="loading" empty-icon="ArrowUpCircle" empty-title="Nenhuma conta a pagar">
        <template #cell-descricao="{ value }"><span class="font-medium text-ink">{{ value }}</span></template>
        <template #cell-valor="{ value }"><span class="font-semibold text-ink">{{ formatCurrency(value) }}</span></template>
        <template #cell-vencimento="{ value }"><span class="text-ink-soft">{{ formatDate(value) }}</span></template>
        <template #cell-status="{ value }"><Badge :variant="statusVariant[value]" dot>{{ statusLabel[value] }}</Badge></template>
        <template #cell-acoes="{ row }">
          <button
            v-if="row.status !== 'pago'"
            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
            @click="marcarPago(row)"
          >
            <CheckCircle2 :size="14" /> Marcar como pago
          </button>
        </template>
      </Table>

      <Pagination v-if="filtered.length" v-model:page="page" :page-size="pageSize" :total="filtered.length" />
    </Card>

    <Modal v-model="modalOpen" title="Nova conta a pagar" size="sm">
      <div class="space-y-4">
        <Input v-model="form.descricao" label="Descrição" required />
        <Input v-model="form.categoria" label="Categoria" placeholder="Ex: Fornecedores, Fixas..." />
        <MoneyInput v-model="form.valor" label="Valor" required />
        <DatePicker v-model="form.vencimento" label="Vencimento" />
      </div>
      <template #footer>
        <Button variant="ghost" @click="modalOpen = false">Cancelar</Button>
        <Button @click="submit">Cadastrar</Button>
      </template>
    </Modal>
  </div>
</template>
