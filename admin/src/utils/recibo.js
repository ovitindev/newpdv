/**
 * Monta o objeto que o ReciboVenda (o mesmo recibo do PC) espera a partir do
 * detalhe da venda devolvido pela API. `cliente` pode ser passado já como
 * objeto (com CPF/CNPJ) quando o app o tem; a API devolve só o nome.
 */
export function reciboDeVenda(d, { cliente } = {}) {
  return {
    id: d.id,
    data: d.data,
    itens: d.itensDetalhe,
    subtotal: d.subtotal,
    discountPercent: d.descontoPercent,
    discountValue: d.descontoValor,
    entrega: d.entrega,
    valorFrete: d.valorFrete,
    total: d.total,
    cliente: cliente ?? (d.cliente && d.cliente !== 'Consumidor Final' ? { nome: d.cliente } : null),
    vendedor: d.vendedor ? { nome: d.vendedor } : null,
    payments: d.pagamentos.map((p) => ({ id: p.id, method: p.metodo, valor: p.valor, parcelas: p.parcelas })),
  }
}

/**
 * Transforma o recibo da tela (elemento HTML) num PDF de bobina 80 mm — o mesmo
 * visual que o PC imprime. As bibliotecas só carregam quando alguém pede o PDF.
 */
export async function gerarPdfDoElemento(elemento) {
  const [{ toPng }, { jsPDF }] = await Promise.all([import('html-to-image'), import('jspdf')])

  const opcoes = { pixelRatio: 3, backgroundColor: '#ffffff', cacheBust: true }
  // A primeira passada "aquece" fontes e o logo (o Safari costuma sair sem imagem na 1ª).
  await toPng(elemento, opcoes)
  const imagem = await toPng(elemento, opcoes)

  const { width, height } = elemento.getBoundingClientRect()
  const larguraMm = 80
  const alturaMm = (larguraMm * height) / width

  const pdf = new jsPDF({ unit: 'mm', format: [larguraMm, alturaMm], compress: true })
  pdf.addImage(imagem, 'PNG', 0, 0, larguraMm, alturaMm)
  return pdf.output('blob')
}

export function baixarArquivo(blob, nome) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = nome
  document.body.appendChild(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(url), 10_000)
}

/** Abre o menu de compartilhar do celular (WhatsApp, e-mail...) com o PDF; sem suporte, baixa. */
export async function compartilharPdf(blob, nome, titulo) {
  const arquivo = new File([blob], nome, { type: 'application/pdf' })
  if (navigator.canShare?.({ files: [arquivo] })) {
    try {
      await navigator.share({ files: [arquivo], title: titulo })
    } catch (e) {
      if (e?.name !== 'AbortError') throw e // fechar o menu não é erro
    }
    return 'compartilhado'
  }
  baixarArquivo(blob, nome)
  return 'baixado'
}

export const podeCompartilharArquivo = () => {
  try {
    return Boolean(navigator.canShare?.({ files: [new File([''], 'a.pdf', { type: 'application/pdf' })] }))
  } catch {
    return false
  }
}
