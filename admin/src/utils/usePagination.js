import { computed, ref, watch } from 'vue'

/**
 * Paginação client-side simples para listas já carregadas/filtradas em memória.
 * Reseta para a página 1 sempre que o tamanho da lista de origem muda (ex: filtro aplicado).
 */
export function usePagination(sourceRef, pageSize = 8) {
  const page = ref(1)

  watch(
    () => sourceRef.value.length,
    () => {
      page.value = 1
    },
  )

  const paginated = computed(() => {
    const start = (page.value - 1) * pageSize
    return sourceRef.value.slice(start, start + pageSize)
  })

  return { page, pageSize, paginated }
}
