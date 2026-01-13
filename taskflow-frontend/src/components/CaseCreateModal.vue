<template>
  <Transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 z-[80] overflow-y-auto px-4 py-8 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-white/5 w-full max-w-2xl">
        <!-- Header -->
        <div class="p-8 border-b border-slate-100 dark:border-white/5">
          <h2 class="text-2xl font-black text-slate-800 dark:text-white">Crear Nuevo Caso</h2>
          <p v-if="opportunityName" class="text-sm text-slate-500 dark:text-slate-400 mt-2">
            Vinculado a: <strong>{{ opportunityName }}</strong>
          </p>
        </div>

        <!-- Form -->
        <div class="p-8 space-y-6 max-h-[60vh] overflow-y-auto">
          <div>
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Asunto</label>
            <input
              v-model="form.subject"
              type="text"
              placeholder="Ingresa el asunto del caso"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-700 text-slate-800 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Descripción</label>
            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Describe el caso en detalle..."
              class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-700 text-slate-800 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all resize-none"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Prioridad</label>
              <select
                v-model="form.priority"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
              >
                <option value="P1">Alta</option>
                <option value="P2">Media</option>
                <option value="P3">Baja</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Asignar a</label>
              <select
                v-model="form.assigned_user_id"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
              >
                <option value="">Sin asignar</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-8 border-t border-slate-100 dark:border-white/5 flex justify-end gap-3 bg-slate-50/50 dark:bg-white/5">
          <button
            @click="$emit('close')"
            class="px-6 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors text-sm"
          >
            Cancelar
          </button>
          <button
            @click="createCase"
            :disabled="creating || !form.subject.trim()"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-colors text-sm flex items-center gap-2"
          >
            <Loader2 v-if="creating" :size="16" class="animate-spin" />
            {{ creating ? 'Creando...' : 'Crear Caso' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { Loader2 } from 'lucide-vue-next'

const props = defineProps({
  isOpen: Boolean,
  defaultAccountId: String,
  defaultOpportunityId: String,
  opportunityName: String
})

const emit = defineEmits(['close', 'case-created'])

const form = ref({
  subject: '',
  description: '',
  priority: 'P2',
  assigned_user_id: '',
  account_id: props.defaultAccountId,
  opportunity_id: props.defaultOpportunityId
})

const users = ref([])
const creating = ref(false)

const createCase = async () => {
  if (!form.value.subject.trim()) return

  creating.value = true
  try {
    const response = await api.post('/api/v1/cases', {
      subject: form.value.subject,
      description: form.value.description,
      priority: form.value.priority,
      assigned_user_id: form.value.assigned_user_id || null,
      account_id: form.value.account_id,
      opportunity_id: form.value.opportunity_id
    })

    emit('case-created', response.data.data)
    emit('close')
  } catch (error) {
    console.error('Error creating case:', error)
    alert('No se pudo crear el caso. Por favor intenta de nuevo.')
  } finally {
    creating.value = false
  }
}

onMounted(async () => {
  try {
    const res = await api.get('/api/v1/users')
    users.value = res.data.data || []
  } catch (err) {
    console.error('Error fetching users:', err)
  }
})
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}
.modal-enter-from {
  opacity: 0;
  transform: scale(0.95);
}
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
