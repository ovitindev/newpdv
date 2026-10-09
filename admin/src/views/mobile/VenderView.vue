<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import {
  Search, ScanLine, Plus, Minus, Trash2, PackagePlus, ShoppingBag, Truck, Banknote, QrCode, CreditCard,
  CircleCheck, MessageCircle, X, Tag,
} from '@lucide/vue'
import { getProdutos } from '@/services/produtosService'
import { getVendedores } from '@/services/configuracoesService'
import { createVenda } from '@/services/vendasService'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import { formatCurrency } from '@/utils/format'
import { whatsappLink } from '@/utils/whatsapp'
import BottomSheet from '@/components/mobile/BottomSheet.vue'
import ClientePicker from '@/components/mobile/ClientePicker.vue'
import BarcodeScanner from '@/components/mobile/BarcodeScanner.vue'

const auth = useAuthStore()
const cart = useCartStore()
const toast = useToastStore()

const produtos = ref([])
const vendedores = ref([])
const loading = ref(true)
const busca = ref('')
const categoria = ref('')

onMounted(async () => {
  restaurarCarrinho()
  try {
    const [lista, vends] = await Promise.all([getProdutos(), auth.ehVendedor ? Promise.resolve([]) : getVendedores()])
    produtos.value = lista.filter((p) => p.status === 'ativo')
    vendedores.value = vends.filter((v) => v.status === 'ativo')
  } catch (e) {
    toast.error('Não foi possível carregar os produtos', e.message)
  } finally {
    loading.value = false
  }
})

// iOS/Android encerram o app em segundo plano: guardar o carrinho evita a
// vendedora perder a venda montada ao trocar de app pra responder um cliente.
const CART_KEY = `novapdv_cart_${auth.user?.id ?? 'anon'}`
function restaurarCarrinho() {
  if (cart.items.length) return
  try {
    const salvo = JSON.parse(localStorage.getItem(CART_KEY) ?? 'null')
    if (!salvo) return
    cart.items = salvo.items ?? []
    cart.discountPercent = salvo.discountPercent ?? 0
    cart.cliente = salvo.cliente ?? null
    cart.entrega = Boolean(salvo.entrega)
    cart.frete = salvo.frete ?? 0
  } catch {
    // dado corrompido: ignora e começa vazio
  }
}
const parar = cart.$subscribe(() => {
  try {
    if (!cart.items.length && !cart.cliente) return localStorage.removeItem(CART_KEY)
    localStorage.setItem(
      CART_KEY,
      JSON.stringify({ items: cart.items, discountPercent: cart.discountPercent, cliente: cart.cliente, entrega: cart.entrega, frete: cart.frete }),
    )
  } catch {
    // storage cheio/indisponível: segue sem persistir
  }
})
onUnmounted(parar)

const categorias = computed(() => [...new Set(produtos.value.map((p) => p.categoria).filter((c) => c && c !== '—'))].sort())

const resultados = computed(() => {
  const q = busca.value.trim().toLowerCase()
  return produtos.value
    .filter((p) => (!categoria.value || p.categoria === categoria.value) && (!q || p.nome.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q)))
    .slice(0, q || categoria.value ? 60 : 30)
})

const qtdNoCarrinho = (id) => cart.items.find((i) => i.id === id)?.quantidade ?? 0

function adicionar(produto) {
  cart.addItem(produto)
  navigator.vibrate?.(15)
}

function enterBusca() {
  const exato = produtos.value.find((p) => p.codigo.toLowerCase() === busca.value.trim().toLowerCase())
  if (exato) {
    adicionar(exato)
    busca.value = ''
  } else if (resultados.value.length === 1) {
    adicionar(resultados.value[0])
    busca.value = ''
  }
}

// Leitor de código de barras (só onde o navegador suporta — Android/Chrome).
const scannerDisponivel = typeof window !== 'undefined' && 'BarcodeDetector' in window && Boolean(navigator.mediaDevices?.getUserMedia)
const scannerAberto = ref(false)
let ultimoLido = ''
function lido(codigo) {
  if (codigo === ultimoLido) return
  ultimoLido = codigo
  setTimeout(() => (ultimoLido = ''), 1500)
  const produto = produtos.value.find((p) => p.codigo === codigo)
  if (produto) {
    adicionar(produto)
    toast.success('Adicionado', produto.nome)
  } else {
    toast.error('Código não encontrado', codigo)
  }
}

