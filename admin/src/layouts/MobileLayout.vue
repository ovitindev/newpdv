<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { House, ShoppingBag, Receipt, ClipboardList, Users, LogOut, WifiOff, Monitor } from '@lucide/vue'
import { useAuthStore } from '@/stores/auth'
import { useEmpresaStore } from '@/stores/empresa'
import BottomSheet from '@/components/mobile/BottomSheet.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const empresaStore = useEmpresaStore()

// Admin entrando pelo app não tem permissão pra /empresa em vendedor, mas aqui
// o carregamento só é pedido pra quem pode (a vendedora usa o nome vindo do /me).
onMounted(() => {
  if (!auth.ehVendedor) empresaStore.carregar()
})

const tabs = [
  { to: '/app', name: 'app-inicio', label: 'Início', icon: House },
  { to: '/app/vendas', name: 'app-vendas', label: 'Vendas', icon: Receipt },
  { to: '/app/vender', name: 'app-vender', label: 'Vender', icon: ShoppingBag, destaque: true },
  { to: '/app/pedidos', name: 'app-pedidos', label: 'Pedidos', icon: ClipboardList },
  { to: '/app/clientes', name: 'app-clientes', label: 'Clientes', icon: Users },
]

const nomeEmpresa = computed(() => auth.empresa?.nome_fantasia || auth.empresa?.razao_social || 'NovaPDV')
const titulo = computed(() => (route.name === 'app-inicio' ? `${auth.greeting}, ${auth.firstName}` : route.meta.title))

const perfilAberto = ref(false)

async function sair() {
  perfilAberto.value = false
  await auth.logout()
  router.replace({ name: 'login' })
}

// Aviso de "sem internet": a venda precisa do servidor, então a vendedora
// precisa saber antes de montar o carrinho inteiro.
const offline = ref(!navigator.onLine)
const setOnline = () => (offline.value = false)
const setOffline = () => (offline.value = true)
onMounted(() => {
  window.addEventListener('online', setOnline)
  window.addEventListener('offline', setOffline)
})
onUnmounted(() => {
  window.removeEventListener('online', setOnline)
  window.removeEventListener('offline', setOffline)
})
</script>

<template>
  <div class="flex h-dvh flex-col bg-surface">
    <header class="relative shrink-0 overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700 px-5 pb-4 pt-[max(0.9rem,env(safe-area-inset-top))] text-white">
      <div class="pointer-events-none absolute -right-10 -top-14 size-44 rounded-full bg-brand-400/20 blur-3xl" />
      <div class="relative flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="truncate text-xs font-medium text-brand-200">{{ nomeEmpresa }}</p>
          <h1 class="truncate text-xl font-semibold leading-tight">{{ titulo }}</h1>
        </div>
        <button
          class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-semibold ring-1 ring-white/25 active:bg-white/25"
          aria-label="Perfil"
          @click="perfilAberto = true"
        >
          {{ auth.user?.iniciais }}
        </button>
      </div>
    </header>

    <div v-if="offline" class="flex shrink-0 items-center justify-center gap-2 bg-warning px-4 py-1.5 text-xs font-medium text-white">
      <WifiOff :size="14" />Sem internet — vendas e pedidos precisam de conexão
    </div>

    <main id="app-scroll" class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
      <router-view v-slot="{ Component }">
        <transition enter-active-class="transition-opacity duration-150" enter-from-class="opacity-0" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <nav class="relative z-40 shrink-0 border-t border-border bg-white pb-[env(safe-area-inset-bottom)] shadow-[0_-6px_20px_-12px_rgba(10,61,30,0.25)]">
      <ul class="mx-auto grid max-w-xl grid-cols-5">
        <li v-for="tab in tabs" :key="tab.to">
          <router-link :to="tab.to" class="relative flex h-16 flex-col items-center justify-center gap-0.5 text-[11px] font-medium" :class="route.name === tab.name ? 'text-brand-700' : 'text-ink-faint'">
            <template v-if="tab.destaque">
              <span class="-mt-6 flex size-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-soft-lg ring-4 ring-white" :class="route.name === tab.name ? 'scale-105' : ''">
                <component :is="tab.icon" :size="26" />
              </span>
              <span class="-mt-0.5">{{ tab.label }}</span>
            </template>
            <template v-else>
              <component :is="tab.icon" :size="22" :stroke-width="route.name === tab.name ? 2.4 : 2" />
              {{ tab.label }}
              <span v-if="route.name === tab.name" class="absolute top-0 h-0.5 w-8 rounded-full bg-brand-600" />
            </template>
          </router-link>
        </li>
      </ul>
    </nav>

    <BottomSheet v-model="perfilAberto" title="Minha conta">
      <div class="flex items-center gap-4 rounded-2xl bg-surface p-4">
        <span class="flex size-14 items-center justify-center rounded-full bg-brand-600 text-lg font-semibold text-white">{{ auth.user?.iniciais }}</span>
        <div class="min-w-0">
          <p class="truncate font-semibold text-ink">{{ auth.user?.nome }}</p>
          <p class="truncate text-sm text-ink-soft">{{ auth.user?.email }}</p>
          <p v-if="auth.vendedor" class="mt-0.5 text-xs text-brand-700">Vendedora · comissão {{ auth.vendedor.comissao }}%</p>
        </div>
      </div>
      <router-link v-if="!auth.ehVendedor" to="/" class="mt-3 flex h-14 w-full items-center gap-3 rounded-2xl bg-surface px-4 font-medium text-ink active:bg-surface-alt" @click="perfilAberto = false">
        <Monitor :size="20" class="text-ink-soft" />Abrir painel completo
      </router-link>
      <button class="mt-3 flex h-14 w-full items-center gap-3 rounded-2xl bg-danger-soft px-4 font-medium text-danger active:opacity-80" @click="sair">
        <LogOut :size="20" />Sair
      </button>
    </BottomSheet>
  </div>
</template>
