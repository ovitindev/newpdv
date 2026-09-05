export const currentUser = {
  id: 1,
  nome: 'Victor Hugo',
  cargo: 'Administrador',
  email: 'victor@novapdv.com.br',
  avatarUrl: null,
  iniciais: 'VH',
}

export const usuarios = [
  { id: 1, nome: 'Victor Hugo', email: 'victor@novapdv.com.br', cargo: 'Administrador', ultimoAcesso: '2026-08-30T14:40:00', status: 'ativo' },
  { id: 2, nome: 'Ana Souza', email: 'ana.souza@novapdv.com.br', cargo: 'Operador de Caixa', ultimoAcesso: '2026-08-30T14:10:00', status: 'ativo' },
  { id: 3, nome: 'Bruno Ferreira', email: 'bruno.ferreira@novapdv.com.br', cargo: 'Estoquista', ultimoAcesso: '2026-08-29T18:22:00', status: 'ativo' },
  { id: 4, nome: 'Carla Menezes', email: 'carla.menezes@novapdv.com.br', cargo: 'Financeiro', ultimoAcesso: '2026-08-25T09:15:00', status: 'inativo' },
]

export const perfis = [
  {
    id: 1,
    nome: 'Administrador',
    descricao: 'Acesso completo a todos os módulos do sistema.',
    permissoes: { pdv: true, produtos: true, financeiro: true, fiscal: true, relatorios: true, configuracoes: true },
  },
  {
    id: 2,
    nome: 'Operador de Caixa',
    descricao: 'Acesso à tela de vendas e consulta de produtos.',
    permissoes: { pdv: true, produtos: true, financeiro: false, fiscal: false, relatorios: false, configuracoes: false },
  },
  {
    id: 3,
    nome: 'Estoquista',
    descricao: 'Gestão de produtos, categorias e movimentações de estoque.',
    permissoes: { pdv: false, produtos: true, financeiro: false, fiscal: false, relatorios: true, configuracoes: false },
  },
  {
    id: 4,
    nome: 'Financeiro',
    descricao: 'Acesso ao módulo financeiro e relatórios.',
    permissoes: { pdv: false, produtos: false, financeiro: true, fiscal: true, relatorios: true, configuracoes: false },
  },
]
