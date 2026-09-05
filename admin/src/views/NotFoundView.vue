<script setup>
import { FileQuestion } from '@lucide/vue'
import Button from '@/components/ui/Button.vue'
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center gap-4 bg-surface px-6 text-center">
    <span class="flex size-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
      <FileQuestion :size="32" />
    </span>
    <div>
      <h1 class="text-xl font-semibold text-ink">Página não encontrada</h1>
      <p class="text-sm text-ink-soft mt-1">O endereço acessado não existe ou foi movido.</p>
    </div>
    <Button @click="$router.push('/')">Voltar ao Dashboard</Button>
  </div>
</template>
