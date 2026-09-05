<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus, Pencil, Trash2 } from '@lucide/vue'
import { getUsuarios, getPerfis } from '@/services/configuracoesService'
import { useToastStore } from '@/stores/toast'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import { formatDateTime } from '@/utils/format'

const toast = useToastStore()
const loading = ref(true)
const usuarios = ref([])
const perfis = ref([])
const search = ref('')

onMounted(async () => {
  const [usuariosData, perfisData] = await Promise.all([getUsuarios(), getPerfis()])
  usuarios.value = usuariosData
  perfis.value = perfisData
  loading.value = false
})

const filtered = computed(() => usuarios.value.filter((u) => u.nome.toLowerCase().includes(search.value.toLowerCase())))

const columns = [
  { key: 'nome', label: 'Usuário' },
  { key: 'cargo', label: 'Cargo' },
  { key: 'ultimoAcesso', label: 'Último acesso' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
]

const modalOpen = ref(false)
const editingId = ref(null)
const form = ref({ nome: '', email: '', cargo: '' })

function openCreate() {
  editingId.value = null
  form.value = { nome: '', email: '', cargo: perfis.value[0]?.nome ?? '' }
  modalOpen.value = true
}

function openEdit(usuario) {
  editingId.value = usuario.id
  form.value = { nome: usuario.nome, email: usuario.email, cargo: usuario.cargo }
  modalOpen.value = true
}

function submit() {
  if (!form.value.nome || !form.value.email) return
  if (editingId.value) {
    const index = usuarios.value.findIndex((u) => u.id === editingId.value)
    usuarios.value[index] = { ...usuarios.value[index], ...form.value }
    toast.success('Usuário atualizado')
  } else {
    usuarios.value.unshift({ id: Date.now(), ...form.value, ultimoAcesso: new Date().toISOString(), status: 'ativo' })
    toast.success('Usuário cadastrado', 'Um convite de acesso foi enviado por e-mail.')
  }
  modalOpen.value = false
}

function remove(usuario) {
  usuarios.value = usuarios.value.filter((u) => u.id !== usuario.id)
  toast.info('Usuário removido')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-ink">Usuários</h1>
        <p class="text-sm text-ink-soft mt-1">Pessoas com acesso ao painel administrativo.</p>
      </div>
      <Button @click="openCreate"><template #icon-left><Plus :size="16" /></template>Novo Usuário</Button>
    </div>

    <Card :padded="false" class="p-5 sm:p-6">
      <div class="mb-5 max-w-sm"><SearchInput v-model="search" placeholder="Buscar usuário..." /></div>

      <Table :columns="columns" :rows="filtered" :loading="loading" empty-icon="UserCog" empty-title="Nenhum usuário encontrado">
        <template #cell-nome="{ row }">
          <div class="flex items-center gap-2.5">
            <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600 text-xs font-semibold">
              {{ row.nome.slice(0, 2).toUpperCase() }}
            </span>
            <div>
              <p class="text-sm font-medium text-ink">{{ row.nome }}</p>
              <p class="text-xs text-ink-faint">{{ row.email }}</p>
            </div>
          </div>
        </template>
        <template #cell-ultimoAcesso="{ value }"><span class="text-ink-soft">{{ formatDateTime(value) }}</span></template>
        <template #cell-status="{ value }">
          <Badge :variant="value === 'ativo' ? 'success' : 'neutral'" dot>{{ value === 'ativo' ? 'Ativo' : 'Inativo' }}</Badge>
        </template>
        <template #cell-acoes="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-surface hover:text-ink" @click="openEdit(row)">
              <Pencil :size="15" />
            </button>
            <button class="flex size-8 items-center justify-center rounded-lg text-ink-faint hover:bg-danger-soft hover:text-danger" @click="remove(row)">
              <Trash2 :size="15" />
            </button>
          </div>
        </template>
      </Table>
    </Card>

    <Modal v-model="modalOpen" :title="editingId ? 'Editar usuário' : 'Novo usuário'" size="sm">
      <div class="space-y-4">
        <Input v-model="form.nome" label="Nome completo" required />
        <Input v-model="form.email" type="email" label="E-mail" required />
        <Select v-model="form.cargo" label="Perfil de acesso" :options="perfis.map((p) => ({ value: p.nome, label: p.nome }))" />
      </div>
      <template #footer>
        <Button variant="ghost" @click="modalOpen = false">Cancelar</Button>
        <Button @click="submit">{{ editingId ? 'Salvar' : 'Convidar usuário' }}</Button>
      </template>
    </Modal>
  </div>
</template>
