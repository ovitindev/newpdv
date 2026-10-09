<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const emit = defineEmits(['detected', 'error'])

const video = ref(null)
const erro = ref('')
let stream = null
let timer = null

onMounted(async () => {
  try {
    const detector = new window.BarcodeDetector({ formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'qr_code'] })
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
    video.value.srcObject = stream
    await video.value.play()
    timer = setInterval(async () => {
      try {
        const [achado] = await detector.detect(video.value)
        if (achado?.rawValue) emit('detected', achado.rawValue)
      } catch {
        // frame ainda sem imagem; tenta no próximo ciclo
      }
    }, 300)
  } catch (e) {
    erro.value = e?.name === 'NotAllowedError' ? 'Permita o uso da câmera para ler o código de barras.' : 'Não foi possível abrir a câmera.'
    emit('error', erro.value)
  }
})

onBeforeUnmount(() => {
  clearInterval(timer)
  stream?.getTracks().forEach((t) => t.stop())
})
</script>

<template>
  <div class="relative overflow-hidden rounded-2xl bg-black">
    <video ref="video" playsinline muted class="aspect-[3/4] w-full object-cover" />
    <div v-if="!erro" class="pointer-events-none absolute inset-x-8 top-1/2 h-0.5 -translate-y-1/2 bg-brand-400 shadow-[0_0_12px_2px_rgba(61,211,92,0.8)]" />
    <p v-if="erro" class="absolute inset-0 flex items-center justify-center p-6 text-center text-sm text-white">{{ erro }}</p>
  </div>
</template>
