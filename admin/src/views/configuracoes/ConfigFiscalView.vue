<script setup>
import { onMounted, ref } from 'vue'
import { FileCog, Save } from '@lucide/vue'
import { getConfigFiscal } from '@/services/configuracoesService'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'
import Alert from '@/components/ui/Alert.vue'
import Skeleton from '@/components/ui/Skeleton.vue'

const toast = useToastStore()
const loading = ref(true)
const form = ref(null)

onMounted(async () => {
  form.value = await getConfigFiscal()
  loading.value = false
})

const ambienteOptions = [
  { value: 'producao', label: 'Produção' },
  { value: 'homologacao', label: 'Homologação (testes)' },
]

function submit() {
  toast.success('Configurações fiscais salvas')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><FileCog :size="20" /></span>
      <div>
        <h1 class="text-xl font-semibold text-ink">Configurações Fiscais</h1>
        <p class="text-sm text-ink-soft mt-1">Parâmetros usados na emissão de NFC-e e NF-e.</p>
      </div>
    </div>

    <Card v-if="loading"><Skeleton height="14rem" rounded="12px" /></Card>

    <template v-else>
      <Alert v-if="form.ambiente === 'homologacao'" variant="warning" title="Ambiente de homologação ativo">
        As notas emitidas neste modo não têm validade fiscal. Use apenas para testes.
      </Alert>

      <Card title="Ambiente de emissão">
        <Select v-model="form.ambiente" label="Ambiente" :options="ambienteOptions" class="max-w-sm" />
      </Card>

      <Card title="NFC-e">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Input v-model="form.serieNfce" label="Série" />
          <Input v-model="form.proximoNumeroNfce" type="number" label="Próximo número" />
          <Input v-model="form.idCsc" label="ID do CSC" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
          <Input v-model="form.csc" type="password" label="Código de Segurança do Contribuinte (CSC)" class="sm:col-span-3" />
        </div>
      </Card>

      <Card title="NF-e">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Input v-model="form.serieNfe" label="Série" />
          <Input v-model="form.proximoNumeroNfe" type="number" label="Próximo número" />
        </div>
      </Card>

      <div class="flex justify-end">
        <Button @click="submit"><template #icon-left><Save :size="15" /></template>Salvar configurações</Button>
      </div>
    </template>
  </div>
</template>
