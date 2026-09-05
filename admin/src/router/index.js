import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { title: 'Entrar' },
  },
  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { title: 'Dashboard', breadcrumb: ['Dashboard'] },
      },
      // PDV
      {
        path: 'pdv/nova-venda',
        name: 'pdv-nova-venda',
        component: () => import('@/views/pdv/NovaVendaView.vue'),
        meta: { title: 'Nova Venda', breadcrumb: ['PDV', 'Nova Venda'], bare: true },
      },
      {
        path: 'pdv/vendas',
        name: 'pdv-vendas',
        component: () => import('@/views/pdv/VendasView.vue'),
        meta: { title: 'Vendas', breadcrumb: ['PDV', 'Vendas'] },
      },
      {
        path: 'pdv/devolucoes',
        name: 'pdv-devolucoes',
        component: () => import('@/views/pdv/DevolucoesView.vue'),
        meta: { title: 'Devoluções', breadcrumb: ['PDV', 'Devoluções'] },
      },
      // Produtos
      {
        path: 'produtos',
        name: 'produtos',
        component: () => import('@/views/produtos/ProdutosView.vue'),
        meta: { title: 'Produtos', breadcrumb: ['Produtos', 'Produtos'] },
      },
      {
        path: 'produtos/categorias',
        name: 'produtos-categorias',
        component: () => import('@/views/produtos/CategoriasView.vue'),
        meta: { title: 'Categorias', breadcrumb: ['Produtos', 'Categorias'] },
      },
      {
        path: 'produtos/marcas',
        name: 'produtos-marcas',
        component: () => import('@/views/produtos/MarcasView.vue'),
        meta: { title: 'Marcas', breadcrumb: ['Produtos', 'Marcas'] },
      },
      {
        path: 'produtos/estoque',
        name: 'produtos-estoque',
        component: () => import('@/views/produtos/EstoqueView.vue'),
        meta: { title: 'Estoque', breadcrumb: ['Produtos', 'Estoque'] },
      },
      {
        path: 'produtos/movimentacoes',
        name: 'produtos-movimentacoes',
        component: () => import('@/views/produtos/MovimentacoesView.vue'),
        meta: { title: 'Movimentações', breadcrumb: ['Produtos', 'Movimentações'] },
      },
      // Clientes / Fornecedores
      {
        path: 'clientes',
        name: 'clientes',
        component: () => import('@/views/ClientesView.vue'),
        meta: { title: 'Clientes', breadcrumb: ['Clientes'] },
      },
      {
        path: 'fornecedores',
        name: 'fornecedores',
        component: () => import('@/views/FornecedoresView.vue'),
        meta: { title: 'Fornecedores', breadcrumb: ['Fornecedores'] },
      },
      // Financeiro
      {
        path: 'financeiro/contas-a-pagar',
        name: 'financeiro-contas-a-pagar',
        component: () => import('@/views/financeiro/ContasPagarView.vue'),
        meta: { title: 'Contas a Pagar', breadcrumb: ['Financeiro', 'Contas a Pagar'] },
      },
      {
        path: 'financeiro/contas-a-receber',
        name: 'financeiro-contas-a-receber',
        component: () => import('@/views/financeiro/ContasReceberView.vue'),
        meta: { title: 'Contas a Receber', breadcrumb: ['Financeiro', 'Contas a Receber'] },
      },
      {
        path: 'financeiro/fluxo-de-caixa',
        name: 'financeiro-fluxo-de-caixa',
        component: () => import('@/views/financeiro/FluxoCaixaView.vue'),
        meta: { title: 'Fluxo de Caixa', breadcrumb: ['Financeiro', 'Fluxo de Caixa'] },
      },
      // Fiscal
      {
        path: 'fiscal/nfce',
        name: 'fiscal-nfce',
        component: () => import('@/views/fiscal/NFCeView.vue'),
        meta: { title: 'NFC-e', breadcrumb: ['Fiscal', 'NFC-e'] },
      },
      {
        path: 'fiscal/nfe',
        name: 'fiscal-nfe',
        component: () => import('@/views/fiscal/NFeView.vue'),
        meta: { title: 'NF-e', breadcrumb: ['Fiscal', 'NF-e'] },
      },
      {
        path: 'fiscal/notas-emitidas',
        name: 'fiscal-notas-emitidas',
        component: () => import('@/views/fiscal/NotasEmitidasView.vue'),
        meta: { title: 'Notas Emitidas', breadcrumb: ['Fiscal', 'Notas Emitidas'] },
      },
      {
        path: 'fiscal/notas-canceladas',
        name: 'fiscal-notas-canceladas',
        component: () => import('@/views/fiscal/NotasCanceladasView.vue'),
        meta: { title: 'Notas Canceladas', breadcrumb: ['Fiscal', 'Notas Canceladas'] },
      },
      {
        path: 'fiscal/contingencia',
        name: 'fiscal-contingencia',
        component: () => import('@/views/fiscal/ContingenciaView.vue'),
        meta: { title: 'Contingência', breadcrumb: ['Fiscal', 'Contingência'] },
      },
      // Relatórios
      {
        path: 'relatorios/vendas',
        name: 'relatorios-vendas',
        component: () => import('@/views/relatorios/VendasReportView.vue'),
        meta: { title: 'Relatório de Vendas', breadcrumb: ['Relatórios', 'Vendas'] },
      },
      {
        path: 'relatorios/produtos',
        name: 'relatorios-produtos',
        component: () => import('@/views/relatorios/ProdutosReportView.vue'),
        meta: { title: 'Relatório de Produtos', breadcrumb: ['Relatórios', 'Produtos'] },
      },
      {
        path: 'relatorios/estoque',
        name: 'relatorios-estoque',
        component: () => import('@/views/relatorios/EstoqueReportView.vue'),
        meta: { title: 'Relatório de Estoque', breadcrumb: ['Relatórios', 'Estoque'] },
      },
      {
        path: 'relatorios/financeiro',
        name: 'relatorios-financeiro',
        component: () => import('@/views/relatorios/FinanceiroReportView.vue'),
        meta: { title: 'Relatório Financeiro', breadcrumb: ['Relatórios', 'Financeiro'] },
      },
      {
        path: 'relatorios/fiscal',
        name: 'relatorios-fiscal',
        component: () => import('@/views/relatorios/FiscalReportView.vue'),
        meta: { title: 'Relatório Fiscal', breadcrumb: ['Relatórios', 'Fiscal'] },
      },
      // Configurações
      {
        path: 'configuracoes/empresa',
        name: 'configuracoes-empresa',
        component: () => import('@/views/configuracoes/EmpresaView.vue'),
        meta: { title: 'Empresa', breadcrumb: ['Configurações', 'Empresa'] },
      },
      {
        path: 'configuracoes/usuarios',
        name: 'configuracoes-usuarios',
        component: () => import('@/views/configuracoes/UsuariosView.vue'),
        meta: { title: 'Usuários', breadcrumb: ['Configurações', 'Usuários'] },
      },
      {
        path: 'configuracoes/vendedores',
        name: 'configuracoes-vendedores',
        component: () => import('@/views/configuracoes/VendedoresView.vue'),
        meta: { title: 'Vendedores', breadcrumb: ['Configurações', 'Vendedores'] },
      },
      {
        path: 'configuracoes/permissoes',
        name: 'configuracoes-permissoes',
        component: () => import('@/views/configuracoes/PermissoesView.vue'),
        meta: { title: 'Permissões', breadcrumb: ['Configurações', 'Permissões'] },
      },
      {
        path: 'configuracoes/certificado-a1',
        name: 'configuracoes-certificado-a1',
        component: () => import('@/views/configuracoes/CertificadoA1View.vue'),
        meta: { title: 'Certificado A1', breadcrumb: ['Configurações', 'Certificado A1'] },
      },
      {
        path: 'configuracoes/fiscais',
        name: 'configuracoes-fiscais',
        component: () => import('@/views/configuracoes/ConfigFiscalView.vue'),
        meta: { title: 'Configurações Fiscais', breadcrumb: ['Configurações', 'Configurações Fiscais'] },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Página não encontrada' },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (!authStore.ready) {
    await authStore.fetchMe()
  }

  if (to.name !== 'login' && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }
})

router.afterEach((to) => {
  const base = 'NovaPDV'
  document.title = to.meta?.title ? `${to.meta.title} · ${base}` : base
})
