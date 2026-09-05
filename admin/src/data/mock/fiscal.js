export const fiscalSummary = {
  nfceEmitidas: 246,
  nfeEmitidas: 18,
  canceladas: 5,
  rejeitadas: 2,
  contingencia: 0,
}

export const notasFiscais = [
  { id: 1, numero: '000001048', serie: '1', tipo: 'NFC-e', cliente: 'Consumidor Final', valor: 320.0, status: 'autorizada', data: '2026-08-30T14:32:00' },
  { id: 2, numero: '000001047', serie: '1', tipo: 'NFC-e', cliente: 'Consumidor Final', valor: 150.0, status: 'autorizada', data: '2026-08-30T14:26:00' },
  { id: 3, numero: '000000512', serie: '1', tipo: 'NF-e', cliente: 'Comércio Estrela Ltda', valor: 890.0, status: 'autorizada', data: '2026-08-30T14:19:00' },
  { id: 4, numero: '000001045', serie: '1', tipo: 'NFC-e', cliente: 'Consumidor Final', valor: 210.0, status: 'cancelada', data: '2026-08-30T13:16:00' },
  { id: 5, numero: '000001043', serie: '1', tipo: 'NFC-e', cliente: 'Consumidor Final', valor: 76.4, status: 'rejeitada', data: '2026-08-30T11:40:00' },
  { id: 6, numero: '000000511', serie: '1', tipo: 'NF-e', cliente: 'Padaria Bom Sabor Ltda', valor: 1290.0, status: 'autorizada', data: '2026-08-29T18:05:00' },
  { id: 7, numero: '000001039', serie: '1', tipo: 'NFC-e', cliente: 'Consumidor Final', valor: 79.9, status: 'cancelada', data: '2026-08-29T13:02:00' },
  { id: 8, numero: '000001032', serie: '1', tipo: 'NFC-e', cliente: 'Consumidor Final', valor: 118.0, status: 'autorizada', data: '2026-08-29T09:47:00' },
]

export const fiscalStatusOptions = [
  { value: '', label: 'Todos os status' },
  { value: 'autorizada', label: 'Autorizada' },
  { value: 'cancelada', label: 'Cancelada' },
  { value: 'rejeitada', label: 'Rejeitada' },
]

export const fiscalTypeOptions = [
  { value: '', label: 'Todos os tipos' },
  { value: 'NFC-e', label: 'NFC-e' },
  { value: 'NF-e', label: 'NF-e' },
]
