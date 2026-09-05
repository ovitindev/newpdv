<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Eye, EyeOff, LogIn, User, Lock, ShieldCheck } from '@lucide/vue'
import { useAuthStore } from '@/stores/auth'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import logo from '@/assets/img/logo.png'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const usuario = ref('')
const senha = ref('')
const showSenha = ref(false)
const loading = ref(false)
const error = ref('')

async function handleSubmit() {
  error.value = ''

  if (!usuario.value || !senha.value) {
    error.value = 'Informe usuário e senha.'
    return
  }

  loading.value = true
  try {
    await authStore.login(usuario.value.trim(), senha.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
    router.push(redirect)
  } catch (err) {
    error.value = err.message || 'Não foi possível entrar. Verifique suas credenciais.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen bg-surface">
    <!-- Painel de marca (oculto em telas pequenas) -->
    <div
      class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-brand-950 via-brand-800 to-brand-600 p-12 text-white lg:flex"
    >
      <div
        class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-400/20 blur-3xl"
      />
      <div
        class="pointer-events-none absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-brand-300/10 blur-3xl"
      />

      <div class="relative flex items-center gap-3">
        <img :src="logo" alt="NovaPDV" class="h-9 w-auto brightness-0 invert" />
      </div>

      <div class="relative space-y-6">
        <h1 class="text-3xl font-semibold leading-tight">
          Sua loja, do estoque ao caixa,<br />em um só lugar.
        </h1>
        <p class="max-w-md text-sm text-brand-100/80">
          Cadastre produtos, feche vendas no PDV e acompanhe o financeiro da sua empresa em tempo real.
        </p>
        <div class="flex items-center gap-2 text-xs text-brand-100/70">
          <ShieldCheck :size="16" :stroke-width="1.75" />
          <span>Acesso protegido e criptografado.</span>
        </div>
      </div>

      <p class="relative text-xs text-brand-100/60">&copy; {{ new Date().getFullYear() }} NovaPDV. Todos os direitos reservados.</p>
    </div>

    <!-- Formulário -->
    <div class="flex w-full flex-1 items-center justify-center px-6 py-12 lg:w-1/2">
      <div class="w-full max-w-sm animate-fade-in">
        <div class="mb-8 flex flex-col items-center gap-4 lg:hidden">
          <img :src="logo" alt="NovaPDV" class="h-10 w-auto" />
        </div>

        <div class="mb-8">
          <h2 class="text-2xl font-semibold text-ink">Entrar</h2>
          <p class="mt-1 text-sm text-ink-soft">Acesse o painel da sua loja.</p>
        </div>

        <form class="space-y-5" novalidate @submit.prevent="handleSubmit">
          <Input
            v-model="usuario"
            label="Usuário"
            placeholder="Digite seu usuário"
            autocomplete="username"
            :disabled="loading"
            required
          >
            <template #icon>
              <User :size="16" :stroke-width="1.75" />
            </template>
          </Input>

          <div class="relative">
            <Input
              v-model="senha"
              label="Senha"
              :type="showSenha ? 'text' : 'password'"
              placeholder="Digite sua senha"
              autocomplete="current-password"
              :disabled="loading"
              required
            >
              <template #icon>
                <Lock :size="16" :stroke-width="1.75" />
              </template>
            </Input>
            <button
              type="button"
              class="absolute right-3 top-[34px] text-ink-faint transition-colors hover:text-ink-soft"
              :aria-label="showSenha ? 'Ocultar senha' : 'Mostrar senha'"
              tabindex="-1"
              @click="showSenha = !showSenha"
            >
              <EyeOff v-if="showSenha" :size="16" :stroke-width="1.75" />
              <Eye v-else :size="16" :stroke-width="1.75" />
            </button>
          </div>

          <p v-if="error" class="rounded-[var(--radius-control)] bg-danger-soft px-3.5 py-2.5 text-sm text-danger">
            {{ error }}
          </p>

          <Button type="submit" variant="primary" size="lg" block :loading="loading">
            <template v-if="!loading" #icon-left>
              <LogIn :size="18" :stroke-width="1.75" />
            </template>
            Entrar
          </Button>
        </form>
      </div>
    </div>
  </div>
</template>
