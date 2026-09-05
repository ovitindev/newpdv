<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import {
  Search,
  ScanLine,
  Plus,
  Minus,
  Trash2,
  UserRound,
  UserPlus,
  Contact,
  ShoppingCart,
  PackagePlus,
  SearchX,
  CornerDownLeft,
} from '@lucide/vue'
import { getProdutos } from '@/services/produtosService'
import { getClientes, createCliente } from '@/services/clientesService'
import { getVendedores } from '@/services/configuracoesService'
import { createVenda } from '@/services/vendasService'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Modal from '@/components/ui/Modal.vue'
import Input from '@/components/ui/Input.vue'
import MoneyInput from '@/components/ui/MoneyInput.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PaymentModal from '@/components/pdv/PaymentModal.vue'
import VendaConcluidaModal from '@/components/pdv/VendaConcluidaModal.vue'
import { formatCurrency } from '@/utils/format'

const cart = useCartStore()
const toast = useToastStore()

const produtos = ref([])
const clientes = ref([])
const vendedores = ref([])

const search = ref('')
const searchInput = ref(null)

onMounted(async () => {
  const [produtosData, clientesData, vendedoresData] = await Promise.all([getProdutos(), getClientes(), getVendedores()])
  produtos.value = produtosData.filter((produto) => produto.status === 'ativo')
  clientes.value = clientesData
  vendedores.value = vendedoresData.filter((v) => v.status === 'ativo')
  nextTick(() => searchInput.value?.focus())
  window.addEventListener('keydown', handleGlobalKeydown)
})

onUnmounted(() => window.removeEventListener('keydown', handleGlobalKeydown))

const results = computed(() => {
  if (!search.value.trim()) return []
  const term = search.value.toLowerCase()
  return produtos.value.filter((p) => p.nome.toLowerCase().includes(term) || p.codigo.toLowerCase().includes(term)).slice(0, 8)
})

function handleQtyInput(item, event) {
  const raw = event.target.value
  const value = Number(raw)
  if (raw.trim() !== '' && Number.isFinite(value) && value > 0) {
    cart.setQuantity(item.id, value)
  }
}

function handleQtyBlur(item, event) {
  const raw = event.target.value
  const value = Number(raw)
  if (raw.trim() === '' || !Number.isFinite(value) || value <= 0) {
    cart.removeItem(item.id)
  }
}

function addProduto(produto) {
  cart.addItem(produto)
  search.value = ''
  nextTick(() => searchInput.value?.focus())
}

function handleSearchEnter() {
  if (results.value.length) {
    addProduto(results.value[0])
  } else if (search.value.trim()) {
    openAvulso()
  }
}

function handleGlobalKeydown(event) {
  if (event.key === 'F2') {
    event.preventDefault()
    openAvulso()
  }
}

// Modal: item avulso
const avulsoOpen = ref(false)
const avulsoForm = ref({ nome: '', preco: '', quantidade: '1' })

function openAvulso() {
  avulsoForm.value = { nome: search.value.trim(), preco: '', quantidade: '1' }
  avulsoOpen.value = true
}

function submitAvulso() {
  if (!avulsoForm.value.nome || !avulsoForm.value.preco) return
  cart.addAvulso({ nome: avulsoForm.value.nome, preco: avulsoForm.value.preco, quantidade: avulsoForm.value.quantidade })
  avulsoForm.value = { nome: '', preco: '', quantidade: '1' }
  avulsoOpen.value = false
  search.value = ''
  toast.success('Item avulso adicionado', 'O item foi incluído no carrinho.')
  nextTick(() => searchInput.value?.focus())
}

// Modal: cliente
const clienteOpen = ref(false)
const clienteSearch = ref('')
const novoClienteForm = ref({ nome: '', telefone: '' })

const filteredClientes = computed(() =>
  clientes.value.filter((cliente) => cliente.nome.toLowerCase().includes(clienteSearch.value.toLowerCase())),
)

function selectCliente(cliente) {
  cart.setCliente(cliente)
  clienteOpen.value = false
}

const salvandoCliente = ref(false)

async function addNovoCliente() {
  if (!novoClienteForm.value.nome) return
  salvandoCliente.value = true
  try {
    const cliente = await createCliente({ nome: novoClienteForm.value.nome, telefone: novoClienteForm.value.telefone || null })
    clientes.value.unshift(cliente)
    cart.setCliente(cliente)
    novoClienteForm.value = { nome: '', telefone: '' }
    clienteOpen.value = false
    toast.success('Cliente cadastrado', `${cliente.nome} foi adicionado e selecionado.`)
  } catch (error) {
    toast.error('Não foi possível cadastrar o cliente', error.message)
  } finally {
    salvandoCliente.value = false
  }
}

