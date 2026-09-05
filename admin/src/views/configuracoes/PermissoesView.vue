<script setup>
import { onMounted, ref } from 'vue'
import { Check, Save } from '@lucide/vue'
import { getPerfis } from '@/services/configuracoesService'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Skeleton from '@/components/ui/Skeleton.vue'

const toast = useToastStore()
const loading = ref(true)
const perfis = ref([])

const modules = [
  { key: 'pdv', label: 'PDV' },
  { key: 'produtos', label: 'Produtos' },
  { key: 'financeiro', label: 'Financeiro' },
  { key: 'fiscal', label: 'Fiscal' },
  { key: 'relatorios', label: 'Relatórios' },
  { key: 'configuracoes', label: 'Configurações' },
]

onMounted(async () => {
  perfis.value = await getPerfis()
  loading.value = false
})

function toggle(perfil, moduleKey) {
  perfil.permissoes[moduleKey] = !perfil.permissoes[moduleKey]
}

function salvar(perfil) {
  toast.success('Permissões atualizadas', `Perfil "${perfil.nome}" foi salvo.`)
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold text-ink">Permissões</h1>
      <p class="text-sm text-ink-soft mt-1">Controle o que cada perfil de usuário pode acessar.</p>
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton v-for="n in 3" :key="n" height="10rem" rounded="16px" />
    </div>

    <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <Card v-for="perfil in perfis" :key="perfil.id" :title="perfil.nome" :subtitle="perfil.descricao">
        <template #actions>
          <Button size="sm" variant="outline" @click="salvar(perfil)">
            <template #icon-left><Save :size="14" /></template>
            Salvar
          </Button>
        </template>
        <div class="grid grid-cols-2 gap-2">
          <button
            v-for="module in modules"
            :key="module.key"
            type="button"
            class="flex items-center gap-2.5 rounded-lg border px-3 py-2.5 text-sm transition-colors"
            :class="
              perfil.permissoes[module.key]
                ? 'border-brand-200 bg-brand-50 text-brand-800'
                : 'border-border text-ink-soft hover:border-brand-200'
            "
            @click="toggle(perfil, module.key)"
          >
            <span
              class="flex size-4 shrink-0 items-center justify-center rounded border"
              :class="perfil.permissoes[module.key] ? 'border-brand-500 bg-brand-500 text-white' : 'border-ink-faint'"
            >
              <Check v-if="perfil.permissoes[module.key]" :size="11" />
            </span>
            {{ module.label }}
          </button>
        </div>
      </Card>
    </div>
  </div>
</template>
