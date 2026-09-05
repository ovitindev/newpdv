/**
 * Simula a latência de uma chamada de API real usando os dados mockados.
 * Os services trocam a chamada a `mockRequest` por `http.get(...)` quando a
 * API REST (PHP/CodeIgniter) estiver disponível, sem mudar a assinatura
 * usada pelas stores/views.
 */
export function mockRequest(data, { delay = 350, fail = false, errorMessage = 'Falha ao carregar dados.' } = {}) {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      if (fail) {
        reject(new Error(errorMessage))
        return
      }
      resolve(typeof data === 'function' ? data() : data)
    }, delay)
  })
}
