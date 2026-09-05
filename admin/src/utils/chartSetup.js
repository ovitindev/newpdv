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

export { ChartJS }
