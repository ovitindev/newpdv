<script setup>
import { ref } from 'vue'
import { KeyRound } from '@lucide/vue'
import { http } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'

const auth = useAuthStore()
const toast = useToastStore()

const form = ref({ senha_atual: '', novo_login: '', nova_senha: '', nova_senha_confirmation: '' })
const saving = ref(false)

async function submit() {
  const f = form.value
  if (!f.senha_atual) return toast.error('Informe a senha atual')
  if (!f.novo_login && !f.nova_senha) return toast.error('Informe um novo login e/ou uma nova senha')
  if (f.nova_senha && f.nova_senha.length < 8) return toast.error('A nova senha precisa ter pelo menos 8 caracteres')
  if (f.nova_senha !== f.nova_senha_confirmation) return toast.error('As senhas novas não conferem')

  saving.value = true
  try {
    const payload = { senha_atual: f.senha_atual }
    if (f.novo_login) payload.novo_login = f.novo_login
    if (f.nova_senha) {
      payload.nova_senha = f.nova_senha
      payload.nova_senha_confirmation = f.nova_senha_confirmation
    }
    const data = await http.put('/me/credenciais', payload)
    auth.user = data.user
    form.value = { senha_atual: '', novo_login: '', nova_senha: '', nova_senha_confirmation: '' }
    toast.success('Dados de acesso atualizados', 'Outros aparelhos conectados foram desconectados.')
  } catch (e) {
    toast.error('Não foi possível salvar', e.message)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-6 max-w-xl">
    <div>
      <h1 class="text-xl font-semibold text-ink">Minha conta</h1>
      <p class="text-sm text-ink-soft mt-1">Troque seu login e/ou sua senha de acesso.</p>
    </div>

    <Card class="p-5 sm:p-6">
      <form class="space-y-4" @submit.prevent="submit">
        <p class="text-sm text-ink-soft">
          Login atual: <strong class="text-ink">{{ auth.user?.email }}</strong>
        </p>
        <Input v-model="form.senha_atual" type="password" label="Senha atual" required autocomplete="current-password" />
        <Input v-model="form.novo_login" label="Novo login (deixe em branco para manter)" autocomplete="off" />
        <Input v-model="form.nova_senha" type="password" label="Nova senha (mín. 8 caracteres)" autocomplete="new-password" />
        <Input v-model="form.nova_senha_confirmation" type="password" label="Repita a nova senha" autocomplete="new-password" />
        <div class="flex justify-end">
          <Button type="submit" :loading="saving"><template #icon-left><KeyRound :size="16" /></template>Salvar</Button>
        </div>
      </form>
    </Card>
  </div>
</template>
