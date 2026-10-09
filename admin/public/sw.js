// Service worker do NovaPDV. Cuida só do "casco" do app: arquivos do build
// ficam em cache pra abrir rápido e a tela inicial abrir mesmo sem sinal.
// Chamadas da API (/api) NUNCA são cacheadas: venda e estoque precisam ser
// sempre os dados reais do servidor.
const CACHE = 'novapdv-v1'

self.addEventListener('install', () => self.skipWaiting())

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim()),
  )
})

self.addEventListener('fetch', (event) => {
  const { request } = event
  if (request.method !== 'GET') return

  const url = new URL(request.url)
  const mesmaOrigem = url.origin === self.location.origin
  if (mesmaOrigem && url.pathname.startsWith('/api')) return

  // Navegação: tenta a rede (versão nova do app); sem rede, volta pro index em cache.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((resposta) => {
          if (resposta.ok) {
            const copia = resposta.clone()
            caches.open(CACHE).then((c) => c.put('/index.html', copia))
          }
          return resposta
        })
        .catch(() => caches.match('/index.html')),
    )
    return
  }

  // Arquivos com hash do build, ícones e fontes: cache primeiro.
  const estatico = (mesmaOrigem && /^\/(assets|icons)\//.test(url.pathname)) || url.hostname.endsWith('gstatic.com') || url.hostname.endsWith('googleapis.com')
  if (estatico) {
    event.respondWith(
      caches.match(request).then(
        (emCache) =>
          emCache ||
          fetch(request).then((resposta) => {
            if (resposta.ok || resposta.type === 'opaque') {
              const copia = resposta.clone()
              caches.open(CACHE).then((c) => c.put(request, copia))
            }
            return resposta
          }),
      ),
    )
  }
})
