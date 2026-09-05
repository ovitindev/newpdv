import { defineStore } from 'pinia'

let nextPaymentId = 1

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    discountPercent: 0,
    cliente: null,
    vendedor: null,
    payments: [], // [{ id, method, valor, parcelas }]
  }),
  getters: {
    itemCount: (state) => state.items.reduce((sum, item) => sum + item.quantidade, 0),
    subtotal: (state) => state.items.reduce((sum, item) => sum + item.preco * item.quantidade, 0),
    discountValue() {
      return this.subtotal * (this.discountPercent / 100)
    },
    total() {
      return Math.max(this.subtotal - this.discountValue, 0)
    },
    totalPago: (state) => state.payments.reduce((sum, p) => sum + p.valor, 0),
    restante() {
      return Math.max(Math.round((this.total - this.totalPago) * 100) / 100, 0)
    },
  },
  actions: {
    addItem(produto, quantidade = 1) {
      const existing = this.items.find((item) => item.id === produto.id)
      if (existing) {
        existing.quantidade += quantidade
        return
      }
      this.items.push({
        id: produto.id,
        nome: produto.nome,
        preco: produto.preco,
        codigo: produto.codigo,
        quantidade: Math.max(Number(quantidade) || 1, 0.001),
        avulso: produto.avulso ?? false,
      })
    },
    addAvulso({ nome, preco, quantidade = 1 }) {
      this.items.push({
        id: `avulso-${Date.now()}`,
        nome: nome || 'Item avulso',
        preco: Number(preco) || 0,
        codigo: '—',
        quantidade: Math.max(Number(quantidade) || 1, 0.001),
        avulso: true,
      })
    },
    incrementItem(id) {
      const item = this.items.find((entry) => entry.id === id)
      if (item) item.quantidade += 1
    },
    decrementItem(id) {
      const item = this.items.find((entry) => entry.id === id)
      if (!item) return
      item.quantidade -= 1
      if (item.quantidade <= 0) this.removeItem(id)
    },
    setQuantity(id, quantidade) {
      const item = this.items.find((entry) => entry.id === id)
      if (!item) return
      const value = Number(quantidade)
      if (!Number.isFinite(value) || value <= 0) {
        this.removeItem(id)
        return
      }
      item.quantidade = value
    },
    removeItem(id) {
      this.items = this.items.filter((item) => item.id !== id)
    },
    setDiscount(percent) {
      this.discountPercent = Math.min(Math.max(Number(percent) || 0, 0), 100)
    },
    setCliente(cliente) {
      this.cliente = cliente
    },
    setVendedor(vendedor) {
      this.vendedor = vendedor
    },
    addPayment({ method, valor, parcelas = 1 }) {
      this.payments.push({ id: nextPaymentId++, method, valor: Number(valor) || 0, parcelas: Number(parcelas) || 1 })
    },
    removePayment(id) {
      this.payments = this.payments.filter((p) => p.id !== id)
    },
    clearPayments() {
      this.payments = []
    },
    clear() {
      this.items = []
      this.discountPercent = 0
      this.cliente = null
      this.vendedor = null
      this.payments = []
    },
  },
})
