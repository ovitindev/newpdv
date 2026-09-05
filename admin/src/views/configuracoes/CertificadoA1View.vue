<script setup>
import { onMounted, ref } from 'vue'
import { KeySquare, UploadCloud, ShieldCheck, FileKey } from '@lucide/vue'
import { getCertificadoA1 } from '@/services/configuracoesService'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import Alert from '@/components/ui/Alert.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import { formatDate } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const certificado = ref(null)
const selectedFile = ref(null)
const senha = ref('')
const fileInput = ref(null)

onMounted(async () => {
  certificado.value = await getCertificadoA1()
  loading.value = false
})

function handleFileChange(event) {
  const file = event.target.files?.[0]
  if (file) selectedFile.value = file
}

function handleDrop(event) {
  event.preventDefault()
  const file = event.dataTransfer.files?.[0]
  if (file) selectedFile.value = file
}

function submit() {
  if (!selectedFile.value || !senha.value) {
    toast.error('Preencha o certificado e a senha', 'Selecione o arquivo .pfx e informe a senha para continuar.')
    return
  }
  certificado.value = {
    ...certificado.value,
    arquivo: selectedFile.value.name,
    validoAte: '2027-04-12',
    status: 'valido',
  }
  selectedFile.value = null
  senha.value = ''
  toast.success('Certificado atualizado', 'O certificado A1 foi validado e salvo com sucesso.')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><KeySquare :size="20" /></span>
      <div>
        <h1 class="text-xl font-semibold text-ink">Certificado A1</h1>
        <p class="text-sm text-ink-soft mt-1">Certificado digital usado para assinar as notas fiscais.</p>
      </div>
    </div>

    <Card v-if="loading" title="Certificado atual">
      <Skeleton height="6rem" rounded="12px" />
    </Card>

    <Card v-else title="Certificado atual">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-xl bg-surface p-4">
        <div class="flex items-center gap-3">
          <span class="flex size-11 items-center justify-center rounded-xl bg-white text-brand-600"><FileKey :size="20" /></span>
          <div>
            <p class="text-sm font-medium text-ink">{{ certificado.arquivo }}</p>
            <p class="text-xs text-ink-soft mt-0.5">{{ certificado.titular }}</p>
            <p class="text-xs text-ink-faint mt-0.5">Emitido por {{ certificado.emissor }} · válido até {{ formatDate(certificado.validoAte) }}</p>
          </div>
        </div>
        <Badge variant="success" dot>
          <ShieldCheck :size="12" /> Válido
        </Badge>
      </div>
    </Card>

    <Card title="Enviar novo certificado" subtitle="Formatos aceitos: .pfx e .p12">
      <div class="space-y-4">
        <div
          class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-border p-8 text-center transition-colors hover:border-brand-300 cursor-pointer"
          @click="fileInput.click()"
          @dragover.prevent
          @drop="handleDrop"
        >
          <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><UploadCloud :size="20" /></span>
          <p class="text-sm font-medium text-ink">
            {{ selectedFile ? selectedFile.name : 'Arraste o arquivo .pfx ou clique para selecionar' }}
          </p>
          <p class="text-xs text-ink-faint">O arquivo é armazenado de forma criptografada.</p>
          <input ref="fileInput" type="file" accept=".pfx,.p12" class="hidden" @change="handleFileChange" />
        </div>

        <Input v-model="senha" type="password" label="Senha do certificado" placeholder="••••••••" />

        <Alert variant="warning" title="Atenção">
          O certificado A1 precisa ser renovado anualmente junto a uma Autoridade Certificadora. Notas não podem ser
          emitidas com um certificado vencido.
        </Alert>

        <div class="flex justify-end">
          <Button @click="submit">Salvar certificado</Button>
        </div>
      </div>
    </Card>
  </div>
</template>
