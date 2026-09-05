<script setup>
import Skeleton from './Skeleton.vue'
import EmptyState from './EmptyState.vue'

const props = defineProps({
  columns: { type: Array, required: true }, // [{ key, label, align?: 'left'|'right'|'center', width? }]
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  loading: { type: Boolean, default: false },
  skeletonRows: { type: Number, default: 5 },
  emptyTitle: { type: String, default: 'Nenhum registro encontrado' },
  emptyDescription: { type: String, default: 'Ajuste os filtros ou cadastre um novo registro.' },
  emptyIcon: { type: String, default: 'Inbox' },
})

function alignClass(align) {
  if (align === 'right') return 'text-right'
  if (align === 'center') return 'text-center'
  return 'text-left'
}
</script>

<template>
  <div class="overflow-x-auto -mx-5 sm:-mx-6 px-5 sm:px-6">
    <table class="w-full min-w-[640px] border-collapse text-sm">
      <thead>
        <tr class="border-b border-border">
          <th
            v-for="column in columns"
            :key="column.key"
            scope="col"
            class="py-3 px-3 first:pl-0 last:pr-0 font-medium text-xs uppercase tracking-wide text-ink-faint whitespace-nowrap"
            :class="alignClass(column.align)"
            :style="column.width ? { width: column.width } : {}"
          >
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <template v-if="loading">
          <tr v-for="n in skeletonRows" :key="`skeleton-${n}`" class="border-b border-border last:border-0">
            <td v-for="column in columns" :key="column.key" class="py-3.5 px-3 first:pl-0 last:pr-0">
              <Skeleton height="0.875rem" :width="column.align === 'right' ? '60%' : '80%'" />
            </td>
          </tr>
        </template>
        <template v-else-if="rows.length">
          <tr
            v-for="row in rows"
            :key="row[rowKey]"
            class="border-b border-border last:border-0 transition-colors duration-150 hover:bg-surface/70"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="py-3.5 px-3 first:pl-0 last:pr-0 text-ink align-middle"
              :class="alignClass(column.align)"
            >
              <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                {{ row[column.key] }}
              </slot>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <EmptyState v-if="!loading && !rows.length" :icon="emptyIcon" :title="emptyTitle" :description="emptyDescription" />
  </div>
</template>
