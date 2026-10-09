<script setup>
import { computed, onMounted, ref } from 'vue'
import { Search, Plus, Phone, MessageCircle, Pencil } from '@lucide/vue'
import { createCliente, updateCliente } from '@/services/clientesService'
import { useClientes } from '@/composables/useClientes'
import { useToastStore } from '@/stores/toast'
import { mascaraTelefone, telLink, whatsappLink } from '@/utils/whatsapp'
import BottomSheet from '@/components/mobile/BottomSheet.vue'

const toast = useToastStore()
const { clientes, carregar, upsert } = useClientes()
const loading = ref(true)
const busca = ref('')

onMounted(async () => {
  try {
    await carregar(true)
  } catch (e) {
    toast.error('Não foi possível carregar os clientes', e.message)
  } finally {
    loading.value = false
  }
})

const lista = computed(() => {
  const q = busca.value.trim().toLowerCase()
  return clientes.value.filter((c) => !q || `${c.nome} ${c.telefone ?? ''} ${c.documento ?? ''}`.toLowerCase().includes(q))
})

const aberto = ref(false)
const editandoId = ref(null)
const form = ref({ nome: '', telefone: '', email: '', documento: '' })
const salvando = ref(false)

function abrirNovo() {
  editandoId.value = null
  form.value = { nome: '', telefone: '', email: '', documento: '' }
  aberto.value = true
}

function abrirEdicao(c) {
  editandoId.value = c.id
  form.value = { nome: c.nome, telefone: c.telefone ?? '', email: c.email ?? '', documento: c.documento ?? '' }
  aberto.value = true
}

async function salvar() {
  if (!form.value.nome.trim()) return toast.error('Informe o nome do cliente')
  salvando.value = true
  const payload = {
    nome: form.value.nome.trim(),
    telefone: form.value.telefone || null,
    email: form.value.email || null,
    documento: form.value.documento || null,
  }
  try {
    const salvo = editandoId.value ? await updateCliente(editandoId.value, payload) : await createCliente(payload)
    upsert(salvo)
    aberto.value = false
    toast.success(editandoId.value ? 'Cliente atualizado' : 'Cliente cadastrado')
  } catch (e) {
    toast.error('Não foi possível salvar', e.message)
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <div class="space-y-4 px-4 pb-28 pt-4">
    <div class="relative">
      <Search :size="19" class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-faint" />
      <input v-model="busca" type="search" placeholder="Buscar por nome, telefone ou CPF" class="h-[52px] w-full rounded-2xl border border-border bg-white pl-12 pr-4 text-base shadow-soft outline-none focus:border-brand-400" />
    </div>

    <div v-if="loading" class="space-y-2.5"><div v-for="n in 5" :key="n" class="h-[72px] animate-pulse rounded-2xl bg-white" /></div>

    <ul v-else-if="lista.length" class="divide-y divide-border overflow-hidden rounded-2xl bg-white shadow-soft">
      <li v-for="c in lista" :key="c.id" class="flex items-center gap-3 px-4 py-3">
        <button class="flex min-w-0 flex-1 items-center gap-3 py-0.5 text-left" @click="abrirEdicao(c)">
          <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">{{ c.nome.slice(0, 2).toUpperCase() }}</span>
          <span class="min-w-0">
            <span class="block truncate font-medium text-ink">{{ c.nome }}</span>
            <span class="block truncate text-xs text-ink-faint">{{ c.telefone || 'Sem telefone' }}</span>
          </span>
        </button>
        <template v-if="c.telefone">
          <a :href="telLink(c.telefone)" class="flex size-11 items-center justify-center rounded-full bg-surface text-ink-soft active:bg-surface-alt" aria-label="Ligar"><Phone :size="18" /></a>
          <a :href="whatsappLink(c.telefone)" target="_blank" rel="noopener" class="flex size-11 items-center justify-center rounded-full bg-[#25D366]/15 text-[#128C7E] active:bg-[#25D366]/25" aria-label="WhatsApp"><MessageCircle :size="18" /></a>
        </template>
        <button v-else class="flex size-11 items-center justify-center rounded-full bg-surface text-ink-faint" aria-label="Editar" @click="abrirEdicao(c)"><Pencil :size="17" /></button>
      </li>
    </ul>

    <div v-else class="rounded-2xl bg-white p-10 text-center shadow-soft">
      <p class="text-sm text-ink-soft">{{ busca ? 'Nenhum cliente encontrado.' : 'Nenhum cliente cadastrado ainda.' }}</p>
    </div>

    <button class="fixed bottom-24 right-4 z-30 flex h-14 items-center gap-2 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 px-5 font-semibold text-white shadow-soft-lg active:scale-95" style="bottom: calc(6rem + env(safe-area-inset-bottom))" @click="abrirNovo">
      <Plus :size="20" />Novo cliente
    </button>

    <BottomSheet v-model="aberto" :title="editandoId ? 'Editar cliente' : 'Novo cliente'">
      <form class="space-y-4 pt-1" @submit.prevent="salvar">
        <label class="block"><span class="mb-1.5 block text-sm font-medium">Nome</span>
          <input v-model="form.nome" type="text" autocomplete="off" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
        </label>
        <label class="block"><span class="mb-1.5 block text-sm font-medium">Telefone / WhatsApp</span>
          <input :value="form.telefone" type="tel" inputmode="tel" placeholder="(00) 00000-0000" @input="form.telefone = mascaraTelefone($event.target.value)" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
        </label>
        <label class="block"><span class="mb-1.5 block text-sm font-medium">E-mail</span>
          <input v-model="form.email" type="email" inputmode="email" autocapitalize="off" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
        </label>
        <label class="block"><span class="mb-1.5 block text-sm font-medium">CPF / CNPJ</span>
          <input v-model="form.documento" type="text" inputmode="numeric" class="h-12 w-full rounded-2xl border border-border px-4 text-base outline-none focus:border-brand-400" />
        </label>
        <button type="submit" :disabled="salvando" class="h-14 w-full rounded-2xl bg-brand-600 font-semibold text-white active:bg-brand-700 disabled:opacity-60">{{ salvando ? 'Salvando...' : 'Salvar' }}</button>
      </form>
    </BottomSheet>
  </div>
</template>
