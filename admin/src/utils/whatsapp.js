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