// Item avulso
const avulsoAberto = ref(false)
const avulso = ref({ nome: '', preco: '', quantidade: '1' })
const num = (v) => Number(String(v).replace(',', '.')) || 0
function abrirAvulso() {
  avulso.value = { nome: busca.value.trim(), preco: '', quantidade: '1' }
  avulsoAberto.value = true
}
function addAvulso() {
  if (!avulso.value.nome.trim() || num(avulso.value.preco) <= 0) return toast.error('Informe o nome e o valor')
  cart.addAvulso({ nome: avulso.value.nome.trim(), preco: num(avulso.value.preco), quantidade: num(avulso.value.quantidade) || 1 })
  avulsoAberto.value = false
  busca.value = ''
}

// Carrinho / pagamento
const carrinhoAberto = ref(false)
const etapa = ref('carrinho') // carrinho | pagamento | concluida
const finalizando = ref(false)
const vendaConcluida = ref(null)

const temFreteAvulso = computed(() => cart.entrega && cart.freteValue > 0)
const podeAvancar = computed(() => cart.items.length > 0 || temFreteAvulso.value)

function abrirCarrinho() {
  etapa.value = 'carrinho'
  carrinhoAberto.value = true
}

const vendedorId = ref('')
const vendedorEscolhido = computed(() => (auth.ehVendedor ? auth.vendedor : vendedores.value.find((v) => String(v.id) === String(vendedorId.value))))

function irParaPagamento() {
  if (!podeAvancar.value) return
  chaveVenda = null
  if (!vendedorEscolhido.value) return toast.error('Selecione o vendedor', 'Escolha quem está realizando esta venda.')
  cart.clearPayments()
  metodo.value = ''
  trocoVenda.value = 0
  etapa.value = 'pagamento'
}

const metodos = [
  { id: 'pix', label: 'PIX', icon: QrCode },
  { id: 'dinheiro', label: 'Dinheiro', icon: Banknote },
  { id: 'debito', label: 'Débito', icon: CreditCard },
  { id: 'credito', label: 'Crédito', icon: CreditCard },
]
const metodoLabel = Object.fromEntries(metodos.map((m) => [m.id, m.label]))
const metodo = ref('')
const valorPagamento = ref('')
const parcelas = ref(1)
const recebido = ref('')

watch(metodo, () => {
  valorPagamento.value = cart.restante.toFixed(2).replace('.', ',')
  parcelas.value = 1
  recebido.value = ''
})

const troco = computed(() => (metodo.value === 'dinheiro' && recebido.value ? Math.max(num(recebido.value) - num(valorPagamento.value), 0) : 0))
// O troco precisa sobreviver ao "Adicionar" (que limpa o método) até a tela final.
const trocoVenda = ref(0)

function addPagamento() {
  const valor = Math.round(num(valorPagamento.value) * 100) / 100
  if (!metodo.value || valor <= 0) return
  if (valor > cart.restante + 0.005) return toast.error('Valor maior que o restante', `Restam ${formatCurrency(cart.restante)}.`)
  if (metodo.value === 'dinheiro') trocoVenda.value = troco.value
  cart.addPayment({ method: metodo.value, valor, parcelas: metodo.value === 'credito' ? parcelas.value : 1 })
  metodo.value = ''
  valorPagamento.value = ''
}

