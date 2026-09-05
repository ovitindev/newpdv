<script setup>
import { ref } from 'vue'
import { Building2, Save, UploadCloud, Trash2 } from '@lucide/vue'
import { useEmpresaStore } from '@/stores/empresa'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'

const toast = useToastStore()
const empresaStore = useEmpresaStore()

const form = ref({ ...empresaStore.dados, endereco: { ...empresaStore.dados.endereco } })
const logoInput = ref(null)

const regimeOptions = [
  { value: 'Simples Nacional', label: 'Simples Nacional' },
  { value: 'Lucro Presumido', label: 'Lucro Presumido' },
  { value: 'Lucro Real', label: 'Lucro Real' },
]

function handleLogoChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => empresaStore.setLogo(reader.result)
  reader.readAsDataURL(file)
}

function removeLogo() {
  empresaStore.setLogo(null)
}

function submit() {
  empresaStore.atualizar(form.value)
  toast.success('Dados da empresa atualizados', 'As informações serão usadas em notas e recibos.')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><Building2 :size="20" /></span>
      <div>
        <h1 class="text-xl font-semibold text-ink">Empresa</h1>
        <p class="text-sm text-ink-soft mt-1">Dados cadastrais, fiscais e visuais usados na emissão de notas e recibos.</p>
      </div>
    </div>

    <Card title="Logo da empresa" subtitle="Aparece no cabeçalho de notas fiscais e recibos emitidos no PDV">
      <div class="flex flex-col sm:flex-row items-center gap-5">
        <div
          class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-border bg-surface"
        >
          <img v-if="empresaStore.logoUrl" :src="empresaStore.logoUrl" alt="Logo da empresa" class="size-full object-contain p-2" />
          <Building2 v-else :size="26" class="text-ink-faint" />
        </div>
        <div class="flex-1 space-y-2">
          <div class="flex flex-wrap gap-2">
            <Button size="sm" variant="outline" @click="logoInput.click()">
              <template #icon-left><UploadCloud :size="14" /></template>
              {{ empresaStore.logoUrl ? 'Trocar logo' : 'Enviar logo' }}
            </Button>
            <Button v-if="empresaStore.logoUrl" size="sm" variant="ghost" @click="removeLogo">
              <template #icon-left><Trash2 :size="14" /></template>
              Remover
            </Button>
            <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="handleLogoChange" />
          </div>
          <p class="text-xs text-ink-faint">PNG ou JPG, fundo transparente de preferência. Recomendado 400x400px.</p>
        </div>
      </div>
    </Card>

    <Card title="Dados gerais">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Input v-model="form.razaoSocial" label="Razão social" required />
        <Input v-model="form.nomeFantasia" label="Nome fantasia" />
        <Input v-model="form.cnpj" label="CNPJ" required />
        <Input v-model="form.inscricaoEstadual" label="Inscrição estadual" />
        <Select v-model="form.regimeTributario" label="Regime tributário" :options="regimeOptions" />
        <Input v-model="form.telefone" label="Telefone" />
        <Input v-model="form.email" label="E-mail" class="sm:col-span-2" />
      </div>
    </Card>

    <Card title="Endereço">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Input v-model="form.endereco.logradouro" label="Logradouro" class="sm:col-span-2" />
        <Input v-model="form.endereco.bairro" label="Bairro" />
        <Input v-model="form.endereco.cidade" label="Cidade" />
        <Input v-model="form.endereco.uf" label="UF" />
        <Input v-model="form.endereco.cep" label="CEP" />
      </div>
    </Card>

    <div class="flex justify-end">
      <Button @click="submit"><template #icon-left><Save :size="15" /></template>Salvar alterações</Button>
    </div>
  </div>
</template>
