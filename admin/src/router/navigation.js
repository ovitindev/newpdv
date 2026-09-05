/**
 * Fonte única da navegação: usada pela Sidebar para renderizar o menu e
 * espelhada nas rotas em `router/index.js`. Manter os `to` em sincronia
 * com os `path` das rotas.
 */
export const navigation = [
  {
    label: 'Dashboard',
    icon: 'LayoutDashboard',
    to: '/',
  },
  {
    label: 'PDV',
    icon: 'ShoppingCart',
    children: [
      { label: 'Nova Venda', icon: 'CirclePlus', to: '/pdv/nova-venda' },
      { label: 'Vendas', icon: 'Receipt', to: '/pdv/vendas' },
      { label: 'Devoluções', icon: 'Undo2', to: '/pdv/devolucoes' },
    ],
  },
  {
    label: 'Produtos',
    icon: 'Package',
    children: [
      { label: 'Produtos', icon: 'Boxes', to: '/produtos' },
      { label: 'Categorias', icon: 'Tags', to: '/produtos/categorias' },
      { label: 'Marcas', icon: 'BadgeCheck', to: '/produtos/marcas' },
      { label: 'Estoque', icon: 'Warehouse', to: '/produtos/estoque' },
      { label: 'Movimentações', icon: 'ArrowLeftRight', to: '/produtos/movimentacoes' },
    ],
  },
  {
    label: 'Clientes',
    icon: 'Users',
    to: '/clientes',
  },
  {
    label: 'Fornecedores',
    icon: 'Truck',
    to: '/fornecedores',
  },
  {
    label: 'Financeiro',
    icon: 'Wallet',
    children: [
      { label: 'Contas a Pagar', icon: 'ArrowUpCircle', to: '/financeiro/contas-a-pagar' },
      { label: 'Contas a Receber', icon: 'ArrowDownCircle', to: '/financeiro/contas-a-receber' },
      { label: 'Fluxo de Caixa', icon: 'LineChart', to: '/financeiro/fluxo-de-caixa' },
    ],
  },
  {
    label: 'Fiscal',
    icon: 'FileText',
    children: [
      { label: 'NFC-e', icon: 'Receipt', to: '/fiscal/nfce' },
      { label: 'NF-e', icon: 'FileCheck2', to: '/fiscal/nfe' },
      { label: 'Notas Emitidas', icon: 'FileOutput', to: '/fiscal/notas-emitidas' },
      { label: 'Notas Canceladas', icon: 'FileX2', to: '/fiscal/notas-canceladas' },
      { label: 'Contingência', icon: 'ShieldAlert', to: '/fiscal/contingencia' },
    ],
  },
  {
    label: 'Relatórios',
    icon: 'BarChart3',
    children: [
      { label: 'Vendas', icon: 'TrendingUp', to: '/relatorios/vendas' },
      { label: 'Produtos', icon: 'Package', to: '/relatorios/produtos' },
      { label: 'Estoque', icon: 'Warehouse', to: '/relatorios/estoque' },
      { label: 'Financeiro', icon: 'Wallet', to: '/relatorios/financeiro' },
      { label: 'Fiscal', icon: 'FileText', to: '/relatorios/fiscal' },
    ],
  },
  {
    label: 'Configurações',
    icon: 'Settings',
    children: [
      { label: 'Empresa', icon: 'Building2', to: '/configuracoes/empresa' },
      { label: 'Usuários', icon: 'UserCog', to: '/configuracoes/usuarios' },
      { label: 'Vendedores', icon: 'Contact', to: '/configuracoes/vendedores' },
      { label: 'Permissões', icon: 'Lock', to: '/configuracoes/permissoes' },
      { label: 'Certificado A1', icon: 'KeySquare', to: '/configuracoes/certificado-a1' },
      { label: 'Configurações Fiscais', icon: 'FileCog', to: '/configuracoes/fiscais' },
    ],
  },
]