// Mesma chave em todas as tentativas de fechar ESTA venda; muda só depois de concluída.
let chaveVenda = null
const novaChave = () => (crypto.randomUUID ? crypto.randomUUID() : '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (c) => (c ^ (Math.random() * 16) >> (c / 4)).toString(16)))

async function finalizar() {
  if (cart.restante > 0.005 || finalizando.value) return
  finalizando.value = true
  const itens = cart.items.map((i) => ({ ...i }))
  const pagamentos = cart.payments.map((p) => ({ ...p }))
  const cliente = cart.cliente
  try {
    chaveVenda ??= novaChave()
    const venda = await createVenda({
      client_uuid: chaveVenda,
      cliente_id: cliente?.id ?? null,
      vendedor_id: vendedorEscolhido.value.id,
      desconto_percent: cart.discountPercent,
      entrega: cart.entrega,
      valor_frete: cart.freteValue,
      itens: itens.map((i) => ({
        produto_id: i.avulso ? null : i.id,
        nome: i.nome,
        codigo: i.codigo,
        preco: i.preco,
        quantidade: i.quantidade,
        avulso: i.avulso,
      })),
      pagamentos: pagamentos.map((p) => ({ metodo: p.method, valor: p.valor, parcelas: p.parcelas })),
    })
    vendaConcluida.value = { ...venda, itens, pagamentos, cliente, troco: pagamentos.some((p) => p.method === 'dinheiro') ? trocoVenda.value : 0 }
    for (const i of itens) {
      if (i.avulso) continue
      const p = produtos.value.find((x) => x.id === i.id)
      if (p) p.estoque = Math.max(p.estoque - i.quantidade, 0)
    }
    chaveVenda = null
    cart.clear()
    etapa.value = 'concluida'
  } catch (e) {
    toast.error('Não foi possível concluir a venda', e.message)
  } finally {
    finalizando.value = false
  }
}

function recibo(v) {
  const linhas = v.itens.map((i) => `• ${i.quantidade}x ${i.nome} — ${formatCurrency(i.preco * i.quantidade)}`)
  const extra = []
  if (v.descontoValor) extra.push(`Desconto: -${formatCurrency(v.descontoValor)}`)
  if (v.valorFrete) extra.push(`Frete: ${formatCurrency(v.valorFrete)}`)
  return [
    `*${auth.empresa?.nome_fantasia ?? 'NovaPDV'}*`,
    `Recibo da venda #${v.id}`,
    '',
    ...linhas,
    ...extra,
    '',
    `*Total: ${formatCurrency(v.total)}*`,
    v.pagamentos.map((p) => `${metodoLabel[p.method]}${p.method === 'credito' && p.parcelas > 1 ? ` ${p.parcelas}x` : ''}: ${formatCurrency(p.valor)}`).join(' | '),
    '',
    'Obrigada pela preferência!',
  ].join('\n')
}

function novaVenda() {
  carrinhoAberto.value = false
  vendaConcluida.value = null
  etapa.value = 'carrinho'
}

function fecharSheet(aberto) {
  carrinhoAberto.value = aberto
  // Se fechar a tela de "venda concluída" sem tocar em Nova venda, limpa o estado também.
  if (!aberto && etapa.value === 'concluida') novaVenda()
}

const tituloSheet = computed(() => ({ carrinho: 'Carrinho', pagamento: 'Pagamento', concluida: 'Venda concluída' })[etapa.value])
</script>

<template>
  <div class="flex min-h-full flex-col">
    <div class="sticky top-0 z-10 space-y-2.5 bg-surface/95 px-4 pb-2 pt-3 backdrop-blur">
      <div class="flex gap-2">
        <div class="relative flex-1">
          <Search :size="19" class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-faint" />
          <input
            v-model="busca"
            type="search"
            enterkeyhint="search"
            placeholder="Buscar produto ou código"
            class="h-13 h-[52px] w-full rounded-2xl border border-border bg-white pl-12 pr-4 text-base text-ink shadow-soft outline-none placeholder:text-ink-faint focus:border-brand-400"
            @keydown.enter="enterBusca"
          />
        </div>
        <button v-if="scannerDisponivel" class="flex size-[52px] shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-soft active:bg-brand-700" aria-label="Ler código de barras" @click="scannerAberto = true">
          <ScanLine :size="22" />
        </button>
      </div>
      <div v-if="categorias.length" class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]">
        <button class="shrink-0 rounded-full px-4 py-2 text-sm font-medium" :class="!categoria ? 'bg-brand-600 text-white' : 'bg-white text-ink-soft shadow-soft'" @click="categoria = ''">Todos</button>
        <button v-for="c in categorias" :key="c" class="shrink-0 rounded-full px-4 py-2 text-sm font-medium" :class="categoria === c ? 'bg-brand-600 text-white' : 'bg-white text-ink-soft shadow-soft'" @click="categoria = categoria === c ? '' : c">{{ c }}</button>
      </div>
    </div>

    <div class="flex-1 space-y-2.5 px-4 pb-28 pt-1">
      <template v-if="loading">
        <div v-for="n in 5" :key="n" class="h-20 animate-pulse rounded-2xl bg-white" />
      </template>
      <template v-else>
        <article v-for="p in resultados" :key="p.id" class="flex items-center gap-3 rounded-2xl bg-white p-3.5 shadow-soft">
          <div class="min-w-0 flex-1">
            <p class="line-clamp-2 font-medium leading-snug text-ink">{{ p.nome }}</p>
            <p class="mt-0.5 text-xs text-ink-faint">
              {{ p.codigo }} ·
              <span :class="p.estoque <= 0 ? 'font-medium text-danger' : p.estoque <= 3 ? 'font-medium text-warning' : ''">{{ p.estoque <= 0 ? 'Sem estoque' : `${p.estoque} em estoque` }}</span>
            </p>
            <p class="mt-1 text-lg font-semibold text-brand-700">{{ formatCurrency(p.preco) }}</p>
          </div>
          <div v-if="qtdNoCarrinho(p.id)" class="flex shrink-0 items-center gap-1 rounded-full bg-brand-50 p-1">
            <button class="flex size-10 items-center justify-center rounded-full bg-white text-brand-700 shadow-soft active:scale-95" aria-label="Diminuir" @click="cart.decrementItem(p.id)"><Minus :size="18" /></button>
            <span class="w-7 text-center font-semibold text-brand-700">{{ qtdNoCarrinho(p.id) }}</span>
            <button class="flex size-10 items-center justify-center rounded-full bg-brand-600 text-white active:scale-95" aria-label="Aumentar" @click="adicionar(p)"><Plus :size="18" /></button>
          </div>
          <button v-else class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-soft active:scale-95 active:bg-brand-700" aria-label="Adicionar" @click="adicionar(p)"><Plus :size="22" /></button>
        </article>

        <p v-if="!resultados.length" class="py-10 text-center text-sm text-ink-faint">Nenhum produto encontrado.</p>

        <button class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-brand-300 bg-white font-medium text-brand-700 active:bg-brand-50" @click="abrirAvulso">
          <PackagePlus :size="20" />Adicionar item avulso
        </button>
      </template>
    </div>

    <div v-if="cart.items.length || temFreteAvulso" class="sticky bottom-0 z-20 px-4 pb-3">
      <button class="flex h-16 w-full items-center gap-3 rounded-2xl bg-gradient-to-r from-brand-700 to-brand-900 px-4 text-white shadow-soft-lg active:scale-[0.99]" @click="abrirCarrinho">
        <span class="relative flex size-10 items-center justify-center rounded-full bg-white/15">
          <ShoppingBag :size="20" />
          <span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-brand-300 text-[11px] font-bold text-brand-950">{{ Math.round(cart.itemCount) }}</span>
        </span>
        <span class="flex-1 text-left text-sm font-medium">Ver carrinho</span>
        <span class="text-lg font-semibold">{{ formatCurrency(cart.total) }}</span>
      </button>
    </div>

    <!-- Scanner -->
    <BottomSheet v-model="scannerAberto" title="Ler código de barras">
      <BarcodeScanner v-if="scannerAberto" @detected="lido" />
      <p class="mt-3 pb-2 text-center text-sm text-ink-soft">Aponte para o código. Os produtos lidos vão direto pro carrinho.</p>
    </BottomSheet>

    <!-- Item avulso -->
    <BottomSheet v-model="avulsoAberto" title="Item avulso">
      <form class="space-y-4 pt-1" @submit.prevent="addAvulso">
        <label class="block"><span class="mb-1.5 block text-sm font-medium">Descrição</span>
          <input v-model="avulso.nome" type="text" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
        </label>
        <div class="grid grid-cols-2 gap-3">
          <label class="block"><span class="mb-1.5 block text-sm font-medium">Valor (R$)</span>
            <input v-model="avulso.preco" type="text" inputmode="decimal" placeholder="0,00" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
          </label>
          <label class="block"><span class="mb-1.5 block text-sm font-medium">Quantidade</span>
            <input v-model="avulso.quantidade" type="text" inputmode="decimal" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
          </label>
        </div>
        <button type="submit" class="h-13 h-[52px] w-full rounded-2xl bg-brand-600 font-semibold text-white active:bg-brand-700">Adicionar ao carrinho</button>
      </form>
    </BottomSheet>

    <!-- Carrinho -> pagamento -> concluída -->
    <BottomSheet :model-value="carrinhoAberto" :title="tituloSheet" full @update:model-value="fecharSheet">
      <!-- 1. Carrinho -->
      <div v-if="etapa === 'carrinho'" class="space-y-5 pt-1">
        <ul v-if="cart.items.length" class="divide-y divide-border rounded-2xl border border-border">
          <li v-for="i in cart.items" :key="i.id" class="flex items-center gap-3 p-3">
            <div class="min-w-0 flex-1">
              <p class="line-clamp-2 text-sm font-medium text-ink">{{ i.nome }}</p>
              <p class="text-xs text-ink-faint">{{ formatCurrency(i.preco) }} · <span class="font-medium text-ink-soft">{{ formatCurrency(i.preco * i.quantidade) }}</span></p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
              <button class="flex size-10 items-center justify-center rounded-full bg-surface active:bg-surface-alt" aria-label="Diminuir" @click="cart.decrementItem(i.id)"><Minus :size="16" /></button>
              <span class="w-8 text-center font-semibold">{{ i.quantidade }}</span>
              <button class="flex size-10 items-center justify-center rounded-full bg-brand-600 text-white active:bg-brand-700" aria-label="Aumentar" @click="cart.incrementItem(i.id)"><Plus :size="16" /></button>
              <button class="ml-1 flex size-10 items-center justify-center rounded-full text-ink-faint active:bg-danger-soft active:text-danger" aria-label="Remover" @click="cart.removeItem(i.id)"><Trash2 :size="17" /></button>
            </div>
          </li>
        </ul>
        <p v-else class="rounded-2xl bg-surface p-5 text-center text-sm text-ink-soft">Sem itens — só o frete será cobrado.</p>

        <ClientePicker :model-value="cart.cliente" @update:model-value="cart.setCliente($event)" />

        <label v-if="!auth.ehVendedor" class="block">
          <span class="mb-1.5 block text-sm font-medium text-ink">Vendedor</span>
          <select v-model="vendedorId" class="h-12 w-full rounded-2xl border border-border bg-white px-4 text-base outline-none focus:border-brand-400">
            <option value="" disabled>Selecione...</option>
            <option v-for="v in vendedores" :key="v.id" :value="v.id">{{ v.nome }}</option>
          </select>
        </label>

        <div>
          <p class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-ink"><Tag :size="15" class="text-ink-faint" />Desconto</p>
          <div class="flex gap-2">
            <button v-for="d in [0, 5, 10, 15]" :key="d" class="h-11 flex-1 rounded-xl text-sm font-medium" :class="cart.discountPercent === d ? 'bg-brand-600 text-white' : 'bg-surface text-ink-soft'" @click="cart.setDiscount(d)">{{ d ? `${d}%` : 'Sem' }}</button>
            <input :value="cart.discountPercent || ''" type="text" inputmode="decimal" placeholder="%" aria-label="Desconto personalizado em %" class="h-11 w-16 rounded-xl border border-border text-center text-base outline-none focus:border-brand-400" @input="cart.setDiscount(num($event.target.value))" />
          </div>
        </div>

        <div>
          <button class="flex h-14 w-full items-center gap-3 rounded-2xl border px-4 text-left" :class="cart.entrega ? 'border-brand-400 bg-brand-50' : 'border-border'" @click="cart.setEntrega(!cart.entrega)">
            <Truck :size="20" :class="cart.entrega ? 'text-brand-600' : 'text-ink-faint'" />
            <span class="flex-1 font-medium text-ink">Entrega / frete</span>
            <span class="relative h-7 w-12 rounded-full transition-colors" :class="cart.entrega ? 'bg-brand-600' : 'bg-border'"><span class="absolute top-0.5 size-6 rounded-full bg-white shadow transition-all" :class="cart.entrega ? 'left-[22px]' : 'left-0.5'" /></span>
          </button>
          <input v-if="cart.entrega" :value="cart.frete || ''" type="text" inputmode="decimal" placeholder="Valor do frete (R$)" class="mt-2 h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" @input="cart.setFrete(num($event.target.value))" />
        </div>

        <dl class="space-y-1.5 rounded-2xl bg-surface p-4 text-sm">
          <div class="flex justify-between text-ink-soft"><dt>Subtotal</dt><dd>{{ formatCurrency(cart.subtotal) }}</dd></div>
          <div v-if="cart.discountValue" class="flex justify-between text-ink-soft"><dt>Desconto</dt><dd>-{{ formatCurrency(cart.discountValue) }}</dd></div>
          <div v-if="cart.freteValue" class="flex justify-between text-ink-soft"><dt>Frete</dt><dd>{{ formatCurrency(cart.freteValue) }}</dd></div>
          <div class="flex justify-between border-t border-border pt-2 text-lg font-semibold text-ink"><dt>Total</dt><dd>{{ formatCurrency(cart.total) }}</dd></div>
        </dl>
      </div>

      <!-- 2. Pagamento -->
      <div v-else-if="etapa === 'pagamento'" class="space-y-5 pt-1">
        <div class="rounded-2xl bg-gradient-to-br from-brand-700 to-brand-950 p-5 text-white">
          <p class="text-sm text-brand-100">Total da venda</p>
          <p class="text-3xl font-semibold">{{ formatCurrency(cart.total) }}</p>
          <p class="mt-1 text-sm" :class="cart.restante > 0 ? 'text-brand-200' : 'text-brand-300'">{{ cart.restante > 0 ? `Falta ${formatCurrency(cart.restante)}` : 'Pagamento completo' }}</p>
        </div>

        <ul v-if="cart.payments.length" class="divide-y divide-border rounded-2xl border border-border">
          <li v-for="p in cart.payments" :key="p.id" class="flex items-center gap-3 px-4 py-3">
            <span class="flex-1 font-medium text-ink">{{ metodoLabel[p.method] }}<span v-if="p.method === 'credito' && p.parcelas > 1" class="text-ink-faint"> · {{ p.parcelas }}x</span></span>
            <span class="font-semibold">{{ formatCurrency(p.valor) }}</span>
            <button class="flex size-9 items-center justify-center rounded-full text-ink-faint active:bg-danger-soft" aria-label="Remover pagamento" @click="cart.removePayment(p.id)"><X :size="16" /></button>
          </li>
        </ul>

        <template v-if="cart.restante > 0.005">
          <div class="grid grid-cols-2 gap-2.5">
            <button v-for="m in metodos" :key="m.id" class="flex h-16 items-center gap-3 rounded-2xl border px-4 font-medium" :class="metodo === m.id ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-200' : 'border-border text-ink'" @click="metodo = m.id">
              <component :is="m.icon" :size="22" :class="metodo === m.id ? 'text-brand-600' : 'text-ink-faint'" />{{ m.label }}
            </button>
          </div>

          <div v-if="metodo" class="space-y-3 rounded-2xl bg-surface p-4">
            <label class="block"><span class="mb-1.5 block text-sm font-medium">Valor neste pagamento (R$)</span>
              <input v-model="valorPagamento" type="text" inputmode="decimal" class="h-14 w-full rounded-2xl border border-border bg-white px-4 text-xl font-semibold outline-none focus:border-brand-400" />
            </label>
            <div v-if="metodo === 'credito'">
              <p class="mb-1.5 text-sm font-medium">Parcelas</p>
              <div class="flex flex-wrap gap-2">
                <button v-for="n in 12" :key="n" class="h-10 min-w-12 rounded-xl px-3 text-sm font-medium" :class="parcelas === n ? 'bg-brand-600 text-white' : 'bg-white text-ink-soft'" @click="parcelas = n">{{ n }}x</button>
              </div>
              <p class="mt-1.5 text-xs text-ink-faint">{{ parcelas }}x de {{ formatCurrency(num(valorPagamento) / parcelas) }}</p>
            </div>
            <label v-if="metodo === 'dinheiro'" class="block"><span class="mb-1.5 block text-sm font-medium">Valor recebido (para o troco)</span>
              <input v-model="recebido" type="text" inputmode="decimal" placeholder="Opcional" class="h-12 w-full rounded-2xl border border-border bg-white px-4 text-base outline-none focus:border-brand-400" />
              <span v-if="troco > 0" class="mt-1.5 block text-lg font-semibold text-brand-700">Troco: {{ formatCurrency(troco) }}</span>
            </label>
            <button class="h-[52px] w-full rounded-2xl bg-brand-600 font-semibold text-white active:bg-brand-700" @click="addPagamento">Adicionar {{ formatCurrency(num(valorPagamento)) }}</button>
          </div>
        </template>
      </div>

      <!-- 3. Concluída -->
      <div v-else-if="vendaConcluida" class="flex flex-col items-center pb-2 pt-4 text-center">
        <span class="flex size-20 items-center justify-center rounded-full bg-brand-50 text-brand-600"><CircleCheck :size="48" /></span>
        <p class="mt-4 text-sm text-ink-soft">Venda #{{ vendaConcluida.id }}</p>
        <p class="text-4xl font-semibold text-ink">{{ formatCurrency(vendaConcluida.total) }}</p>
        <p v-if="vendaConcluida.cliente" class="mt-1 text-sm text-ink-soft">{{ vendaConcluida.cliente.nome }}</p>
        <p v-if="vendaConcluida.troco > 0" class="mt-3 rounded-full bg-brand-50 px-4 py-1.5 text-sm font-semibold text-brand-700">Troco: {{ formatCurrency(vendaConcluida.troco) }}</p>
      </div>

      <template #footer>
        <div v-if="etapa === 'carrinho'" class="flex items-center gap-3">
          <div class="shrink-0"><p class="text-xs text-ink-faint">Total</p><p class="text-xl font-semibold text-ink">{{ formatCurrency(cart.total) }}</p></div>
          <button :disabled="!podeAvancar" class="h-14 flex-1 rounded-2xl bg-brand-600 text-base font-semibold text-white active:bg-brand-700 disabled:opacity-50" @click="irParaPagamento">Ir para pagamento</button>
        </div>
        <div v-else-if="etapa === 'pagamento'" class="flex gap-3">
          <button class="h-14 flex-1 rounded-2xl bg-surface font-medium text-ink-soft active:bg-surface-alt" @click="etapa = 'carrinho'">Voltar</button>
          <button :disabled="cart.restante > 0.005 || finalizando" class="h-14 flex-[2] rounded-2xl bg-brand-600 text-base font-semibold text-white active:bg-brand-700 disabled:opacity-50" @click="finalizar">{{ finalizando ? 'Finalizando...' : 'Finalizar venda' }}</button>
        </div>
        <div v-else class="space-y-2.5">
          <a :href="whatsappLink(vendaConcluida?.cliente?.telefone, recibo(vendaConcluida))" target="_blank" rel="noopener" class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] font-semibold text-white active:opacity-90"><MessageCircle :size="20" />Enviar recibo no WhatsApp</a>
          <button class="h-14 w-full rounded-2xl bg-brand-600 font-semibold text-white active:bg-brand-700" @click="novaVenda">Nova venda</button>
        </div>
      </template>
    </BottomSheet>
  </div>
</template>
