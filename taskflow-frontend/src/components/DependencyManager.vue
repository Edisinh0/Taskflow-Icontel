<template>
  <!-- Overlay del Modal -->
  <Transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Fondo oscuro -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Contenido del Modal -->
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 z-10">
          <!-- Header -->
          <div class="flex justify-between items-start mb-6">
            <div>
              <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                🔗 Gestionar Dependencias
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ task?.title }}
              </p>
            </div>
            <button
              @click="closeModal"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Estado Actual -->
          <div v-if="task?.is_blocked" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-start">
              <span class="text-2xl mr-3">🔒</span>
              <div>
                <p class="font-semibold text-red-800 dark:text-red-400">Esta tarea está bloqueada</p>
                <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                  No se puede iniciar o completar hasta que se cumplan sus dependencias.
                </p>
              </div>
            </div>
          </div>

          <!-- Formulario -->
          <form @submit.prevent="handleSubmit">
            <!-- Tarea Precedente -->
            <div class="mb-6">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                📋 Tarea Precedente
              </label>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                Esta tarea no puede iniciarse hasta que se complete la tarea seleccionada
              </p>
              <select
                v-model="formData.depends_on_task_id"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              >
                <option :value="null">Sin dependencia de tarea</option>
                <option
                  v-for="availableTask in availableTasks"
                  :key="availableTask.id"
                  :value="availableTask.id"
                  :disabled="availableTask.id === task?.id"
                >
                  {{ availableTask.title }}
                  <span v-if="availableTask.status === 'completed'">✅</span>
                  <span v-else-if="availableTask.is_blocked">🔒</span>
                </option>
              </select>
              
              <!-- Info de la tarea seleccionada -->
              <div v-if="selectedPrecedentTask" class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium dark:text-white">{{ selectedPrecedentTask.title }}</span>
                  <span
                    :class="getStatusBadgeClass(selectedPrecedentTask.status)"
                    class="px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ getStatusText(selectedPrecedentTask.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Milestone Requerido -->
            <div class="mb-6">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                ⭐ Milestone Requerido
              </label>
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                Esta tarea no puede iniciarse hasta que se complete el milestone seleccionado
              </p>
              <select
                v-model="formData.depends_on_milestone_id"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              >
                <option :value="null">Sin dependencia de milestone</option>
                <option
                  v-for="milestone in availableMilestones"
                  :key="milestone.id"
                  :value="milestone.id"
                  :disabled="milestone.id === task?.id"
                >
                  {{ milestone.title }}
                  <span v-if="milestone.status === 'completed'">✅</span>
                  <span v-else-if="milestone.is_blocked">🔒</span>
                </option>
              </select>

              <!-- Info del milestone seleccionado -->
              <div v-if="selectedMilestone" class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium dark:text-white">⭐ {{ selectedMilestone.title }}</span>
                  <span
                    :class="getStatusBadgeClass(selectedMilestone.status)"
                    class="px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ getStatusText(selectedMilestone.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Advertencia de validación -->
            <div v-if="validationError" class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
              {{ validationError }}
            </div>

            <!-- Mensaje de error -->
            <div v-if="error" class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
              {{ error }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="loading || !!validationError"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {{ loading ? 'Guardando...' : 'Guardar Dependencias' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { tasksAPI } from '@/services/api'

console.log('✅ DependencyManager.vue cargado')

const props = defineProps({
  isOpen: Boolean,
  task: Object,
  availableTasks: {
    type: Array,
    default: () => []
  }
})

onMounted(() => {
  console.log('🎬 DependencyManager montado')
})

const emit = defineEmits(['close', 'updated'])

const loading = ref(false)
const error = ref(null)

const formData = ref({
  depends_on_task_id: null,
  depends_on_milestone_id: null
})

// Tareas disponibles (excluyendo la tarea actual y milestones)
const availableTasks = computed(() => {
  return props.availableTasks.filter(t => 
    t.id !== props.task?.id && !t.is_milestone
  )
})

// Milestones disponibles
const availableMilestones = computed(() => {
  return props.availableTasks.filter(t => 
    t.id !== props.task?.id && t.is_milestone
  )
})

// Tarea precedente seleccionada
const selectedPrecedentTask = computed(() => {
  if (!formData.value.depends_on_task_id) return null
  return props.availableTasks.find(t => t.id === formData.value.depends_on_task_id)
})

// Milestone seleccionado
const selectedMilestone = computed(() => {
  if (!formData.value.depends_on_milestone_id) return null
  return props.availableTasks.find(t => t.id === formData.value.depends_on_milestone_id)
})

// Validación de dependencias circulares
const validationError = computed(() => {
  // No puede depender de la misma tarea como precedente y milestone
  if (formData.value.depends_on_task_id && 
      formData.value.depends_on_milestone_id &&
      formData.value.depends_on_task_id === formData.value.depends_on_milestone_id) {
    return 'No puedes seleccionar la misma tarea como precedente y milestone'
  }
  return null
})

// Watch para cargar datos cuando se abre el modal
watch(() => props.isOpen, (isOpen) => {
  console.log('🔍 DependencyManager - Modal abierto:', isOpen)
  if (isOpen && props.task) {
    console.log('📋 Cargando tarea:', props.task)
    formData.value = {
      depends_on_task_id: props.task.depends_on_task_id || null,
      depends_on_milestone_id: props.task.depends_on_milestone_id || null
    }
    console.log('📝 FormData inicial:', formData.value)
    error.value = null
  }
})

const closeModal = () => {
  console.log('❌ Cerrando modal')
  emit('close')
}

const handleSubmit = async () => {
  console.log('🚀 handleSubmit ejecutándose')
  console.log('📊 FormData:', formData.value)
  console.log('⚠️ ValidationError:', validationError.value)
  
  if (validationError.value) {
    console.log('❌ Validación falló, abortando')
    return
  }

  loading.value = true
  error.value = null

  try {
    // Siempre enviar ambos campos para permitir limpiar dependencias
    const payload = {
      depends_on_task_id: formData.value.depends_on_task_id,
      depends_on_milestone_id: formData.value.depends_on_milestone_id
    }

    console.log('Enviando dependencias:', payload)
    
    const response = await tasksAPI.update(props.task.id, payload)
    console.log('Respuesta:', response.data)

    emit('updated')
    closeModal()
  } catch (err) {
    console.error('Error al actualizar dependencias:', err)
    console.error('Response:', err.response?.data)
    error.value = err.response?.data?.message || 'Error al actualizar las dependencias'
  } finally {
    loading.value = false
  }
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    blocked: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  const texts = {
    pending: 'Pendiente',
    blocked: 'Bloqueada',
    in_progress: 'En Progreso',
    paused: 'Pausada',
    completed: 'Completada',
    cancelled: 'Cancelada'
  }
  return texts[status] || status
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