// Modal: vendedor
const vendedorOpen = ref(false)

function selectVendedor(vendedor) {
  cart.setVendedor(vendedor)
  vendedorOpen.value = false
}

// Pagamento / conclusão
const paymentOpen = ref(false)
const concluidaOpen = ref(false)
const vendaConcluida = ref(null)

function abrirPagamento() {
  if (!cart.items.length) return
  if (!cart.vendedor) {
    toast.error('Selecione o vendedor', 'Escolha quem está realizando esta venda antes de continuar.')
    vendedorOpen.value = true
    return
  }
  paymentOpen.value = true
}

const finalizando = ref(false)

async function handlePagamentoConfirmado() {
  finalizando.value = true
  const itensVendidos = cart.items.map((item) => ({ ...item }))
  try {
    const venda = await createVenda({
      cliente_id: cart.cliente?.id ?? null,
      vendedor_id: cart.vendedor.id,
      desconto_percent: cart.discountPercent,
      itens: itensVendidos.map((item) => ({
        produto_id: item.avulso ? null : item.id,
        nome: item.nome,
        codigo: item.codigo,
        preco: item.preco,
        quantidade: item.quantidade,
        avulso: item.avulso,
      })),
      pagamentos: cart.payments.map((p) => ({
        metodo: p.method,
        valor: p.valor,
        parcelas: p.parcelas,
      })),
    })

    vendaConcluida.value = {
      id: venda.id,
      data: venda.data,
      itens: itensVendidos,
      subtotal: venda.subtotal,
      discountPercent: venda.descontoPercent,
      discountValue: venda.descontoValor,
      total: venda.total,
      cliente: cart.cliente,
      vendedor: cart.vendedor,
      payments: cart.payments.map((p) => ({ ...p })),
    }

    // reflete o estoque baixado no backend sem precisar recarregar a lista
    for (const item of itensVendidos) {
      if (item.avulso) continue
      const produto = produtos.value.find((p) => p.id === item.id)
      if (produto) produto.estoque = Math.max(produto.estoque - item.quantidade, 0)
    }

    paymentOpen.value = false
    concluidaOpen.value = true
    cart.clear()
  } catch (error) {
    toast.error('Não foi possível concluir a venda', error.message)
  } finally {
    finalizando.value = false
  }
}

function handleFinish() {
  concluidaOpen.value = false
  vendaConcluida.value = null
}
</script>

