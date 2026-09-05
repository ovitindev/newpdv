import { defineStore } from 'pinia'

let nextId = 1

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),
  actions: {
    push({ title, description = '', variant = 'success', duration = 4000 }) {
      const id = nextId++
      this.toasts.push({ id, title, description, variant })
      if (duration > 0) {
        setTimeout(() => this.dismiss(id), duration)
      }
      return id
    },
    dismiss(id) {
      this.toasts = this.toasts.filter((toast) => toast.id !== id)
    },
    success(title, description) {
      return this.push({ title, description, variant: 'success' })
    },
    error(title, description) {
      return this.push({ title, description, variant: 'danger' })
    },
    info(title, description) {
      return this.push({ title, description, variant: 'info' })
    },
  },
})
