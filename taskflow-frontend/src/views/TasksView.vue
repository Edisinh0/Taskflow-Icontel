<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-12">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Todas las Tareas</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Filtra y gestiona todas tus tareas en un solo lugar</p>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 mb-8 border border-slate-200 dark:border-white/5">
        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center">
          <Filter class="w-5 h-5 mr-2 text-blue-500" />
          Filtros
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Filtro por Estado -->
          <div>
            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Estado</label>
            <select v-model="filters.status" @change="applyFilters" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
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
            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Prioridad</label>
            <select v-model="filters.priority" @change="applyFilters" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
              <option value="">Todas</option>
              <option value="low">Baja</option>
              <option value="medium">Media</option>
              <option value="high">Alta</option>
              <option value="urgent">Urgente</option>
            </select>
          </div>

          <!-- Filtro por Responsable -->
          <div>
            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Responsable</label>
            <select v-model="filters.assignee_id" @change="applyFilters" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
              <option value="">Todos</option>
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
          </div>

          <!-- Buscar por texto -->
          <div>
            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Buscar</label>
            <input
              v-model="filters.search"
              @input="applyFilters"
              type="text"
              placeholder="Título de tarea..."
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
            />
          </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-slate-200 dark:border-white/5">
          <div class="flex items-center space-x-6">
            <label class="flex items-center cursor-pointer group">
              <input v-model="filters.milestones_only" @change="applyFilters" type="checkbox" class="w-4 h-4 text-blue-600 rounded border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-blue-500 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800" />
              <div class="flex items-center ml-2 text-sm text-slate-500 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200 transition-colors">
                Solo Milestones <Target class="w-4 h-4 ml-1 text-yellow-500" />
              </div>
            </label>
            <label class="flex items-center cursor-pointer group">
              <input v-model="filters.my_tasks_only" @change="applyFilters" type="checkbox" class="w-4 h-4 text-blue-600 rounded border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:ring-blue-500 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800" />
              <span class="ml-2 text-sm text-slate-500 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200 transition-colors">Solo mis tareas</span>
            </label>
          </div>
          <button @click="clearFilters" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors flex items-center">
             <X class="w-4 h-4 mr-1" />
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- Lista de Tareas -->
      <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white">
            Resultados: <span class="text-blue-600 dark:text-blue-400">{{ filteredTasks.length }}</span> tareas
          </h3>
          <div class="flex space-x-2 bg-slate-200 dark:bg-slate-900/50 p-1 rounded-lg border border-slate-300 dark:border-white/5">
            <button
              @click="viewMode = 'list'"
              :class="viewMode === 'list' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-md' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
            >
              <List class="w-4 h-4" />
            </button>
            <button
              @click="viewMode = 'grid'"
              :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-md' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
            >
              <LayoutGrid class="w-4 h-4" />
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
                  <Target v-if="task.is_milestone" class="w-5 h-5 text-yellow-500" title="Milestone" />
                  <h4 class="text-lg font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ task.title }}
                  </h4>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 font-medium">{{ task.description || 'Sin descripción' }}</p>
                <div class="flex items-center space-x-6 text-sm">
                  <span class="flex items-center text-slate-600 dark:text-slate-500">
                    <User class="w-4 h-4 mr-1.5" />
                    {{ task.assignee?.name || 'Sin asignar' }}
                  </span>
                  <span class="flex items-center text-slate-600 dark:text-slate-500">
                    <TrendingUp class="w-4 h-4 mr-1.5" />
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
                <div class="w-28 bg-slate-200 dark:bg-slate-700/50 rounded-full h-1.5 overflow-hidden">
                  <div 
                    class="bg-blue-500 h-1.5 rounded-full transition-all duration-500"
                    :style="`width: ${task.progress}%`"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="filteredTasks.length === 0" class="p-16 text-center">
            <div class="bg-slate-100 dark:bg-slate-800/50 p-4 rounded-full inline-block mb-4">
              <Inbox class="w-12 h-12 text-slate-400 dark:text-slate-500" />
            </div>
            <p class="text-slate-800 dark:text-white text-lg font-bold">No se encontraron tareas</p>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Intenta ajustar los filtros de búsqueda</p>
          </div>
        </div>

        <!-- Vista Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
          <div
            v-for="task in filteredTasks"
            :key="task.id"
            class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl p-6 hover:shadow-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 hover:border-blue-500/30 transition-all cursor-pointer group"
            @click="goToFlow(task.flow_id)"
          >
            <div class="flex items-start justify-between mb-4">
              <Target v-if="task.is_milestone" class="w-5 h-5 text-yellow-500" title="Milestone" />
              <span v-else class="w-8"></span> <!-- Spacer if no milestone -->
              <span :class="getStatusBadge(task.status)" class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20">
                {{ getStatusText(task.status) }}
              </span>
            </div>
            
            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-1">
              {{ task.title }}
            </h4>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 line-clamp-2 h-10">{{ task.description }}</p>
            
            <!-- Info adicional -->
            <div class="space-y-3 mb-2">
              <div class="flex items-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                <User class="w-3 h-3 mr-1.5" />
                  {{ task.assignee?.name || 'Sin asignar' }}
              </div>
              <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-white/5">
                <span :class="getPriorityColor(task.priority)" class="text-xs font-bold uppercase">
                  {{ getPriorityText(task.priority) }}
                </span>
                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ task.progress }}%</span>
              </div>
            </div>

            <!-- Barra de progreso -->
            <div class="w-full bg-slate-100 dark:bg-slate-900 rounded-full h-1.5 overflow-hidden mt-3">
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
import Navbar from '@/components/AppNavbar.vue'
import { Filter, X, List, LayoutGrid, User, TrendingUp, Inbox, Target } from 'lucide-vue-next'

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