<template>
  <div class="flex h-full flex-col lg:flex-row">
    <!-- Coluna de busca / produtos -->
    <section class="flex min-w-0 flex-1 flex-col">
      <div class="shrink-0 border-b border-border p-4 sm:p-6">
        <div class="flex gap-2 max-w-2xl mx-auto">
          <div class="relative flex-1">
            <Search :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-faint" />
            <input
              ref="searchInput"
              v-model="search"
              type="text"
              placeholder="Buscar produto por nome ou código de barras..."
              class="h-14 w-full rounded-2xl border border-border bg-white pl-12 pr-4 text-base text-ink placeholder:text-ink-faint shadow-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400"
              @keydown.enter="handleSearchEnter"
            />
          </div>
          <button
            type="button"
            class="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-border bg-white text-ink-soft shadow-soft hover:border-brand-300 hover:text-brand-700"
            title="Ler código de barras"
          >
            <ScanLine :size="20" />
          </button>
        </div>
        <p class="text-center text-xs text-ink-faint mt-2.5 max-w-2xl mx-auto">
          Pressione <kbd class="rounded border border-border bg-surface px-1.5 py-0.5 font-mono">F2</kbd> a qualquer momento para lançar um item avulso (sem cadastro)
        </p>
      </div>

      <div class="flex-1 overflow-y-auto p-4 sm:p-6">
        <div class="max-w-2xl mx-auto">
          <!-- Idle -->
          <EmptyState
            v-if="!search.trim()"
            icon="Search"
            title="Busque um produto para começar"
            description="Digite o nome, escaneie o código de barras ou pressione F2 para vender um item sem cadastro."
          />

          <!-- Sem resultados -->
          <div v-else-if="!results.length" class="flex flex-col items-center gap-3 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-ink-faint"><SearchX :size="26" /></span>
            <div>
              <p class="text-sm font-semibold text-ink">Nenhum produto encontrado</p>
              <p class="text-sm text-ink-soft mt-1">Você pode vender "{{ search }}" como item avulso, sem cadastro.</p>
            </div>
            <Button size="sm" @click="openAvulso">
              <template #icon-left><PackagePlus :size="14" /></template>
              Adicionar "{{ search }}" como avulso
            </Button>
          </div>

          <!-- Resultados -->
          <ul v-else class="space-y-2">
            <li v-for="(produto, index) in results" :key="produto.id">
              <button
                type="button"
                class="group flex w-full items-center gap-4 rounded-2xl border bg-white p-4 text-left transition-all duration-150 hover:border-brand-300 hover:shadow-soft"
                :class="index === 0 ? 'border-brand-200 bg-brand-50/40' : 'border-border'"
                @click="addProduto(produto)"
              >
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-ink truncate">{{ produto.nome }}</p>
                  <p class="text-xs text-ink-faint mt-0.5">{{ produto.categoria }} · {{ produto.codigo }}</p>
                </div>
                <span class="text-sm font-bold text-brand-700 shrink-0">{{ formatCurrency(produto.preco) }}</span>
                <span
                  v-if="index === 0"
                  class="hidden sm:flex items-center gap-1 shrink-0 rounded-lg bg-brand-100 px-2 py-1 text-[11px] font-medium text-brand-700"
                >
                  <CornerDownLeft :size="11" /> Enter
                </span>
                <span
                  class="flex size-8 shrink-0 items-center justify-center rounded-full bg-surface text-ink-faint transition-colors group-hover:bg-brand-500 group-hover:text-white"
                >
                  <Plus :size="15" />
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Carrinho -->
    <aside class="flex w-full shrink-0 flex-col border-t lg:border-t-0 lg:border-l border-border bg-white lg:w-[400px]">
      <div class="flex items-center justify-between gap-2 border-b border-border p-4">
        <div class="flex items-center gap-2">
          <ShoppingCart :size="18" class="text-brand-600" />
          <h2 class="text-sm font-semibold text-ink">Carrinho</h2>
          <Badge v-if="cart.itemCount" variant="success" size="sm">{{ cart.itemCount }}</Badge>
        </div>
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium hover:bg-surface"
            :class="cart.vendedor ? 'text-ink' : 'text-danger'"
            @click="vendedorOpen = true"
          >
            <Contact :size="14" />
            <span class="max-w-[80px] truncate">{{ cart.vendedor?.nome ?? 'Vendedor' }}</span>
          </button>
          <button
            type="button"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium text-ink-soft hover:bg-surface hover:text-ink"
            @click="clienteOpen = true"
          >
            <UserRound :size="14" />
            <span class="max-w-[90px] truncate">{{ cart.cliente?.nome ?? 'Consumidor' }}</span>
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto p-4 sm:p-5">
        <EmptyState
          v-if="!cart.items.length"
          icon="ShoppingCart"
          title="Carrinho vazio"
          description="Busque um produto ao lado para começar a venda."
        />
        <ul v-else class="space-y-3">
          <li v-for="item in cart.items" :key="item.id" class="flex items-start gap-3 rounded-xl border border-border p-3">
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-ink truncate">{{ item.nome }}</p>
              <p class="text-xs text-ink-faint mt-0.5">{{ formatCurrency(item.preco) }} / un.</p>
              <div class="mt-2 flex items-center gap-2">
                <button
                  type="button"
                  class="flex size-6 items-center justify-center rounded-md border border-border text-ink-soft hover:border-brand-300 hover:text-brand-700"
                  @click="cart.decrementItem(item.id)"
                >
                  <Minus :size="12" />
                </button>
                <input
                  type="number"
                  min="0"
                  step="any"
                  :value="item.quantidade"
                  class="h-6 w-16 rounded-md border border-border bg-white text-center text-sm font-medium text-ink tabular-nums focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400"
                  @focus="$event.target.select()"
                  @input="handleQtyInput(item, $event)"
                  @blur="handleQtyBlur(item, $event)"
                />
                <button
                  type="button"
                  class="flex size-6 items-center justify-center rounded-md border border-border text-ink-soft hover:border-brand-300 hover:text-brand-700"
                  @click="cart.incrementItem(item.id)"
                >
                  <Plus :size="12" />
                </button>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <span class="text-sm font-semibold text-ink tabular-nums">{{ formatCurrency(item.preco * item.quantidade) }}</span>
              <button type="button" class="text-ink-faint hover:text-danger" @click="cart.removeItem(item.id)">
                <Trash2 :size="14" />
              </button>
            </div>
          </li>
        </ul>
      </div>

      <div class="shrink-0 space-y-4 border-t border-border p-4 sm:p-5">
        <div class="flex items-center justify-between">
          <label class="text-sm text-ink-soft">Desconto</label>
          <div class="flex items-center gap-1.5">
            <input
              type="number"
              min="0"
              max="100"
              :value="cart.discountPercent"
              class="h-8 w-16 rounded-lg border border-border bg-white text-center text-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400"
              @input="cart.setDiscount($event.target.value)"
            />
            <span class="text-sm text-ink-soft">%</span>
          </div>
        </div>

        <div class="space-y-1.5 text-sm">
          <div class="flex items-center justify-between text-ink-soft">
            <span>Subtotal</span>
            <span class="tabular-nums">{{ formatCurrency(cart.subtotal) }}</span>
          </div>
          <div v-if="cart.discountPercent > 0" class="flex items-center justify-between text-danger">
            <span>Desconto ({{ cart.discountPercent }}%)</span>
            <span class="tabular-nums">- {{ formatCurrency(cart.discountValue) }}</span>
          </div>
          <div class="flex items-center justify-between pt-2 border-t border-border text-base font-bold text-ink">
            <span>Total</span>
            <span class="tabular-nums text-brand-700">{{ formatCurrency(cart.total) }}</span>
          </div>
        </div>

        <Button size="lg" block :disabled="!cart.items.length" @click="abrirPagamento">
          FINALIZAR VENDA · {{ formatCurrency(cart.total) }}
        </Button>
      </div>
    </aside>

    <!-- Modal: item avulso -->
    <Modal v-model="avulsoOpen" title="Adicionar item avulso" size="sm">
      <div class="space-y-4">
        <Input v-model="avulsoForm.nome" label="Nome do item" placeholder="Ex: Produto sem cadastro" />
        <div class="grid grid-cols-2 gap-3">
          <MoneyInput v-model="avulsoForm.preco" label="Valor unitário" />
          <Input v-model="avulsoForm.quantidade" type="number" label="Quantidade" placeholder="1" hint="Ex: 100 para vender em gramas/unidades" />
        </div>
      </div>
      <template #footer>
        <Button variant="ghost" @click="avulsoOpen = false">Cancelar</Button>
        <Button @click="submitAvulso">Adicionar ao carrinho</Button>
      </template>
    </Modal>

    <!-- Modal: cliente -->
    <Modal v-model="clienteOpen" title="Selecionar cliente" size="md">
      <div class="space-y-4">
        <div class="relative">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-faint" />
          <input
            v-model="clienteSearch"
            type="text"
            placeholder="Buscar cliente..."
            class="h-10 w-full rounded-[var(--radius-control)] border border-border bg-white pl-9 pr-3 text-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400"
          />
        </div>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm hover:bg-surface"
          @click="selectCliente(null)"
        >
          <span class="flex size-8 items-center justify-center rounded-full bg-surface text-ink-faint">
            <UserRound :size="15" />
          </span>
          Consumidor Final
        </button>
        <ul class="max-h-52 overflow-y-auto divide-y divide-border">
          <li v-for="cliente in filteredClientes" :key="cliente.id">
            <button type="button" class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left hover:bg-surface" @click="selectCliente(cliente)">
              <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600 text-xs font-semibold">
                {{ cliente.nome.slice(0, 2).toUpperCase() }}
              </span>
              <span class="min-w-0">
                <span class="block text-sm font-medium text-ink truncate">{{ cliente.nome }}</span>
                <span class="block text-xs text-ink-soft truncate">{{ cliente.telefone }}</span>
              </span>
            </button>
          </li>
        </ul>

        <div class="rounded-xl bg-surface p-4 space-y-3">
          <p class="flex items-center gap-2 text-sm font-medium text-ink">
            <UserPlus :size="15" class="text-brand-600" /> Cadastro rápido
          </p>
          <div class="grid grid-cols-2 gap-3">
            <Input v-model="novoClienteForm.nome" placeholder="Nome do cliente" />
            <Input v-model="novoClienteForm.telefone" placeholder="Telefone" />
          </div>
          <Button size="sm" variant="outline" :loading="salvandoCliente" @click="addNovoCliente">Cadastrar e selecionar</Button>
        </div>
      </div>
    </Modal>

    <!-- Modal: vendedor -->
    <Modal v-model="vendedorOpen" title="Selecionar vendedor" size="sm">
      <ul class="max-h-72 overflow-y-auto divide-y divide-border">
        <li v-for="vendedor in vendedores" :key="vendedor.id">
          <button type="button" class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left hover:bg-surface" @click="selectVendedor(vendedor)">
            <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600 text-xs font-semibold">
              {{ vendedor.nome.slice(0, 2).toUpperCase() }}
            </span>
            <span class="text-sm font-medium text-ink">{{ vendedor.nome }}</span>
          </button>
        </li>
      </ul>
      <p v-if="!vendedores.length" class="text-sm text-ink-soft text-center py-6">
        Nenhum vendedor cadastrado. Cadastre em Configurações → Vendedores.
      </p>
    </Modal>

    <PaymentModal v-model="paymentOpen" :loading="finalizando" @confirmed="handlePagamentoConfirmado" />
    <VendaConcluidaModal v-model="concluidaOpen" :venda="vendaConcluida" @finish="handleFinish" />
  </div>
</template>
