export const produtos = [
  { id: 1, codigo: '7891000100103', nome: 'Coca-Cola 2L', categoria: 'Bebidas', marca: 'Coca-Cola', preco: 9.0, estoque: 96, status: 'ativo' },
  { id: 2, codigo: '7891000100202', nome: 'Coca-Cola Lata 350ml', categoria: 'Bebidas', marca: 'Coca-Cola', preco: 4.5, estoque: 210, status: 'ativo' },
  { id: 3, codigo: '7891234500011', nome: 'Arroz Tio João 5kg', categoria: 'Mercearia', marca: 'Tio João', preco: 23.0, estoque: 41, status: 'ativo' },
  { id: 4, codigo: '7891234500028', nome: 'Feijão Carioca 1kg', categoria: 'Mercearia', marca: 'Tio João', preco: 8.9, estoque: 3, status: 'ativo' },
  { id: 5, codigo: '7896098700123', nome: 'Detergente Ypê 500ml', categoria: 'Limpeza', marca: 'Ypê', preco: 3.0, estoque: 8, status: 'ativo' },
  { id: 6, codigo: '7896098700456', nome: 'Sabão em Pó Ypê 1kg', categoria: 'Limpeza', marca: 'Ypê', preco: 14.9, estoque: 27, status: 'ativo' },
  { id: 7, codigo: '7891700100789', nome: 'Café Pilão 500g', categoria: 'Mercearia', marca: 'Pilão', preco: 15.0, estoque: 63, status: 'ativo' },
  { id: 8, codigo: '7891150000111', nome: 'Sabonete Dove 90g', categoria: 'Higiene Pessoal', marca: 'Dove', preco: 4.0, estoque: 0, status: 'ativo' },
  { id: 9, codigo: '7891150000234', nome: 'Shampoo Dove 400ml', categoria: 'Higiene Pessoal', marca: 'Dove', preco: 19.9, estoque: 34, status: 'ativo' },
  { id: 10, codigo: 'APL-IP15-128', nome: 'iPhone 15 128GB', categoria: 'Eletrônicos', marca: 'Apple', preco: 5899.0, estoque: 4, status: 'ativo' },
  { id: 11, codigo: 'APL-APP2', nome: 'AirPods Pro 2', categoria: 'Eletrônicos', marca: 'Apple', preco: 1899.0, estoque: 2, status: 'ativo' },
  { id: 12, codigo: 'SAM-A54-128', nome: 'Galaxy A54 128GB', categoria: 'Eletrônicos', marca: 'Samsung', preco: 1799.0, estoque: 0, status: 'inativo' },
  { id: 13, codigo: '7891000300456', nome: 'Nescau 400g', categoria: 'Mercearia', marca: 'Nestlé', preco: 11.9, estoque: 58, status: 'ativo' },
  { id: 14, codigo: '7891000300789', nome: 'Leite Ninho 400g', categoria: 'Mercearia', marca: 'Nestlé', preco: 22.5, estoque: 19, status: 'ativo' },
  { id: 15, codigo: '7896098701234', nome: 'Água Sanitária Ypê 1L', categoria: 'Limpeza', marca: 'Ypê', preco: 5.5, estoque: 46, status: 'ativo' },
  { id: 16, codigo: '7891234501234', nome: 'Macarrão Espaguete 500g', categoria: 'Mercearia', marca: 'Tio João', preco: 4.9, estoque: 88, status: 'ativo' },
  { id: 17, codigo: '7891000400123', nome: 'Coca-Cola Zero 2L', categoria: 'Bebidas', marca: 'Coca-Cola', preco: 9.0, estoque: 5, status: 'ativo' },
  { id: 18, codigo: '7891150000567', nome: 'Desodorante Dove 150ml', categoria: 'Higiene Pessoal', marca: 'Dove', preco: 13.9, estoque: 22, status: 'ativo' },
  { id: 19, codigo: 'HF-TOM-KG', nome: 'Tomate kg', categoria: 'Hortifruti', marca: '—', preco: 7.99, estoque: 34, status: 'ativo' },
  { id: 20, codigo: 'HF-BAN-KG', nome: 'Banana Prata kg', categoria: 'Hortifruti', marca: '—', preco: 5.49, estoque: 41, status: 'ativo' },
  { id: 21, codigo: '7891000500123', nome: 'Suco Del Valle 1L', categoria: 'Bebidas', marca: 'Coca-Cola', preco: 7.5, estoque: 0, status: 'ativo' },
  { id: 22, codigo: '7891700200456', nome: 'Café Pilão 250g', categoria: 'Mercearia', marca: 'Pilão', preco: 8.9, estoque: 71, status: 'ativo' },
  { id: 23, codigo: 'PAP-CAD-100', nome: 'Caderno Universitário 100fl', categoria: 'Papelaria', marca: '—', preco: 12.9, estoque: 15, status: 'inativo' },
  { id: 24, codigo: 'PAP-CAN-BIC', nome: 'Caneta Esferográfica Azul', categoria: 'Papelaria', marca: '—', preco: 1.9, estoque: 120, status: 'inativo' },
  { id: 25, codigo: 'SAM-BUDS2', nome: 'Galaxy Buds2', categoria: 'Eletrônicos', marca: 'Samsung', preco: 599.0, estoque: 6, status: 'ativo' },
  { id: 26, codigo: '7896098702345', nome: 'Amaciante Ypê 2L', categoria: 'Limpeza', marca: 'Ypê', preco: 12.5, estoque: 9, status: 'ativo' },
]

export const productStatusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'ativo', label: 'Ativo' },
  { value: 'inativo', label: 'Inativo' },
]

export const stockStatusOptions = [
  { value: '', label: 'Todo o estoque' },
  { value: 'disponivel', label: 'Disponível' },
  { value: 'baixo', label: 'Estoque baixo (< 10)' },
  { value: 'esgotado', label: 'Sem estoque' },
]
