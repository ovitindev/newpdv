<script setup>
import { computed, onMounted, ref } from 'vue'
import { ChevronRight, UserRound, UserPlus, X, Search } from '@lucide/vue'
import { createCliente } from '@/services/clientesService'
import { useClientes } from '@/composables/useClientes'
import { useToastStore } from '@/stores/toast'
import BottomSheet from '@/components/mobile/BottomSheet.vue'

const props = defineProps({
  modelValue: { type: Object, default: null },
  label: { type: String, default: 'Cliente' },
  vazio: { type: String, default: 'Selecionar ou cadastrar cliente' },
})
const emit = defineEmits(['update:modelValue'])

const toast = useToastStore()
const { clientes, carregar, upsert } = useClientes()
onMounted(() => carregar())

const aberto = ref(false)
const busca = ref('')
const modoNovo = ref(false)
const novo = ref({ nome: '', telefone: '' })
const salvando = ref(false)

const lista = computed(() => {
  const q = busca.value.trim().toLowerCase()
  const base = clientes.value.filter((c) => c.status !== 'inativo')
  const filtrada = q ? base.filter((c) => `${c.nome} ${c.telefone ?? ''}`.toLowerCase().includes(q)) : base
  return filtrada.slice(0, 40)
})

function abrir() {
  busca.value = ''
  modoNovo.value = false
  aberto.value = true
}

function escolher(cliente) {
  emit('update:modelValue', cliente)
  aberto.value = false
}

function iniciarNovo() {
  novo.value = { nome: busca.value.trim(), telefone: '' }
  modoNovo.value = true
}

async function cadastrar() {
  if (!novo.value.nome.trim()) return toast.error('Informe o nome do cliente')
  salvando.value = true
  try {
    const cliente = await createCliente({ nome: novo.value.nome.trim(), telefone: novo.value.telefone || null })
    upsert(cliente)
    escolher(cliente)
    toast.success('Cliente cadastrado', cliente.nome)
  } catch (e) {
    toast.error('Não foi possível cadastrar', e.message)
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <div>
    <p v-if="label" class="mb-1.5 text-sm font-medium text-ink">{{ label }}</p>
    <div v-if="props.modelValue" class="flex items-center gap-3 rounded-2xl border border-border bg-white p-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">
        {{ props.modelValue.nome.slice(0, 2).toUpperCase() }}
      </span>
      <div class="min-w-0 flex-1">
        <p class="truncate font-medium text-ink">{{ props.modelValue.nome }}</p>
        <p v-if="props.modelValue.telefone" class="text-xs text-ink-faint">{{ props.modelValue.telefone }}</p>
      </div>
      <button class="flex size-10 items-center justify-center rounded-full text-ink-faint active:bg-surface" aria-label="Remover cliente" @click="emit('update:modelValue', null)">
        <X :size="18" />
      </button>
    </div>
    <button v-else class="flex h-14 w-full items-center gap-3 rounded-2xl border border-dashed border-brand-300 bg-brand-50/50 px-4 text-left text-base text-ink-soft active:bg-brand-50" @click="abrir">
      <UserRound :size="20" class="text-brand-600" />
      <span class="flex-1">{{ vazio }}</span>
      <ChevronRight :size="18" class="text-ink-faint" />
    </button>

    <BottomSheet v-model="aberto" :title="modoNovo ? 'Novo cliente' : 'Escolher cliente'" full>
      <template v-if="!modoNovo">
        <div class="relative mb-3">
          <Search :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-faint" />
          <input v-model="busca" type="search" placeholder="Nome ou telefone" class="h-12 w-full rounded-2xl border border-border bg-surface pl-11 pr-4 text-base outline-none focus:border-brand-400 focus:bg-white" />
        </div>
        <button class="mb-2 flex w-full items-center gap-3 rounded-2xl bg-brand-600 px-4 py-3.5 text-left font-medium text-white active:bg-brand-700" @click="iniciarNovo">
          <UserPlus :size="20" />Cadastrar novo cliente
        </button>
        <ul class="divide-y divide-border">
          <li v-for="c in lista" :key="c.id">
            <button class="flex w-full items-center gap-3 py-3 text-left active:bg-surface" @click="escolher(c)">
              <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">{{ c.nome.slice(0, 2).toUpperCase() }}</span>
              <span class="min-w-0 flex-1">
                <span class="block truncate font-medium text-ink">{{ c.nome }}</span>
                <span v-if="c.telefone" class="text-xs text-ink-faint">{{ c.telefone }}</span>
              </span>
            </button>
          </li>
        </ul>
        <p v-if="!lista.length" class="py-8 text-center text-sm text-ink-faint">Nenhum cliente encontrado.</p>
      </template>
      <form v-else class="space-y-4 pt-2" @submit.prevent="cadastrar">
        <label class="block">
          <span class="mb-1.5 block text-sm font-medium text-ink">Nome</span>
          <input v-model="novo.nome" type="text" autocomplete="off" placeholder="Ex: Maria Silva" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
        </label>
        <label class="block">
          <span class="mb-1.5 block text-sm font-medium text-ink">Telefone / WhatsApp</span>
          <input v-model="novo.telefone" type="tel" inputmode="tel" placeholder="(00) 00000-0000" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
        </label>
        <div class="flex gap-3 pt-2">
          <button type="button" class="h-12 flex-1 rounded-2xl bg-surface font-medium text-ink-soft active:bg-surface-alt" @click="modoNovo = false">Voltar</button>
          <button type="submit" :disabled="salvando" class="h-12 flex-[2] rounded-2xl bg-brand-600 font-semibold text-white active:bg-brand-700 disabled:opacity-60">{{ salvando ? 'Salvando...' : 'Cadastrar' }}</button>
        </div>
      </form>
    </BottomSheet>
  </div>
</template>
