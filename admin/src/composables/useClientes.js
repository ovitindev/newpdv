import { ref } from 'vue'
import { getClientes } from '@/services/clientesService'

// Cache compartilhado entre as telas do app: a lista de clientes é usada na
// venda, nos pedidos e na tela de clientes, e só precisa ir ao servidor uma vez.
const clientes = ref([])
let carregado = false
let carregando = null

export function useClientes() {
  async function carregar(forcar = false) {
    if (carregado && !forcar) return clientes.value
    carregando ??= getClientes()
      .then((lista) => {
        clientes.value = lista
        carregado = true
        return lista
      })
      .finally(() => {
        carregando = null
      })
    return carregando
  }

  function upsert(cliente) {
    const i = clientes.value.findIndex((c) => c.id === cliente.id)
    if (i >= 0) clientes.value[i] = cliente
    else clientes.value.unshift(cliente)
  }

  return { clientes, carregar, upsert }
}
