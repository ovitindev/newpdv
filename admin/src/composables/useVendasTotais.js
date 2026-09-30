import { computed, unref } from 'vue'

/** Totais derivados de uma lista de vendas (ref ou computed) — usado na tela de Vendas e no Relatório de Vendas. */
export function useVendasTotais(vendasRef) {
  const vendasConcluidas = computed(() => unref(vendasRef).filter((venda) => venda.status === 'concluida'))
  const totalVendido = computed(() => vendasConcluidas.value.reduce((sum, venda) => sum + venda.total, 0))
  const totalFrete = computed(() => vendasConcluidas.value.reduce((sum, venda) => sum + (venda.valorFrete || 0), 0))
  const quantidadeVendas = computed(() => vendasConcluidas.value.length)
  const ticketMedio = computed(() => (quantidadeVendas.value ? totalVendido.value / quantidadeVendas.value : 0))

  return { vendasConcluidas, totalVendido, totalFrete, quantidadeVendas, ticketMedio }
}
