<template>
  <!-- Overlay del Modal -->
  <Transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Fondo oscuro -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Contenido del Modal -->
        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 z-10">
          <!-- Header -->
          <div class="flex justify-between items-start mb-6">
            <h3 class="text-2xl font-bold text-gray-800">
              {{ isEditMode ? 'Editar Tarea' : 'Nueva Tarea' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Formulario -->
          <form @submit.prevent="handleSubmit">
            <!-- Título -->
            <div class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Título <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.title"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ej: Configurar servidor"
              />
            </div>

            <!-- Descripción -->
            <div class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Descripción
              </label>
              <textarea
                v-model="formData.description"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Describe la tarea..."
              ></textarea>
            </div>

            <!-- Grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-4 mb-4">
              <!-- Responsable -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Responsable
                </label>
                <select
                  v-model="formData.assignee_id"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option :value="null">Sin asignar</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- Prioridad -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Prioridad
                </label>
                <select
                  v-model="formData.priority"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="low">Baja</option>
                  <option value="medium">Media</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>
            </div>

            <!-- Grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-4 mb-4">
              <!-- Estado -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Estado
                </label>
                <select
                  v-model="formData.status"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="pending">Pendiente</option>
                  <option value="in_progress">En Progreso</option>
                  <option value="completed">Completada</option>
                  <option value="blocked">Bloqueada</option>
                  <option value="paused">Pausada</option>
                  <option value="cancelled">Cancelada</option>
                </select>
              </div>

              <!-- Progreso -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Progreso ({{ formData.progress }}%)
                </label>
                <input
                  v-model.number="formData.progress"
                  type="range"
                  min="0"
                  max="100"
                  step="5"
                  class="w-full"
                />
              </div>
            </div>

            <!-- Razón de bloqueo (solo si está bloqueada) -->
            <div v-if="formData.status === 'blocked'" class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Razón del Bloqueo
              </label>
              <textarea
                v-model="formData.blocked_reason"
                rows="2"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Explica por qué está bloqueada..."
              ></textarea>
            </div>

            <!-- Checkbox: Es Milestone -->
            <div class="mb-4">
              <label class="flex items-center">
                <input
                  v-model="formData.is_milestone"
                  type="checkbox"
                  class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm font-semibold text-gray-700">
                  ⭐ Esta tarea es un Milestone (Hito)
                </span>
              </label>
            </div>

            <!-- Fechas estimadas -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Fecha Estimada de Inicio
                </label>
                <input
                  v-model="formData.estimated_start_at"
                  type="datetime-local"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Fecha Estimada de Fin
                </label>
                <input
                  v-model="formData.estimated_end_at"
                  type="datetime-local"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            <!-- Mensaje de error -->
            <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
              {{ error }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50"
              >
                {{ loading ? 'Guardando...' : (isEditMode ? 'Actualizar' : 'Crear') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { tasksAPI } from '@/services/api'

const props = defineProps({
  isOpen: Boolean,
  task: Object,
  flowId: Number,
  parentTaskId: Number,
  users: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const error = ref(null)

const isEditMode = computed(() => !!props.task)

// Datos del formulario
const formData = ref({
  title: '',
  description: '',
  assignee_id: null,
  priority: 'medium',
  status: 'pending',
  progress: 0,
  is_milestone: false,
  blocked_reason: '',
  estimated_start_at: '',
  estimated_end_at: '',
  flow_id: props.flowId,
  parent_task_id: props.parentTaskId || null
})

// Watch para cargar datos cuando se edita
watch(() => props.task, (newTask) => {
  if (newTask) {
    formData.value = {
      title: newTask.title || '',
      description: newTask.description || '',
      assignee_id: newTask.assignee_id,
      priority: newTask.priority || 'medium',
      status: newTask.status || 'pending',
      progress: newTask.progress || 0,
      is_milestone: newTask.is_milestone || false,
      blocked_reason: newTask.blocked_reason || '',
      estimated_start_at: newTask.estimated_start_at || '',
      estimated_end_at: newTask.estimated_end_at || '',
      flow_id: newTask.flow_id,
      parent_task_id: newTask.parent_task_id
    }
  } else {
    // Resetear formulario para nueva tarea
    formData.value = {
      title: '',
      description: '',
      assignee_id: null,
      priority: 'medium',
      status: 'pending',
      progress: 0,
      is_milestone: false,
      blocked_reason: '',
      estimated_start_at: '',
      estimated_end_at: '',
      flow_id: props.flowId,
      parent_task_id: props.parentTaskId || null
    }
  }
}, { immediate: true })

const closeModal = () => {
  error.value = null
  emit('close')
}

const handleSubmit = async () => {
  try {
    loading.value = true
    error.value = null

    if (isEditMode.value) {
      // Actualizar tarea existente
      await tasksAPI.update(props.task.id, formData.value)
    } else {
      // Crear nueva tarea
      await tasksAPI.create(formData.value)
    }

    emit('saved')
    closeModal()
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar la tarea'
    console.error('Error:', err)
  } finally {
    loading.value = false
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