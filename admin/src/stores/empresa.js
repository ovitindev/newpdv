import { defineStore } from 'pinia'
import { empresa } from '@/data/mock/empresa'

export const useEmpresaStore = defineStore('empresa', {
  state: () => ({
    dados: { ...empresa },
    logoUrl: null,
  }),
  actions: {
    atualizar(dados) {
      this.dados = { ...this.dados, ...dados }
    },
    setLogo(url) {
      this.logoUrl = url
    },
  },
})
