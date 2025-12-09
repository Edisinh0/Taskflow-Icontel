<template>
  <div class="min-h-screen bg-slate-900 transition-colors pb-12">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-white tracking-tight">Todas las Tareas</h2>
        <p class="text-slate-400 mt-1 text-lg">Filtra y gestiona todas tus tareas en un solo lugar</p>
      </div>

      <!-- Filtros -->
      <div class="bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-lg p-6 mb-8 border border-white/5">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
          </svg>
          Filtros
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Filtro por Estado -->
          <div>
            <label class="block text-sm font-medium text-slate-400 mb-2">Estado</label>
            <select v-model="filters.status" @change="applyFilters" class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
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
            <label class="block text-sm font-medium text-slate-400 mb-2">Prioridad</label>
            <select v-model="filters.priority" @change="applyFilters" class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
              <option value="">Todas</option>
              <option value="low">Baja</option>
              <option value="medium">Media</option>
              <option value="high">Alta</option>
              <option value="urgent">Urgente</option>
            </select>
          </div>

          <!-- Filtro por Responsable -->
          <div>
            <label class="block text-sm font-medium text-slate-400 mb-2">Responsable</label>
            <select v-model="filters.assignee_id" @change="applyFilters" class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
              <option value="">Todos</option>
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
          </div>

          <!-- Buscar por texto -->
          <div>
            <label class="block text-sm font-medium text-slate-400 mb-2">Buscar</label>
            <input
              v-model="filters.search"
              @input="applyFilters"
              type="text"
              placeholder="Título de tarea..."
              class="w-full px-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
            />
          </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-white/5">
          <div class="flex items-center space-x-6">
            <label class="flex items-center cursor-pointer group">
              <input v-model="filters.milestones_only" @change="applyFilters" type="checkbox" class="w-4 h-4 text-blue-600 rounded border-slate-700 bg-slate-900 focus:ring-blue-500 focus:ring-offset-slate-800" />
              <span class="ml-2 text-sm text-slate-400 group-hover:text-slate-200 transition-colors">Solo Milestones ⭐</span>
            </label>
            <label class="flex items-center cursor-pointer group">
              <input v-model="filters.my_tasks_only" @change="applyFilters" type="checkbox" class="w-4 h-4 text-blue-600 rounded border-slate-700 bg-slate-900 focus:ring-blue-500 focus:ring-offset-slate-800" />
              <span class="ml-2 text-sm text-slate-400 group-hover:text-slate-200 transition-colors">Solo mis tareas</span>
            </label>
          </div>
          <button @click="clearFilters" class="text-sm text-blue-400 hover:text-blue-300 font-medium transition-colors flex items-center">
             <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- Lista de Tareas -->
      <div class="bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-lg border border-white/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-slate-800/50">
          <h3 class="text-lg font-bold text-white">
            Resultados: <span class="text-blue-400">{{ filteredTasks.length }}</span> tareas
          </h3>
          <div class="flex space-x-2 bg-slate-900/50 p-1 rounded-lg border border-white/5">
            <button
              @click="viewMode = 'list'"
              :class="viewMode === 'list' ? 'bg-slate-700 text-white shadow-md' : 'text-slate-400 hover:text-white'"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <button
              @click="viewMode = 'grid'"
              :class="viewMode === 'grid' ? 'bg-slate-700 text-white shadow-md' : 'text-slate-400 hover:text-white'"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Vista Lista -->
        <div v-if="viewMode === 'list'" class="divide-y divide-white/5">
          <div
            v-for="task in filteredTasks"
            :key="task.id"
            class="p-6 hover:bg-slate-700/30 cursor-pointer transition-colors group"
            @click="goToFlow(task.flow_id)"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                  <span v-if="task.is_milestone" class="text-xl" title="Milestone">⭐</span>
                  <h4 class="text-lg font-bold text-slate-200 group-hover:text-blue-400 transition-colors">
                    {{ task.title }}
                  </h4>
                </div>
                <p class="text-sm text-slate-400 mb-3 font-medium">{{ task.description || 'Sin descripción' }}</p>
                <div class="flex items-center space-x-6 text-sm">
                  <span class="flex items-center text-slate-500">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ task.assignee?.name || 'Sin asignar' }}
                  </span>
                  <span class="flex items-center text-slate-500">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    {{ task.progress }}%
                  </span>
                  <span :class="getPriorityColor(task.priority)" class="font-bold uppercase text-xs tracking-wide">
                    {{ getPriorityText(task.priority) }}
                  </span>
                </div>
              </div>
              <div class="ml-6 flex flex-col items-end space-y-3">
                <span :class="getStatusBadge(task.status)" class="px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full border border-current/20">
                  {{ getStatusText(task.status) }}
                </span>
                <div class="w-28 bg-slate-700/50 rounded-full h-1.5 overflow-hidden">
                  <div 
                    class="bg-blue-500 h-1.5 rounded-full transition-all duration-500"
                    :style="`width: ${task.progress}%`"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="filteredTasks.length === 0" class="p-16 text-center">
            <div class="bg-slate-800/50 p-4 rounded-full inline-block mb-4">
              <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <p class="text-white text-lg font-bold">No se encontraron tareas</p>
            <p class="text-slate-400 text-sm mt-1">Intenta ajustar los filtros de búsqueda</p>
          </div>
        </div>

        <!-- Vista Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
          <div
            v-for="task in filteredTasks"
            :key="task.id"
            class="bg-slate-800 border border-white/5 rounded-2xl p-6 hover:shadow-xl hover:bg-slate-700/50 hover:border-blue-500/30 transition-all cursor-pointer group"
            @click="goToFlow(task.flow_id)"
          >
            <div class="flex items-start justify-between mb-4">
              <span v-if="task.is_milestone" class="text-xl" title="Milestone">⭐</span>
              <span v-else class="w-8"></span> <!-- Spacer if no milestone -->
              <span :class="getStatusBadge(task.status)" class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20">
                {{ getStatusText(task.status) }}
              </span>
            </div>
            
            <h4 class="text-lg font-bold text-white mb-2 group-hover:text-blue-400 transition-colors line-clamp-1">
              {{ task.title }}
            </h4>
            <p class="text-sm text-slate-400 mb-6 line-clamp-2 h-10">{{ task.description }}</p>
            
            <!-- Info adicional -->
            <div class="space-y-3 mb-2">
              <div class="flex items-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ task.assignee?.name || 'Sin asignar' }}
              </div>
              <div class="flex items-center justify-between pt-2 border-t border-white/5">
                <span :class="getPriorityColor(task.priority)" class="text-xs font-bold uppercase">
                  {{ getPriorityText(task.priority) }}
                </span>
                <span class="text-sm font-bold text-blue-400">{{ task.progress }}%</span>
              </div>
            </div>

            <!-- Barra de progreso -->
            <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden mt-3">
              <div 
                class="bg-blue-500 h-1.5 rounded-full transition-all duration-500"
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