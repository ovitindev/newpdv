<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from '@lucide/vue'

const props = defineProps({
  page: { type: Number, required: true },
  pageSize: { type: Number, required: true },
  total: { type: Number, required: true },
})

const emit = defineEmits(['update:page'])

const totalPages = computed(() => Math.max(Math.ceil(props.total / props.pageSize), 1))
const rangeStart = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.pageSize + 1))
const rangeEnd = computed(() => Math.min(props.page * props.pageSize, props.total))

function go(page) {
  const clamped = Math.min(Math.max(page, 1), totalPages.value)
  if (clamped !== props.page) emit('update:page', clamped)
}
</script>

<template>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4">
    <p class="text-xs text-ink-soft">
      Mostrando <span class="font-medium text-ink">{{ rangeStart }}–{{ rangeEnd }}</span> de
      <span class="font-medium text-ink">{{ total }}</span>
    </p>
    <div class="flex items-center gap-1.5">
      <button
        type="button"
        class="flex size-8 items-center justify-center rounded-lg border border-border text-ink-soft transition-colors hover:border-brand-300 hover:text-brand-700 disabled:opacity-40 disabled:hover:border-border disabled:hover:text-ink-soft"
        :disabled="page <= 1"
        aria-label="Página anterior"
        @click="go(page - 1)"
      >
        <ChevronLeft :size="16" />
      </button>
      <span class="px-2 text-xs font-medium text-ink-soft">Página {{ page }} de {{ totalPages }}</span>
      <button
        type="button"
        class="flex size-8 items-center justify-center rounded-lg border border-border text-ink-soft transition-colors hover:border-brand-300 hover:text-brand-700 disabled:opacity-40 disabled:hover:border-border disabled:hover:text-ink-soft"
        :disabled="page >= totalPages"
        aria-label="Próxima página"
        @click="go(page + 1)"
      >
        <ChevronRight :size="16" />
      </button>
    </div>
  </div>
</template>
