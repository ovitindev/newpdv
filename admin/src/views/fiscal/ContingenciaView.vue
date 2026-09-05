<script setup>
import { ref } from 'vue'
import { ShieldCheck, ShieldAlert, RefreshCcw } from '@lucide/vue'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Alert from '@/components/ui/Alert.vue'
import Button from '@/components/ui/Button.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

const toast = useToastStore()
const contingenciaAtiva = ref(false)

function toggleContingencia() {
  contingenciaAtiva.value = !contingenciaAtiva.value
  if (contingenciaAtiva.value) {
    toast.error('Contingência ativada', 'As notas serão emitidas em modo offline até a SEFAZ normalizar.')
  } else {
    toast.success('Contingência desativada', 'Emissão normalizada. Notas em contingência serão transmitidas.')
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Contingência</h1>
      <p class="text-sm text-ink-soft mt-1">Situação da comunicação com a SEFAZ e emissão em modo offline.</p>
    </div>

    <Card :padded="true">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
        <div class="flex items-center gap-4">
          <span
            class="flex size-14 items-center justify-center rounded-2xl"
            :class="contingenciaAtiva ? 'bg-danger-soft text-danger' : 'bg-brand-50 text-brand-600'"
          >
            <component :is="contingenciaAtiva ? ShieldAlert : ShieldCheck" :size="28" />
          </span>
          <div>
            <p class="text-base font-semibold text-ink">
              {{ contingenciaAtiva ? 'Contingência ativa' : 'SEFAZ operacional' }}
            </p>
            <p class="text-sm text-ink-soft mt-0.5">
              {{ contingenciaAtiva ? 'Emitindo notas em modo offline (contingência EPEC).' : 'Nenhuma contingência ativa no momento.' }}
            </p>
          </div>
        </div>
        <Button :variant="contingenciaAtiva ? 'outline' : 'danger'" @click="toggleContingencia">
          <template #icon-left><RefreshCcw :size="15" /></template>
          {{ contingenciaAtiva ? 'Encerrar contingência' : 'Ativar contingência manual' }}
        </Button>
      </div>
    </Card>

    <Alert variant="info" title="O que é o modo de contingência?">
      Quando a SEFAZ está indisponível, o sistema pode emitir notas em contingência (EPEC), permitindo continuar vendendo.
      Assim que a comunicação normaliza, as notas pendentes são transmitidas automaticamente.
    </Alert>

    <Card title="Histórico de contingência" subtitle="Eventos de indisponibilidade da SEFAZ">
      <EmptyState
        icon="History"
        title="Nenhum evento registrado"
        description="Sua loja não passou por nenhuma contingência nos últimos 90 dias."
      />
    </Card>
  </div>
</template>
