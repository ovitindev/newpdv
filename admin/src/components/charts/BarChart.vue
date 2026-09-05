<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import '@/utils/chartSetup'

const props = defineProps({
  labels: { type: Array, required: true },
  data: { type: Array, required: true },
  label: { type: String, default: 'Faturamento' },
  height: { type: Number, default: 220 },
  colors: { type: Array, default: () => ['#84f03a', '#3dd35c', '#16c43f'] },
  horizontal: { type: Boolean, default: false },
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      label: props.label,
      data: props.data,
      backgroundColor: props.colors,
      borderRadius: 8,
      maxBarThickness: 56,
    },
  ],
}))

const chartOptions = computed(() => ({
  indexAxis: props.horizontal ? 'y' : 'x',
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(ctx.parsed[props.horizontal ? 'x' : 'y'])}`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: props.horizontal, color: '#eaede9' },
      border: { display: false },
      ticks: { color: '#8f9895' },
    },
    y: {
      grid: { display: !props.horizontal, color: '#eaede9' },
      border: { display: false },
      ticks: { color: '#8f9895' },
    },
  },
}))
</script>

<template>
  <div :style="{ height: `${height}px` }">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>
