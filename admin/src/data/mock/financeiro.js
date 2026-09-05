export const financeSummary = {
  saldoDisponivel: 24680.5,
  entradas: 42350.0,
  saidas: 17669.5,
  contasReceber: 8920.0,
  contasPagar: 6340.0,
}

export const cashFlow = {
  labels: ['24/08', '25/08', '26/08', '27/08', '28/08', '29/08', '30/08'],
  entradas: [4200, 5100, 4800, 6300, 5900, 7400, 8570],
  saidas: [1800, 2200, 1500, 2600, 2100, 3400, 4069],
}

export const contasPagar = [
  { id: 1, descricao: 'Fornecedor Ambev - Pedido 4821', categoria: 'Fornecedores', valor: 3200.0, vencimento: '2026-09-03', status: 'pendente' },
  { id: 2, descricao: 'Aluguel do ponto comercial', categoria: 'Fixas', valor: 2800.0, vencimento: '2026-09-05', status: 'pendente' },
  { id: 3, descricao: 'Energia elétrica - Setembro', categoria: 'Utilidades', valor: 540.0, vencimento: '2026-09-08', status: 'pendente' },
  { id: 4, descricao: 'Fornecedor Ypê - Pedido 4790', categoria: 'Fornecedores', valor: 1120.0, vencimento: '2026-08-29', status: 'atrasado' },
  { id: 5, descricao: 'Internet + telefonia', categoria: 'Utilidades', valor: 220.0, vencimento: '2026-08-25', status: 'pago' },
  { id: 6, descricao: 'Contador - honorários mensais', categoria: 'Serviços', valor: 650.0, vencimento: '2026-09-10', status: 'pendente' },
]

export const contasReceber = [
  { id: 1, descricao: 'Venda #1041 - Padaria Bom Sabor', categoria: 'Vendas a prazo', valor: 1290.0, vencimento: '2026-09-05', status: 'pendente' },
  { id: 2, descricao: 'Venda #1038 - Comércio Estrela', categoria: 'Vendas a prazo', valor: 2340.0, vencimento: '2026-09-02', status: 'pendente' },
  { id: 3, descricao: 'Venda #1029 - Carlos Eduardo Alves', categoria: 'Vendas a prazo', valor: 445.5, vencimento: '2026-08-28', status: 'atrasado' },
  { id: 4, descricao: 'Venda #1017 - Consumidor Final', categoria: 'Cartão (recebível)', valor: 890.0, vencimento: '2026-08-24', status: 'recebido' },
  { id: 5, descricao: 'Venda #1005 - Comércio Estrela', categoria: 'Vendas a prazo', valor: 3954.5, vencimento: '2026-09-12', status: 'pendente' },
]

export const financeStatusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'pendente', label: 'Pendente' },
  { value: 'atrasado', label: 'Atrasado' },
  { value: 'pago', label: 'Pago' },
  { value: 'recebido', label: 'Recebido' },
]
