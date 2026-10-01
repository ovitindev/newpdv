const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
})

const numberFormatter = new Intl.NumberFormat('pt-BR')

export function formatCurrency(value) {
  const number = Number(value ?? 0)
  return currencyFormatter.format(Number.isFinite(number) ? number : 0)
}

export function formatNumber(value) {
  const number = Number(value ?? 0)
  return numberFormatter.format(Number.isFinite(number) ? number : 0)
}

export function formatPercent(value, { signed = true } = {}) {
  const number = Number(value ?? 0)
  const sign = signed && number > 0 ? '+' : ''
  return `${sign}${number.toFixed(1).replace('.', ',')}%`
}

export function formatDate(value, options = {}) {
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    ...options,
  }).format(date)
}

export function formatDateTime(value) {
  return formatDate(value, { hour: '2-digit', minute: '2-digit' })
}

// Para campos que são só uma data (ex: vencimento), sem significado de hora.
// O Laravel serializa um cast "date" como meia-noite UTC; formatar em UTC
// evita que o fuso local jogue a data exibida pro dia anterior.
export function formatDateOnly(value) {
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    timeZone: 'UTC',
  }).format(date)
}

export function formatRelativeTime(value) {
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return '—'

  const diffMs = Date.now() - date.getTime()
  const diffMinutes = Math.round(diffMs / 60000)

  if (diffMinutes < 1) return 'agora mesmo'
  if (diffMinutes < 60) return `há ${diffMinutes} min`

  const diffHours = Math.round(diffMinutes / 60)
  if (diffHours < 24) return `há ${diffHours}h`

  const diffDays = Math.round(diffHours / 24)
  if (diffDays === 1) return 'ontem'
  if (diffDays < 7) return `há ${diffDays} dias`

  return formatDate(date)
}

export function minutesAgo(minutes) {
  return new Date(Date.now() - minutes * 60000)
}

// "Hoje" no fuso do navegador, no formato YYYY-MM-DD usado pelos filtros de
// data e pelo <input type="date">. NUNCA use date.toISOString().slice(0, 10)
// pra isso — toISOString() converte pra UTC, então no Brasil (UTC-3) dá a
// data de amanhã entre ~21h e meia-noite.
export function localISODate(date = new Date()) {
  const ano = date.getFullYear()
  const mes = String(date.getMonth() + 1).padStart(2, '0')
  const dia = String(date.getDate()).padStart(2, '0')
  return `${ano}-${mes}-${dia}`
}
