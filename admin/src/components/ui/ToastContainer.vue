<script setup>
import { useToastStore } from '@/stores/toast'
import Toast from './Toast.vue'

const toastStore = useToastStore()
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[60] flex flex-col gap-2.5">
      <transition-group
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-x-4"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <Toast
          v-for="toast in toastStore.toasts"
          :key="toast.id"
          :title="toast.title"
          :description="toast.description"
          :variant="toast.variant"
          @close="toastStore.dismiss(toast.id)"
        />
      </transition-group>
    </div>
  </Teleport>
</template>
