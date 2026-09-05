<script setup>
import { useRoute } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import Sidebar from '@/components/layout/Sidebar.vue'
import Header from '@/components/layout/Header.vue'

const route = useRoute()
const uiStore = useUiStore()
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-surface">
    <!-- Sidebar desktop -->
    <div class="hidden lg:block shrink-0">
      <Sidebar />
    </div>

    <!-- Sidebar mobile (off-canvas) -->
    <transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="uiStore.mobileSidebarOpen" class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-brand-950/50" @click="uiStore.closeMobileSidebar" />
        <transition
          appear
          enter-active-class="transition-transform duration-200 ease-out"
          enter-from-class="-translate-x-full"
          enter-to-class="translate-x-0"
        >
          <div class="absolute inset-y-0 left-0">
            <Sidebar />
          </div>
        </transition>
      </div>
    </transition>

    <div class="flex min-w-0 flex-1 flex-col">
      <Header />
      <main class="flex-1 overflow-y-auto">
        <div v-if="route.meta?.bare" class="h-full">
          <router-view />
        </div>
        <div v-else class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
          <router-view v-slot="{ Component }">
            <transition
              mode="out-in"
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
            >
              <component :is="Component" />
            </transition>
          </router-view>
        </div>
      </main>
    </div>
  </div>
</template>
