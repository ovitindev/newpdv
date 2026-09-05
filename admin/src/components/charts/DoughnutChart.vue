<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import '@/utils/chartSetup'

const props = defineProps({
  items: { type: Array, required: true }, // [{ label, value, color }]
  height: { type: Number, default: 220 },
})

const chartData = computed(() => ({
  labels: props.items.map((item) => item.label),
  datasets: [
    {
      data: props.items.map((item) => item.value),
      backgroundColor: props.items.map((item) => item.color),
      borderWidth: 3,
      borderColor: '#ffffff',
      hoverOffset: 4,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '72%',
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.label}: ${ctx.parsed}%`,
      },
    },
  },
}
</script>

<template>
  <div :style="{ height: `${height}px` }">
    <Doughnut :data="chartData" :options="chartOptions" />
  </div>
</template>
