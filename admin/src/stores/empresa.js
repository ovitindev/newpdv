import { defineStore } from 'pinia'
import { empresa } from '@/data/mock/empresa'
import { http } from '@/services/http'
import { getEmpresa, updateEmpresa } from '@/services/configuracoesService'

function fromApi(data) {
  return {
    razaoSocial: data.razao_social ?? '',
    nomeFantasia: data.nome_fantasia ?? data.razao_social ?? '',
    cnpj: data.cnpj ?? '',
    inscricaoEstadual: data.inscricao_estadual ?? '',
    regimeTributario: data.regime_tributario ?? '',
    telefone: data.telefone ?? '',
    email: data.email ?? '',
    endereco: {
      logradouro: data.logradouro ?? '',
      bairro: data.bairro ?? '',
      cidade: data.cidade ?? '',
      uf: data.uf ?? '',
      cep: data.cep ?? '',
    },
  }
}

function toApi(dados) {
  return {
    razao_social: dados.razaoSocial,
    nome_fantasia: dados.nomeFantasia,
    cnpj: dados.cnpj,
    inscricao_estadual: dados.inscricaoEstadual || null,
    regime_tributario: dados.regimeTributario || null,
    telefone: dados.telefone || null,
    email: dados.email || null,
    logradouro: dados.endereco?.logradouro || null,
    bairro: dados.endereco?.bairro || null,
    cidade: dados.endereco?.cidade || null,
    uf: dados.endereco?.uf || null,
    cep: dados.endereco?.cep || null,
  }
}

export const useEmpresaStore = defineStore('empresa', {
  state: () => ({
    dados: { ...empresa },
    logoUrl: null,
  }),
  actions: {
    async carregar() {
      try {
        const data = await getEmpresa()
        this.dados = fromApi(data)
        if (data.logo_path) this.logoUrl = new URL(`/storage/${data.logo_path}`, new URL(http.defaults.baseURL, window.location.href)).href
      } catch {
        // mantém os dados atuais se a API estiver indisponível
      }
    },
    async atualizar(dados) {
      const data = await updateEmpresa(toApi({ ...this.dados, ...dados }))
      this.dados = fromApi(data)
    },
    setLogo(url) {
      this.logoUrl = url
    },
  },
})
