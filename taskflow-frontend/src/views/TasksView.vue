<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Todas las Tareas</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Filtra y gestiona todas tus tareas</p>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
          </svg>
          Filtros
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Filtro por Estado -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado</label>
            <select v-model="filters.status" @change="applyFilters" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors">
              <option value="">Todos</option>
              <option value="pending">Pendiente</option>
              <option value="in_progress">En Progreso</option>
              <option value="completed">Completada</option>
              <option value="blocked">Bloqueada</option>
              <option value="paused">Pausada</option>
            </select>
          </div>

          <!-- Filtro por Prioridad -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prioridad</label>
            <select v-model="filters.priority" @change="applyFilters" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors">
              <option value="">Todas</option>
              <option value="low">Baja</option>
              <option value="medium">Media</option>
              <option value="high">Alta</option>
              <option value="urgent">Urgente</option>
            </select>
          </div>

          <!-- Filtro por Responsable -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Responsable</label>
            <select v-model="filters.assignee_id" @change="applyFilters" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors">
              <option value="">Todos</option>
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
          </div>

          <!-- Buscar por texto -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
            <input
              v-model="filters.search"
              @input="applyFilters"
              type="text"
              placeholder="Título de tarea..."
              class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 transition-colors"
            />
          </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center space-x-4">
            <label class="flex items-center cursor-pointer">
              <input v-model="filters.milestones_only" @change="applyFilters" type="checkbox" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500" />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Solo Milestones ⭐</span>
            </label>
            <label class="flex items-center cursor-pointer">
              <input v-model="filters.my_tasks_only" @change="applyFilters" type="checkbox" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500" />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Solo mis tareas</span>
            </label>
          </div>
          <button @click="clearFilters" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors">
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- Lista de Tareas -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none border border-gray-100 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Resultados: <span class="text-blue-600 dark:text-blue-400">{{ filteredTasks.length }}</span> tareas
          </h3>
          <div class="flex space-x-2">
            <button
              @click="viewMode = 'list'"
              :class="viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <button
              @click="viewMode = 'grid'"
              :class="viewMode === 'grid' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Vista Lista -->
        <div v-if="viewMode === 'list'" class="divide-y divide-gray-200 dark:divide-gray-700">
          <div
            v-for="task in filteredTasks"
            :key="task.id"
            class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors group"
            @click="goToFlow(task.flow_id)"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                  <span v-if="task.is_milestone" class="text-2xl">⭐</span>
                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ task.title }}
                  </h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ task.description }}</p>
                <div class="flex items-center space-x-6 text-sm">
                  <span class="flex items-center text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ task.assignee?.name || 'Sin asignar' }}
                  </span>
                  <span class="flex items-center text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    {{ task.progress }}%
                  </span>
                  <span :class="getPriorityColor(task.priority)" class="font-medium">
                    {{ getPriorityText(task.priority) }}
                  </span>
                </div>
              </div>
              <div class="ml-4 flex flex-col items-end space-y-2">
                <span :class="getStatusBadge(task.status)" class="px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap">
                  {{ getStatusText(task.status) }}
                </span>
                <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div 
                    class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all"
                    :style="`width: ${task.progress}%`"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="filteredTasks.length === 0" class="p-12 text-center">
            <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No se encontraron tareas</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Intenta cambiar los filtros</p>
          </div>
        </div>

        <!-- Vista Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
          <div
            v-for="task in filteredTasks"
            :key="task.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:shadow-lg dark:hover:shadow-none hover:border-blue-300 dark:hover:border-blue-700 transition-all cursor-pointer group"
            @click="goToFlow(task.flow_id)"
          >
            <div class="flex items-start justify-between mb-3">
              <span v-if="task.is_milestone" class="text-2xl">⭐</span>
              <span :class="getStatusBadge(task.status)" class="px-3 py-1 text-xs font-semibold rounded-full">
                {{ getStatusText(task.status) }}
              </span>
            </div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
              {{ task.title }}
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ task.description }}</p>
            
            <!-- Info adicional -->
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ task.assignee?.name || 'Sin asignar' }}
              </div>
              <div class="flex items-center justify-between">
                <span :class="getPriorityColor(task.priority)" class="text-xs font-semibold">
                  {{ getPriorityText(task.priority) }}
                </span>
                <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ task.progress }}%</span>
              </div>
            </div>

            <!-- Barra de progreso -->
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div 
                class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all"
                :style="`width: ${task.progress}%`"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { tasksAPI } from '@/services/api'
import Navbar from '@/components/Navbar.vue'

const router = useRouter()
const authStore = useAuthStore()

const tasks = ref([])
const users = ref([
  { id: 1, name: 'Admin TaskFlow' },
  { id: 2, name: 'Juan Pérez' },
  { id: 3, name: 'María González' },
  { id: 4, name: 'Carlos Rodríguez' }
])

const viewMode = ref('list')

const filters = ref({
  status: '',
  priority: '',
  assignee_id: '',
  search: '',
  milestones_only: false,
  my_tasks_only: false
})

const filteredTasks = computed(() => {
  let result = tasks.value

  if (filters.value.status) {
    result = result.filter(t => t.status === filters.value.status)
  }

  if (filters.value.priority) {
    result = result.filter(t => t.priority === filters.value.priority)
  }

  if (filters.value.assignee_id) {
    result = result.filter(t => t.assignee_id == filters.value.assignee_id)
  }

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    result = result.filter(t => 
      t.title.toLowerCase().includes(search) || 
      t.description?.toLowerCase().includes(search)
    )
  }

  if (filters.value.milestones_only) {
    result = result.filter(t => t.is_milestone)
  }

  if (filters.value.my_tasks_only) {
    result = result.filter(t => t.assignee_id === authStore.currentUser?.id)
  }

  return result
})

const applyFilters = () => {
  // Los filtros se aplican automáticamente por computed
}

const clearFilters = () => {
  filters.value = {
    status: '',
    priority: '',
    assignee_id: '',
    search: '',
    milestones_only: false,
    my_tasks_only: false
  }
}

const goToFlow = (flowId) => {
  router.push(`/flows/${flowId}`)
}

const getStatusBadge = (status) => {
  const badges = {
    pending: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
    in_progress: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
    completed: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
    blocked: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400',
    paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400'
  }
  return badges[status] || 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-400'
}

const getStatusText = (status) => {
  const texts = {
    pending: 'Pendiente',
    in_progress: 'En Progreso',
    completed: 'Completada',
    blocked: 'Bloqueada',
    paused: 'Pausada'
  }
  return texts[status] || status
}

const getPriorityColor = (priority) => {
  const colors = {
    low: 'text-blue-600 dark:text-blue-400',
    medium: 'text-yellow-600 dark:text-yellow-400',
    high: 'text-orange-600 dark:text-orange-400',
    urgent: 'text-red-600 dark:text-red-400'
  }
  return colors[priority] || 'text-gray-600 dark:text-gray-400'
}

const getPriorityText = (priority) => {
  const texts = {
    low: '🔵 Baja',
    medium: '🟡 Media',
    high: '🟠 Alta',
    urgent: '🔴 Urgente'
  }
  return texts[priority] || priority
}

onMounted(async () => {
  try {
    const response = await tasksAPI.getAll()
    tasks.value = response.data.data
  } catch (error) {
    console.error('Error cargando tareas:', error)
  }
})
</script>