<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">
          Mis Tareas
        </h1>
        <p class="text-slate-600 dark:text-slate-400">
          Gestiona tus tareas asignadas desde SweetCRM
        </p>
      </div>

      <!-- Filtros y búsqueda -->
      <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar tareas..."
              class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <select
            v-model="statusFilter"
            class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todos los estados</option>
            <option value="Not Started">No Iniciado</option>
            <option value="In Progress">En Progreso</option>
            <option value="Completed">Completado</option>
            <option value="Pending Input">Pendiente</option>
            <option value="Deferred">Diferido</option>
          </select>
          <select
            v-model="priorityFilter"
            class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todas las prioridades</option>
            <option value="High">Alta</option>
            <option value="Medium">Media</option>
            <option value="Low">Baja</option>
          </select>
          <button
            @click="fetchTasks"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Actualizar</span>
          </button>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6 text-center">
        <p class="text-red-600 dark:text-red-400">{{ error }}</p>
      </div>

      <!-- Tasks List -->
      <div v-else-if="filteredTasks.length > 0" class="space-y-4">
        <div
          v-for="task in filteredTasks"
          :key="task.id"
          class="bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-md transition-all p-6 border-l-4"
          :class="getPriorityBorderClass(task.priority)"
        >
          <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <!-- Task Info -->
            <div class="flex-1">
              <div class="flex items-start gap-3 mb-3">
                <input
                  type="checkbox"
                  :checked="task.status === 'Completed'"
                  @change="toggleTaskStatus(task)"
                  class="mt-1 w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                />
                <div class="flex-1">
                  <h3
                    class="text-lg font-bold text-slate-800 dark:text-white"
                    :class="{ 'line-through text-slate-400': task.status === 'Completed' }"
                  >
                    {{ task.name }}
                  </h3>
                  <div class="flex flex-wrap gap-2 mt-2">
                    <span
                      :class="getStatusClass(task.status)"
                      class="px-3 py-1 rounded-full text-xs font-medium"
                    >
                      {{ task.status }}
                    </span>
                    <span
                      :class="getPriorityClass(task.priority)"
                      class="px-3 py-1 rounded-full text-xs font-medium"
                    >
                      {{ task.priority }}
                    </span>
                  </div>
                </div>
              </div>

              <div v-if="task.description" class="mb-4 pl-8">
                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
                  {{ task.description }}
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pl-8">
                <div v-if="task.date_due" class="flex items-center text-sm">
                  <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span class="text-slate-600 dark:text-slate-400">
                    {{ formatDate(task.date_due) }}
                  </span>
                </div>

                <div v-if="task.parent_name" class="flex items-center text-sm">
                  <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  <span class="text-slate-600 dark:text-slate-400">
                    {{ task.parent_type }}: {{ task.parent_name }}
                  </span>
                </div>

                <div v-if="task.assigned_user_name" class="flex items-center text-sm">
                  <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="text-slate-600 dark:text-slate-400">
                    {{ task.assigned_user_name }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-2 md:w-48">
              <button
                @click="viewDetails(task)"
                class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition-colors text-sm font-medium"
              >
                Ver Detalles
              </button>
              <button
                v-if="task.status !== 'Completed'"
                @click="markAsCompleted(task)"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm font-medium"
              >
                Marcar Completada
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
        </svg>
        <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-300 mb-2">
          No hay tareas
        </h3>
        <p class="text-slate-600 dark:text-slate-400">
          No se encontraron tareas con los filtros seleccionados.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const tasks = ref([])
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')

const fetchTasks = async () => {
  loading.value = true
  error.value = null

  try {
    const token = authStore.token
    const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/tasks/my-tasks`, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    })

    tasks.value = response.data.data || response.data || []
  } catch (err) {
    console.error('Error al cargar tareas:', err)
    error.value = 'Error al cargar las tareas. Por favor, intenta de nuevo.'
  } finally {
    loading.value = false
  }
}

const filteredTasks = computed(() => {
  let result = tasks.value

  // Filtrar por búsqueda
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(task =>
      task.name?.toLowerCase().includes(query) ||
      task.description?.toLowerCase().includes(query)
    )
  }

  // Filtrar por estado
  if (statusFilter.value) {
    result = result.filter(task => task.status === statusFilter.value)
  }

  // Filtrar por prioridad
  if (priorityFilter.value) {
    result = result.filter(task => task.priority === priorityFilter.value)
  }

  return result
})

const getStatusClass = (status) => {
  const statusMap = {
    'Not Started': 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    'In Progress': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'Completed': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'Pending Input': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'Deferred': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return statusMap[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
}

const getPriorityClass = (priority) => {
  const priorityMap = {
    'High': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'Medium': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'Low': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  }
  return priorityMap[priority] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
}

const getPriorityBorderClass = (priority) => {
  const borderMap = {
    'High': 'border-red-500',
    'Medium': 'border-yellow-500',
    'Low': 'border-green-500'
  }
  return borderMap[priority] || 'border-slate-300 dark:border-slate-600'
}

const formatDate = (dateString) => {
  if (!dateString) return 'Sin fecha'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-CL', { year: 'numeric', month: 'short', day: 'numeric' })
}

const toggleTaskStatus = async (task) => {
  // TODO: Implementar toggle de estado
  console.log('Toggle status:', task)
}

const viewDetails = (task) => {
  // TODO: Implementar navegación a detalle de tarea
  console.log('Ver detalles de:', task)
}

const markAsCompleted = async (task) => {
  // TODO: Implementar marcar como completada
  console.log('Marcar como completada:', task)
  alert('Esta funcionalidad se implementará en la siguiente fase')
}

onMounted(() => {
  fetchTasks()
})
</script>
