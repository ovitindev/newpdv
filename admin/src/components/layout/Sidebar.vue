<script setup>
import { reactive, computed } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronDown } from '@lucide/vue'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { navigation } from '@/router/navigation'
import AppIcon from '@/components/ui/AppIcon.vue'
import logo from '@/assets/img/logo.png'

const route = useRoute()
const uiStore = useUiStore()
const authStore = useAuthStore()

function groupContainsActive(item) {
  return item.children?.some((child) => child.to === route.path) ?? false
}

const openGroups = reactive(
  Object.fromEntries(navigation.filter((item) => item.children).map((item) => [item.label, groupContainsActive(item)])),
)

function toggleGroup(item) {
  if (uiStore.sidebarCollapsed) {
    uiStore.sidebarCollapsed = false
    openGroups[item.label] = true
    return
  }
  openGroups[item.label] = !openGroups[item.label]
}

const collapsed = computed(() => uiStore.sidebarCollapsed)

function handleNavClick() {
  uiStore.closeMobileSidebar()
}
</script>

<template>
  <aside
    class="flex h-full flex-col bg-brand-950 text-white transition-[width] duration-200 ease-out"
    :class="collapsed ? 'w-[76px]' : 'w-[264px]'"
  >
    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center px-4 border-b border-white/10">
      <router-link to="/" class="flex items-center gap-2 overflow-hidden" @click="handleNavClick">
        <span
          v-if="collapsed"
          class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-300 to-brand-500 font-bold text-brand-950"
        >
          N
        </span>
        <img v-else :src="logo" alt="NovaPDV" class="h-8 w-auto shrink-0" />
      </router-link>
    </div>

    <!-- Navegação -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
      <template v-for="item in navigation" :key="item.label">
        <!-- Item simples -->
        <router-link
          v-if="!item.children"
          :to="item.to"
          class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150"
          :class="
            route.path === item.to
              ? 'bg-brand-500 text-white shadow-sm'
              : 'text-brand-100/70 hover:bg-white/5 hover:text-white'
          "
          :title="collapsed ? item.label : undefined"
          @click="handleNavClick"
        >
          <AppIcon :name="item.icon" :size="19" class="shrink-0" />
          <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
        </router-link>

        <!-- Grupo com submenu -->
        <div v-else>
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150"
            :class="
              groupContainsActive(item)
                ? 'text-brand-300'
                : 'text-brand-100/70 hover:bg-white/5 hover:text-white'
            "
            :title="collapsed ? item.label : undefined"
            @click="toggleGroup(item)"
          >
            <AppIcon :name="item.icon" :size="19" class="shrink-0" />
            <span v-if="!collapsed" class="truncate flex-1 text-left">{{ item.label }}</span>
            <ChevronDown
              v-if="!collapsed"
              :size="15"
              class="shrink-0 transition-transform duration-200"
              :class="openGroups[item.label] ? 'rotate-180' : ''"
            />
          </button>
          <transition
            enter-active-class="transition-all duration-200 ease-out overflow-hidden"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-96 opacity-100"
            leave-active-class="transition-all duration-150 ease-in overflow-hidden"
            leave-from-class="max-h-96 opacity-100"
            leave-to-class="max-h-0 opacity-0"
          >
            <div v-if="!collapsed && openGroups[item.label]" class="mt-1 ml-4 space-y-0.5 border-l border-white/10 pl-4 overflow-hidden">
              <router-link
                v-for="child in item.children"
                :key="child.to"
                :to="child.to"
                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors duration-150"
                :class="
                  route.path === child.to
                    ? 'bg-white/10 text-white'
                    : 'text-brand-100/60 hover:bg-white/5 hover:text-white'
                "
                @click="handleNavClick"
              >
                <AppIcon :name="child.icon" :size="16" class="shrink-0" />
                <span class="truncate">{{ child.label }}</span>
              </router-link>
            </div>
          </transition>
        </div>
      </template>
    </nav>

    <!-- Usuário -->
    <div class="shrink-0 border-t border-white/10 p-3">
      <div class="flex items-center gap-3 rounded-xl px-2 py-2" :class="collapsed ? 'justify-center' : ''">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-semibold text-white">
          {{ authStore.user.iniciais }}
        </span>
        <div v-if="!collapsed" class="min-w-0">
          <p class="truncate text-sm font-medium text-white">{{ authStore.user.nome }}</p>
          <p class="truncate text-xs text-brand-100/60">{{ authStore.user.cargo }}</p>
        </div>
      </div>
    </div>
  </aside>
</template>
