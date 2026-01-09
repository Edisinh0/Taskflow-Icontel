<template>
  <Transition name="modal">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4 border border-slate-200 dark:border-white/10">
        <!-- Header -->
        <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-white/5 px-6 py-4 flex justify-between items-center z-10">
          <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
            Delegar Tarea
          </h2>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Contenido -->
        <div class="px-6 pb-6 pt-6">
          <form @submit.prevent="handleSubmit">
            <!-- Información de la tarea -->
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-500/20">
              <label class="block text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">
                Tarea a Delegar
              </label>
              <div class="flex items-center gap-3">
                <div class="p-2 bg-white dark:bg-blue-900/30 rounded-lg shadow-sm border border-blue-100 dark:border-blue-500/20">
                  <Hand :size="16" class="text-blue-500" />
                </div>
                <div>
                  <p class="text-sm font-black text-slate-800 dark:text-white">{{ task?.title }}</p>
                  <p class="text-xs font-bold text-slate-500 dark:text-slate-400 line-clamp-1">{{ task?.description }}</p>
                </div>
              </div>
            </div>

            <!-- Caso asociado (si aplica) -->
            <div v-if="task?.crmCase" class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/10 rounded-xl border border-purple-200 dark:border-purple-500/20">
              <label class="block text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-2">
                Caso CRM Asociado
              </label>
              <div class="flex items-center gap-3">
                <div class="p-2 bg-white dark:bg-purple-900/30 rounded-lg shadow-sm border border-purple-100 dark:border-purple-500/20">
                  <Briefcase :size="16" class="text-purple-500" />
                </div>
                <div>
                  <p class="text-sm font-black text-slate-800 dark:text-white">#{{ task.crmCase.case_number }}</p>
                  <p class="text-xs font-bold text-slate-500 dark:text-slate-400 line-clamp-1">{{ task.crmCase.subject }}</p>
                </div>
              </div>
            </div>

            <!-- Usuario responsable actual -->
            <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700">
              <label class="block text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">
                Responsable Actual
              </label>
              <p class="text-sm font-bold text-slate-800 dark:text-white">{{ task?.assignee?.name || 'Sin asignar' }}</p>
            </div>

            <!-- Seleccionar usuario de Operaciones -->
            <div class="mb-6">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">
                Delegar a Usuario de Operaciones <span class="text-rose-500">*</span>
              </label>
              <select
                v-model.number="formData.delegated_to_user_id"
                required
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              >
                <option value="" disabled>Selecciona un usuario de Operaciones...</option>
                <option v-for="user in operationsUsers" :key="user.id" :value="user.id">
                  {{ user.name }} ({{ user.email }})
                </option>
              </select>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                Solo se muestran usuarios del departamento de Operaciones
              </p>
            </div>

            <!-- Razón de la delegación -->
            <div class="mb-6">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">
                Razón de la Delegación <span class="text-rose-500">*</span>
              </label>
              <textarea
                v-model="formData.reason"
                required
                rows="4"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="Explica por qué delegas esta tarea a Operaciones..."
              ></textarea>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                Esto será visible para el usuario que reciba la tarea
              </p>
            </div>

            <!-- Nota informativa -->
            <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-xl border border-indigo-200 dark:border-indigo-500/20">
              <p class="text-xs text-indigo-700 dark:text-indigo-400">
                <span class="font-bold">Nota:</span> Al delegar esta tarea, el usuario seleccionado será el nuevo responsable y tendrá visibilidad completa del trabajo. La delegación se sincronizará con SugarCRM automáticamente.
              </p>
            </div>

            <!-- Botones de acción -->
            <div class="flex gap-4">
              <button
                type="button"
                @click="closeModal"
                class="flex-1 px-6 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-white font-bold rounded-xl transition-colors"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="isSubmitting || !formData.delegated_to_user_id || !formData.reason"
                class="flex-1 px-6 py-3 bg-blue-500 hover:bg-blue-600 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white font-bold rounded-xl transition-colors shadow-md hover:shadow-lg disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <Hand class="w-5 h-5" />
                {{ isSubmitting ? 'Delegando...' : 'Delegar Tarea' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import { useAuthStore } from '@/stores/auth'
import {
  X,
  Hand,
  Briefcase,
  Loader2
} from 'lucide-vue-next'
import api from '@/services/api'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  task: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'delegated'])

const tasksStore = useTasksStore()
const authStore = useAuthStore()

const formData = ref({
  delegated_to_user_id: '',
  reason: ''
})

const isSubmitting = ref(false)
const operationsUsers = ref([])

onMounted(async () => {
  await loadOperationsUsers()
})

watch(
  () => props.isOpen,
  (newVal) => {
    if (!newVal) {
      resetForm()
    }
  }
)

const loadOperationsUsers = async () => {
  try {
    const response = await api.get('users', {
      params: { department: 'Operaciones' }
    })
    operationsUsers.value = response.data.data || []
  } catch (error) {
    console.error('Error loading operations users:', error)
  }
}

const closeModal = () => {
  emit('close')
}

const resetForm = () => {
  formData.value = {
    delegated_to_user_id: '',
    reason: ''
  }
}

const handleSubmit = async () => {
  if (!formData.value.delegated_to_user_id || !formData.value.reason) {
    return
  }

  isSubmitting.value = true
  try {
    const result = await tasksStore.delegateTask(
      props.task.id,
      formData.value.delegated_to_user_id,
      formData.value.reason
    )

    emit('delegated', result)
    closeModal()
  } catch (error) {
    console.error('Error delegating task:', error)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
