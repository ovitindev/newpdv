<script setup>
import { computed } from 'vue'
import { useEmpresaStore } from '@/stores/empresa'
import { formatCurrency, formatDateTime } from '@/utils/format'

const props = defineProps({
  venda: { type: Object, required: true },
})

const empresaStore = useEmpresaStore()

const methodLabel = { pix: 'PIX', dinheiro: 'Dinheiro', debito: 'Débito', credito: 'Crédito' }

const empresa = computed(() => empresaStore.dados)

const linhaEndereco = computed(() => {
  const e = empresa.value.endereco ?? {}
  return [e.logradouro, e.bairro].filter(Boolean).join(' - ')
})

const linhaCidade = computed(() => {
  const e = empresa.value.endereco ?? {}
  const cidadeUf = [e.cidade, e.uf].filter(Boolean).join('/')
  return [cidadeUf, e.cep ? `CEP ${e.cep}` : ''].filter(Boolean).join(' - ')
})

const totalUnidades = computed(() => props.venda.itens.reduce((soma, item) => soma + Number(item.quantidade), 0))

const troco = computed(() => {
  const pago = (props.venda.payments ?? []).reduce((soma, p) => soma + Number(p.valor), 0)
  return Math.max(pago - Number(props.venda.total ?? 0), 0)
})
</script>

<template>
  <div class="print-area mx-auto w-full max-w-[340px] bg-white font-mono text-[12px] leading-relaxed text-ink">
    <!-- Cabeçalho da empresa -->
    <div class="flex flex-col items-center gap-0.5 text-center">
      <img v-if="empresaStore.logoUrl" :src="empresaStore.logoUrl" alt="Logo" class="mb-1 h-12 w-auto" />
      <p class="text-sm font-bold uppercase">{{ empresa.nomeFantasia }}</p>
      <p v-if="empresa.razaoSocial && empresa.razaoSocial !== empresa.nomeFantasia" class="text-[11px]">{{ empresa.razaoSocial }}</p>
      <p>CNPJ: {{ empresa.cnpj }}</p>
      <p v-if="empresa.inscricaoEstadual">IE: {{ empresa.inscricaoEstadual }}</p>
      <p v-if="linhaEndereco">{{ linhaEndereco }}</p>
      <p v-if="linhaCidade">{{ linhaCidade }}</p>
      <p v-if="empresa.telefone">Tel: {{ empresa.telefone }}</p>
    </div>

    <div class="my-3 border-t border-dashed border-ink-faint"></div>
    <p class="text-center text-[13px] font-bold uppercase tracking-wider">Recibo de Venda</p>
    <div class="my-3 border-t border-dashed border-ink-faint"></div>

    <!-- Dados da venda -->
    <div class="space-y-0.5">
      <div class="flex justify-between"><span>Venda nº</span><span class="font-bold">{{ String(venda.id).padStart(6, '0') }}</span></div>
      <div class="flex justify-between"><span>Data/Hora</span><span>{{ formatDateTime(venda.data) }}</span></div>
      <div class="flex justify-between"><span>Vendedor</span><span class="text-right">{{ venda.vendedor?.nome ?? 'Não informado' }}</span></div>
      <div class="flex justify-between"><span>Cliente</span><span class="text-right">{{ venda.cliente?.nome ?? 'Consumidor Final' }}</span></div>
      <div v-if="venda.cliente?.documento || venda.cliente?.cpf || venda.cliente?.cnpj" class="flex justify-between">
        <span>CPF/CNPJ</span><span>{{ venda.cliente.documento ?? venda.cliente.cpf ?? venda.cliente.cnpj }}</span>
      </div>
    </div>

    <div class="my-3 border-t border-dashed border-ink-faint"></div>

    <!-- Itens -->
    <div class="grid grid-cols-[1fr_auto] gap-x-2 text-[11px] font-bold uppercase">
      <span>Item</span><span class="text-right">Total</span>
    </div>
    <div class="mt-1 space-y-1.5">
      <div v-for="(item, index) in venda.itens" :key="item.id ?? index">
        <div class="grid grid-cols-[1fr_auto] gap-x-2">
          <span class="break-words">{{ String(index + 1).padStart(2, '0') }} {{ item.nome }}</span>
          <span class="text-right tabular-nums">{{ formatCurrency(item.preco * item.quantidade) }}</span>
        </div>
        <p class="pl-5 text-[11px] text-ink-soft">
          <template v-if="item.codigo && item.codigo !== '—'">Cód. {{ item.codigo }} · </template>{{ item.quantidade }} x {{ formatCurrency(item.preco) }}
        </p>
      </div>
    </div>

    <div class="my-3 border-t border-dashed border-ink-faint"></div>

    <!-- Totais -->
    <div class="space-y-0.5">
      <div class="flex justify-between"><span>Qtd. de itens</span><span>{{ totalUnidades }}</span></div>
      <div class="flex justify-between"><span>Subtotal</span><span class="tabular-nums">{{ formatCurrency(venda.subtotal ?? venda.total) }}</span></div>
      <div v-if="Number(venda.discountValue) > 0" class="flex justify-between">
        <span>Desconto{{ Number(venda.discountPercent) > 0 ? ` (${venda.discountPercent}%)` : '' }}</span>
        <span class="tabular-nums">- {{ formatCurrency(venda.discountValue) }}</span>
      </div>
      <div class="mt-1 flex justify-between border-t border-ink pt-1.5 text-[15px] font-bold">
        <span>TOTAL</span><span class="tabular-nums">{{ formatCurrency(venda.total) }}</span>
      </div>
    </div>

    <div class="my-3 border-t border-dashed border-ink-faint"></div>

    <!-- Pagamento -->
    <p class="mb-1 text-[11px] font-bold uppercase">Forma de pagamento</p>
    <div class="space-y-0.5">
      <div v-for="(payment, index) in venda.payments" :key="payment.id ?? index" class="flex justify-between">
        <span>{{ methodLabel[payment.method] ?? payment.method }}{{ payment.method === 'credito' && payment.parcelas > 1 ? ` em ${payment.parcelas}x de ${formatCurrency(payment.valor / payment.parcelas)}` : '' }}</span>
        <span class="tabular-nums">{{ formatCurrency(payment.valor) }}</span>
      </div>
      <div v-if="troco > 0" class="flex justify-between">
        <span>Troco</span><span class="tabular-nums">{{ formatCurrency(troco) }}</span>
      </div>
    </div>

    <div class="my-3 border-t border-dashed border-ink-faint"></div>

    <div class="space-y-0.5 text-center">
      <p class="font-bold">Obrigado pela preferência!</p>
      <p class="text-[10px] text-ink-soft">Volte sempre</p>
      <p class="mt-2 text-[10px] font-bold uppercase tracking-wide">*** Documento sem valor fiscal ***</p>
      <p class="text-[10px] text-ink-faint">Emitido em {{ formatDateTime(new Date()) }}</p>
    </div>
  </div>
</template>
