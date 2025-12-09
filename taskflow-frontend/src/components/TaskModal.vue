<template>
  <Transition name="modal">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4">
        <div class="sticky top-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center z-10">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ isEditMode ? 'Editar Tarea' : 'Nueva Tarea' }}
          </h2>
          <button @click="closeModal" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Formulario -->
        <div class="px-6 pb-6">
          <form @submit.prevent="handleSubmit">
            <!-- Título -->
            <div class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Título <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.title"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              placeholder="Nombre de la tarea"
            />
            </div>

            <!-- Descripción -->
            <div class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Descripción
            </label>
            <textarea
              v-model="formData.description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
              placeholder="Describe la tarea..."
            ></textarea>
            </div>

            <!-- Grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-4 mb-4">
              <!-- Responsable -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Responsable
                </label>
                <select
                  v-model="formData.assignee_id"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                  <option :value="null">Sin asignar</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- Prioridad -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Prioridad
                </label>
                <select
                  v-model="formData.priority"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
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
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Estado
                </label>
                <select
                  v-model="formData.status"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                  <option value="pending">Pendiente</option>
                  <option value="in_progress">En Progreso</option>
                  <option value="completed">Completada</option>
                  <option value="blocked">Bloqueada</option>
                  <option value="paused">Pausada</option>
                  <option value="cancelled">Cancelada</option>
                </select>
              </div>
            </div>

            <!-- Razón de bloqueo (solo si está bloqueada) -->
            <div v-if="formData.status === 'blocked'" class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Razón del Bloqueo
              </label>
              <textarea
                v-model="formData.blocked_reason"
                rows="2"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                placeholder="Explica por qué está bloqueada..."
              ></textarea>
            </div>

            <!-- Milestone Checkbox -->
            <div class="mb-4">
              <label class="flex items-center cursor-pointer">
                <input
                  v-model="formData.is_milestone"
                  type="checkbox"
                  class="w-4 h-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:bg-gray-700"
                />
                <span class="ml-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                  ⭐ Esta tarea es un Milestone (Hito)
                </span>
              </label>
            </div>

            <!-- Dependencias -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
              <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                🔗 Dependencias
              </h4>
              
              <!-- Tarea Precedente -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  📋 Tarea Precedente
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                  Esta tarea no puede iniciarse hasta que se complete la tarea seleccionada
                </p>
                <select
                  v-model="formData.depends_on_task_id"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                >
                  <option :value="null">Sin dependencia de tarea</option>
                  <option
                    v-for="availableTask in filteredAvailableTasks"
                    :key="availableTask.id"
                    :value="availableTask.id"
                  >
                    {{ availableTask.title }}
                  </option>
                </select>
              </div>

              <!-- Milestone Requerido -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  ⭐ Milestone Requerido
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                  Esta tarea no puede iniciarse hasta que se complete el milestone seleccionado
                </p>
                <select
                  v-model="formData.depends_on_milestone_id"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                >
                  <option :value="null">Sin dependencia de milestone</option>
                  <option
                    v-for="milestone in availableMilestones"
                    :key="milestone.id"
                    :value="milestone.id"
                  >
                    {{ milestone.title }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Fechas estimadas -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Fecha Estimada de Inicio
                </label>
                <input
                  v-model="formData.estimated_start_at"
                  type="datetime-local"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Fecha Estimada de Fin
                </label>
                <input
                  v-model="formData.estimated_end_at"
                  type="datetime-local"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                />
              </div>
            </div>

            <!-- Mensaje de error -->
            <div v-if="error" class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg text-sm">
              {{ error }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium"
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
  },
  availableTasks: {
    type: Array,
    default: () => []
  },
  initialData: {
    type: Object,
    default: () => null
  }
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const error = ref(null)

const isEditMode = computed(() => !!props.task)

// Filtrar tareas disponibles (excluir la tarea actual)
const filteredAvailableTasks = computed(() => {
  // console.log('📋 availableTasks:', props.availableTasks)
  // console.log('📝 Tarea actual:', props.task?.id)
  
  if (!props.availableTasks) return []
  
  // Si estamos editando, excluir la tarea actual
  if (isEditMode.value && props.task) {
    const filtered = props.availableTasks.filter(task => task.id !== props.task.id)
    return filtered
  }
  
  return props.availableTasks
})

// Filtrar solo milestones de las tareas disponibles (excluir la tarea actual)
const availableMilestones = computed(() => {
  return filteredAvailableTasks.value.filter(task => task.is_milestone)
})

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
  depends_on_task_id: null,
  depends_on_milestone_id: null,
  estimated_start_at: '',
  estimated_end_at: '',
  flow_id: null,
  parent_task_id: null
})

// Watch para cargar datos cuando se edita o cambia el flowId
// También maneja initialData para pre-llenar en modo creación
watch([() => props.task, () => props.flowId, () => props.initialData], ([newTask, newFlowId, newInitialData]) => {
  if (newTask) {
    // Cargar datos de tarea existente (Modo Edición)
    formData.value = {
      title: newTask.title || '',
      description: newTask.description || '',
      assignee_id: newTask.assignee_id || null,
      priority: newTask.priority || 'medium',
      status: newTask.status || 'pending',
      progress: newTask.progress || 0,
      is_milestone: newTask.is_milestone || false,
      blocked_reason: newTask.blocked_reason || '',
      depends_on_task_id: newTask.depends_on_task_id || null,
      depends_on_milestone_id: newTask.depends_on_milestone_id || null,
      estimated_start_at: newTask.estimated_start_at || '',
      estimated_end_at: newTask.estimated_end_at || '',
      flow_id: newTask.flow_id,
      parent_task_id: newTask.parent_task_id
    }
  } else {
    // Modo Creación
    // Usar initialData si existe, sino defaults
    const defaults = newInitialData || {}
    
    formData.value = {
      title: defaults.title || '',
      description: defaults.description || '',
      assignee_id: defaults.assignee_id || null,
      priority: defaults.priority || 'medium',
      status: defaults.status || 'pending',
      progress: defaults.progress || 0,
      is_milestone: defaults.is_milestone !== undefined ? defaults.is_milestone : false,
      blocked_reason: '',
      depends_on_task_id: defaults.depends_on_task_id || null,
      depends_on_milestone_id: defaults.depends_on_milestone_id || null,
      estimated_start_at: defaults.estimated_start_at || '',
      estimated_end_at: defaults.estimated_end_at || '',
      flow_id: newFlowId || null,
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