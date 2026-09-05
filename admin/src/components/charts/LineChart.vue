<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup'

const props = defineProps({
  labels: { type: Array, required: true },
  data: { type: Array, required: true },
  label: { type: String, default: 'Vendas' },
  height: { type: Number, default: 280 },
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      label: props.label,
      data: props.data,
      borderColor: '#16c43f',
      backgroundColor: 'rgba(22, 196, 63, 0.08)',
      pointBackgroundColor: '#16c43f',
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 5,
      pointHoverBorderWidth: 2,
      borderWidth: 2.5,
      fill: true,
      tension: 0.38,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(ctx.parsed.y)}`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: '#8f9895' },
    },
    y: {
      grid: { color: '#eaede9' },
      border: { display: false },
      ticks: {
        color: '#8f9895',
        callback: (value) => `R$ ${value >= 1000 ? `${value / 1000}k` : value}`,
      },
    },
  },
}
</script>

<template>
  <div :style="{ height: `${height}px` }">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
