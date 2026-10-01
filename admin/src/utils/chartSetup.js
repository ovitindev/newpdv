import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Legend, Filler)

ChartJS.defaults.font.family = "'Poppins', ui-sans-serif, system-ui, sans-serif"
ChartJS.defaults.font.size = 12
ChartJS.defaults.color = '#8f9895'
ChartJS.defaults.plugins.tooltip.backgroundColor = '#0a3d1e'
ChartJS.defaults.plugins.tooltip.titleFont = { family: "'Poppins', ui-sans-serif, system-ui, sans-serif", weight: '600' }
ChartJS.defaults.plugins.tooltip.bodyFont = { family: "'Poppins', ui-sans-serif, system-ui, sans-serif" }
ChartJS.defaults.plugins.tooltip.padding = 10
ChartJS.defaults.plugins.tooltip.cornerRadius = 8
ChartJS.defaults.plugins.tooltip.displayColors = false

// Chart.js desenha em <canvas>, que não entende var(--token) — precisa do
// valor já resolvido. Sem isso, uma cor como 'var(--color-brand-500)' vira
// preto no gráfico em vez do verde esperado.
export function resolveColor(color) {
  if (typeof color !== 'string') return color
  const match = color.match(/^var\((--[\w-]+)\)$/)
  if (!match) return color
  const resolved = getComputedStyle(document.documentElement).getPropertyValue(match[1]).trim()
  return resolved || color
}

export { ChartJS }
