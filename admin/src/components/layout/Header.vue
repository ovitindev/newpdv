<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { PanelLeft, Menu, Bell, ChevronRight, Search, Settings, LogOut, UserRound } from '@lucide/vue'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import Dropdown from '@/components/ui/Dropdown.vue'

const route = useRoute()
const uiStore = useUiStore()
const authStore = useAuthStore()
const toastStore = useToastStore()

const search = ref('')
const breadcrumb = computed(() => route.meta?.breadcrumb ?? [])

const notifications = [
  { id: 1, title: 'Estoque baixo', description: 'Detergente Ypê 500ml está com apenas 8 unidades.', time: 'há 12 min', variant: 'warning' },
  { id: 2, title: 'Certificado A1', description: 'Seu certificado vence em 30 dias.', time: 'há 2h', variant: 'warning' },
  { id: 3, title: 'Nova venda', description: 'Venda #1048 registrada com sucesso.', time: 'há 2 min', variant: 'success' },
]

function handleLogout() {
  toastStore.info('Sessão encerrada', 'Este é um ambiente de demonstração.')
}
</script>

<template>
  <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-border bg-white/90 px-4 sm:px-6 backdrop-blur">
    <button
      type="button"
      class="hidden lg:flex size-9 items-center justify-center rounded-lg text-ink-soft transition-colors hover:bg-surface hover:text-ink"
      aria-label="Recolher menu lateral"
      @click="uiStore.toggleSidebar"
    >
      <PanelLeft :size="18" />
    </button>
    <button
      type="button"
      class="flex lg:hidden size-9 items-center justify-center rounded-lg text-ink-soft transition-colors hover:bg-surface hover:text-ink"
      aria-label="Abrir menu lateral"
      @click="uiStore.openMobileSidebar"
    >
      <Menu :size="18" />
    </button>

    <!-- Breadcrumb -->
    <nav class="hidden md:flex items-center gap-1.5 text-sm text-ink-soft min-w-0" aria-label="Breadcrumb">
      <template v-for="(crumb, index) in breadcrumb" :key="crumb">
        <ChevronRight v-if="index > 0" :size="14" class="text-ink-faint shrink-0" />
        <span :class="index === breadcrumb.length - 1 ? 'text-ink font-medium truncate' : 'truncate'">{{ crumb }}</span>
      </template>
    </nav>

    <div class="flex-1" />

    <!-- Busca global -->
    <div class="relative hidden sm:block w-64">
      <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-faint" />
      <input
        v-model="search"
        type="text"
        placeholder="Buscar produtos, vendas, clientes..."
        class="h-9 w-full rounded-[var(--radius-control)] border border-border bg-surface pl-9 pr-3 text-sm text-ink placeholder:text-ink-faint transition-colors focus-visible:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400"
      />
    </div>

    <!-- Notificações -->
    <Dropdown align="right">
      <template #trigger="{ open }">
        <button
          type="button"
          class="relative flex size-9 items-center justify-center rounded-lg text-ink-soft transition-colors hover:bg-surface hover:text-ink"
          :class="open ? 'bg-surface text-ink' : ''"
          aria-label="Notificações"
        >
          <Bell :size="18" />
          <span class="absolute right-2 top-2 size-2 rounded-full bg-danger ring-2 ring-white" />
        </button>
      </template>
      <div class="px-2 py-1.5">
        <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-ink-faint">Notificações</p>
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="flex items-start gap-2.5 rounded-lg px-2 py-2.5 hover:bg-surface cursor-default"
        >
          <span
            class="mt-1.5 size-1.5 shrink-0 rounded-full"
            :class="notification.variant === 'warning' ? 'bg-warning' : 'bg-brand-500'"
          />
          <div class="min-w-0">
            <p class="text-sm font-medium text-ink">{{ notification.title }}</p>
            <p class="text-xs text-ink-soft mt-0.5">{{ notification.description }}</p>
            <p class="text-[11px] text-ink-faint mt-1">{{ notification.time }}</p>
          </div>
        </div>
      </div>
    </Dropdown>

    <!-- Usuário -->
    <Dropdown align="right">
      <template #trigger>
        <button type="button" class="flex items-center gap-2.5 rounded-lg py-1 pl-1 pr-2 hover:bg-surface transition-colors">
          <span class="flex size-8 items-center justify-center rounded-full bg-brand-500 text-xs font-semibold text-white">
            {{ authStore.user.iniciais }}
          </span>
          <span class="hidden sm:block text-left">
            <span class="block text-sm font-medium text-ink leading-tight">{{ authStore.user.nome }}</span>
            <span class="block text-xs text-ink-soft leading-tight">{{ authStore.user.cargo }}</span>
          </span>
        </button>
      </template>
      <div class="px-1 py-1">
        <router-link to="/configuracoes/usuarios" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink hover:bg-surface">
          <UserRound :size="16" class="text-ink-faint" /> Meu perfil
        </router-link>
        <router-link to="/configuracoes/empresa" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink hover:bg-surface">
          <Settings :size="16" class="text-ink-faint" /> Configurações
        </router-link>
        <div class="my-1 h-px bg-border" />
        <button type="button" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-danger hover:bg-danger-soft" @click="handleLogout">
          <LogOut :size="16" /> Sair
        </button>
      </div>
    </Dropdown>
  </header>
</template>
