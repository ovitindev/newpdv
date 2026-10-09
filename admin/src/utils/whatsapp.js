/** Link wa.me pro número informado (DDD + número, com ou sem máscara). Sem número, abre o WhatsApp pra escolher o contato. */
export function whatsappLink(telefone, texto = '') {
  const digitos = String(telefone ?? '').replace(/\D/g, '')
  const numero = digitos.length === 10 || digitos.length === 11 ? `55${digitos}` : digitos
  const query = texto ? `?text=${encodeURIComponent(texto)}` : ''
  return `https://wa.me/${numero}${query}`
}

export function telLink(telefone) {
  return `tel:${String(telefone ?? '').replace(/[^\d+]/g, '')}`
}

/** Máscara de telefone BR enquanto digita: (11) 98888-7777 ou (11) 3888-7777. */
export function mascaraTelefone(valor) {
  const d = String(valor ?? '').replace(/\D/g, '').slice(0, 11)
  if (d.length <= 2) return d ? `(${d}` : ''
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
}
