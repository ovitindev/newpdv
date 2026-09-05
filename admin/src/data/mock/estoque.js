export const movimentacoes = [
  { id: 1, produto: 'Coca-Cola 2L', tipo: 'entrada', quantidade: 120, motivo: 'Compra - Fornecedor Ambev', data: '2026-08-30T09:12:00', responsavel: 'Victor Hugo' },
  { id: 2, produto: 'Detergente Ypê 500ml', tipo: 'saida', quantidade: 4, motivo: 'Venda #1046', data: '2026-08-30T10:41:00', responsavel: 'PDV' },
  { id: 3, produto: 'Feijão Carioca 1kg', tipo: 'saida', quantidade: 2, motivo: 'Venda #1044', data: '2026-08-30T08:55:00', responsavel: 'PDV' },
  { id: 4, produto: 'iPhone 15 128GB', tipo: 'entrada', quantidade: 4, motivo: 'Compra - Fornecedor Ingram', data: '2026-08-29T16:20:00', responsavel: 'Victor Hugo' },
  { id: 5, produto: 'Sabonete Dove 90g', tipo: 'saida', quantidade: 12, motivo: 'Venda #1032', data: '2026-08-29T14:03:00', responsavel: 'PDV' },
  { id: 6, produto: 'Amaciante Ypê 2L', tipo: 'ajuste', quantidade: -3, motivo: 'Avaria em estoque', data: '2026-08-28T18:47:00', responsavel: 'Ana Souza' },
  { id: 7, produto: 'Café Pilão 500g', tipo: 'entrada', quantidade: 60, motivo: 'Compra - Fornecedor Pilão', data: '2026-08-28T11:10:00', responsavel: 'Victor Hugo' },
  { id: 8, produto: 'Suco Del Valle 1L', tipo: 'saida', quantidade: 18, motivo: 'Venda balcão', data: '2026-08-27T19:32:00', responsavel: 'PDV' },
]

export const movementTypeOptions = [
  { value: '', label: 'Todos os tipos' },
  { value: 'entrada', label: 'Entrada' },
  { value: 'saida', label: 'Saída' },
  { value: 'ajuste', label: 'Ajuste' },
]
