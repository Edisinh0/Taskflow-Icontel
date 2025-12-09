<template>
  <Transition name="modal">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4 border border-white/10">
        <div class="sticky top-0 bg-slate-800 border-b border-white/5 px-6 py-4 flex justify-between items-center z-10">
          <h2 class="text-2xl font-bold text-white">
            {{ isEditMode ? 'Editar Tarea' : 'Nueva Tarea' }}
          </h2>
          <button @click="closeModal" class="text-slate-400 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Formulario -->
        <div class="px-6 pb-6 pt-6">
          <form @submit.prevent="handleSubmit">
            <!-- Título -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-300 mb-2">
              Título <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="formData.title"
              type="text"
              required
              class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              placeholder="Nombre de la tarea"
            />
            </div>

            <!-- Descripción -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-300 mb-2">
              Descripción
            </label>
            <textarea
              v-model="formData.description"
              rows="3"
              class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              placeholder="Describe detalles, requerimientos..."
            ></textarea>
            </div>

            <!-- Grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-5 mb-5">
              <!-- Responsable -->
              <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">
                  Responsable
                </label>
                <select
                  v-model="formData.assignee_id"
                  class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option :value="null">Sin asignar</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- Prioridad -->
              <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">
                  Prioridad
                </label>
                <select
                  v-model="formData.priority"
                  class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="low">Baja</option>
                  <option value="medium">Media</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>
            </div>

            <!-- Grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-5 mb-5">
              <!-- Estado -->
              <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">
                  Estado
                </label>
                <select
                  v-model="formData.status"
                  class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
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
            <div v-if="formData.status === 'blocked'" class="mb-5">
              <label class="block text-sm font-bold text-rose-400 mb-2">
                Razón del Bloqueo
              </label>
              <textarea
                v-model="formData.blocked_reason"
                rows="2"
                class="w-full px-4 py-3 bg-rose-900/20 border border-rose-500/30 rounded-xl text-white placeholder-rose-300/50 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all"
                placeholder="Explica por qué está bloqueada..."
              ></textarea>
            </div>

            <!-- Milestone Checkbox -->
            <div class="mb-6 p-4 bg-slate-900/50 rounded-xl border border-white/5">
              <label class="flex items-center cursor-pointer group">
                <input
                  v-model="formData.is_milestone"
                  type="checkbox"
                  class="w-5 h-5 text-blue-600 border-slate-700 bg-slate-800 rounded focus:ring-blue-500 focus:ring-offset-slate-900"
                />
                <span class="ml-3 text-sm font-bold text-slate-300 group-hover:text-white transition-colors">
                  ⭐ Esta tarea es un Milestone (Hito)
                </span>
              </label>
            </div>

            <!-- Dependencias -->
            <div class="mb-6 p-5 bg-slate-900/30 rounded-xl border border-white/5">
              <h4 class="text-sm font-bold text-slate-300 mb-4 flex items-center">
                🔗 Dependencias
              </h4>
              
              <!-- Tarea Precedente -->
              <div class="mb-5">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                   Dependencia de Tarea
                </label>
                <p class="text-xs text-slate-500 mb-2">
                  Esta tarea no puede iniciarse hasta que se complete la seleccionada
                </p>
                <select
                  v-model="formData.depends_on_task_id"
                  class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-xl text-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                >
                  <option :value="null">Ninguna</option>
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
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                   Dependencia de Milestone
                </label>
                <p class="text-xs text-slate-500 mb-2">
                  Esta tarea depende de que se complete el siguiente milestone
                </p>
                <select
                  v-model="formData.depends_on_milestone_id"
                  class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-xl text-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                >
                  <option :value="null">Ninguna</option>
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
            <div class="grid grid-cols-2 gap-5 mb-6">
              <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">
                  Inicio Estimado
                </label>
                <input
                  v-model="formData.estimated_start_at"
                  type="datetime-local"
                  class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                />
              </div>
              <div>
                <label class="block text-sm font-bold text-slate-300 mb-2">
                  Fin Estimado
                </label>
                <input
                  v-model="formData.estimated_end_at"
                  type="datetime-local"
                  class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                />
              </div>
            </div>

            <!-- Mensaje de error -->
            <div v-if="error" class="mb-6 p-4 bg-rose-900/20 border border-rose-500/30 text-rose-400 rounded-xl text-sm flex items-start">
              <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              {{ error }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3 border-t border-white/5 pt-6">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2.5 border border-slate-600/50 rounded-xl text-slate-300 hover:bg-slate-700 hover:text-white font-bold transition-all"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-bold shadow-lg shadow-blue-900/20 disabled:opacity-50 flex items-center"
              >
                <span v-if="loading" class="mr-2">
                  <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
                {{ isEditMode ? 'Actualizar' : 'Crear' }}
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
  if (!props.availableTasks) return []
  
  // Si estamos editando, excluir la tarea actual
  if (isEditMode.value && props.task) {
    const filtered = props.availableTasks.filter(task => task.id !== props.task.id)
    return filtered
  }
  
  return props.availableTasks
})

// Filtrar solo milestones de las tareas disponibles
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
      parent_task_id: defaults.parent_task_id || props.parentTaskId || null
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