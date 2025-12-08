<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <Navbar />
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">📊 Reportes Operativos</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Genera reportes personalizados con filtros avanzados</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar de Filtros -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-6 border border-gray-200 dark:border-gray-700 sticky top-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🔍 Filtros</h3>
            
            <form @submit.prevent="applyFilters" class="space-y-4">
              <!-- Estado -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Estado
                </label>
                <select
                  v-model="filters.status"
                  multiple
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                  size="5"
                >
                  <option value="pending">Pendiente</option>
                  <option value="in_progress">En Progreso</option>
                  <option value="completed">Completada</option>
                  <option value="paused">Pausada</option>
                  <option value="cancelled">Cancelada</option>
                </select>
              </div>

              <!-- Prioridad -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Prioridad
                </label>
                <select
                  v-model="filters.priority"
                  multiple
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                  size="4"
                >
                  <option value="low">Baja</option>
                  <option value="medium">Media</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>

              <!-- Usuario Asignado -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Usuario Asignado
                </label>
                <select
                  v-model="filters.assignee_id"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                >
                  <option :value="null">Todos</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- Flujo -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Flujo
                </label>
                <select
                  v-model="filters.flow_id"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                >
                  <option :value="null">Todos</option>
                  <option v-for="flow in flows" :key="flow.id" :value="flow.id">
                    {{ flow.name }}
                  </option>
                </select>
              </div>

              <!-- Fecha Desde -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Fecha Desde
                </label>
                <input
                  v-model="filters.date_from"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Fecha Hasta -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Fecha Hasta
                </label>
                <input
                  v-model="filters.date_to"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Solo Milestones -->
              <div>
                <label class="flex items-center cursor-pointer">
                  <input
                    v-model="filters.is_milestone"
                    type="checkbox"
                    class="w-4 h-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:bg-gray-700"
                  />
                  <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Solo Milestones
                  </span>
                </label>
              </div>

              <!-- Botones -->
              <div class="space-y-2 pt-4">
                <button
                  type="submit"
                  class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors"
                >
                  Aplicar Filtros
                </button>
                <button
                  type="button"
                  @click="clearFilters"
                  class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium transition-colors"
                >
                  Limpiar
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Contenido Principal -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Estadísticas -->
          <div v-if="stats" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-4 border border-gray-200 dark:border-gray-700">
              <p class="text-sm text-gray-600 dark:text-gray-400">Total Tareas</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-4 border border-gray-200 dark:border-gray-700">
              <p class="text-sm text-gray-600 dark:text-gray-400">Completadas</p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.by_status.completed }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-4 border border-gray-200 dark:border-gray-700">
              <p class="text-sm text-gray-600 dark:text-gray-400">Progreso Promedio</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ stats.avg_progress }}%</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-4 border border-gray-200 dark:border-gray-700">
              <p class="text-sm text-gray-600 dark:text-gray-400">Bloqueadas</p>
              <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ stats.blocked }}</p>
            </div>
          </div>

          <!-- Acciones de Exportación -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Exportar Reporte</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Descarga los resultados en tu formato preferido</p>
              </div>
              <div class="flex space-x-3">
                <button
                  @click="exportToCsv"
                  :disabled="loading || !tasks.length"
                  class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span>CSV</span>
                </button>
                <button
                  @click="exportToPdf"
                  :disabled="loading || !tasks.length"
                  class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                  <span>PDF</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Tabla de Resultados -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Resultados ({{ meta.total || 0 }} tareas)
              </h3>
            </div>

            <div v-if="loading" class="p-8 text-center">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              <p class="text-gray-600 dark:text-gray-400 mt-2">Cargando...</p>
            </div>

            <div v-else-if="tasks.length === 0" class="p-8 text-center">
              <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-gray-600 dark:text-gray-400">No se encontraron tareas con los filtros aplicados</p>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prioridad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Asignado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Flujo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progreso</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="task in tasks" :key="task.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ task.id }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ task.title }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getStatusClass(task.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                        {{ getStatusText(task.status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getPriorityClass(task.priority)" class="px-2 py-1 text-xs font-semibold rounded-full">
                        {{ getPriorityText(task.priority) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {{ task.assignee?.name || 'Sin asignar' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ task.flow?.name || '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ task.progress }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Paginación -->
            <div v-if="meta.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Página {{ meta.current_page }} de {{ meta.last_page }}
              </div>
              <div class="flex space-x-2">
                <button
                  @click="changePage(meta.current_page - 1)"
                  :disabled="meta.current_page === 1"
                  class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Anterior
                </button>
                <button
                  @click="changePage(meta.current_page + 1)"
                  :disabled="meta.current_page === meta.last_page"
                  class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Siguiente
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { reportsAPI } from '@/services/reports'
import { flowsAPI } from '@/services/api'
import axios from 'axios'
import Navbar from '@/components/Navbar.vue'

const loading = ref(false)
const tasks = ref([])
const stats = ref(null)
const users = ref([])
const flows = ref([])
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 50,
  total: 0
})

const filters = ref({
  status: [],
  priority: [],
  assignee_id: null,
  flow_id: null,
  date_from: '',
  date_to: '',
  is_milestone: false
})

const applyFilters = async () => {
  loading.value = true
  try {
    // Preparar filtros (solo enviar los que tienen valor)
    const activeFilters = {}
    if (filters.value.status.length) activeFilters.status = filters.value.status
    if (filters.value.priority.length) activeFilters.priority = filters.value.priority
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to
    if (filters.value.is_milestone) activeFilters.is_milestone = 1

    // Obtener datos
    const [reportData, statsData] = await Promise.all([
      reportsAPI.getReport(activeFilters, 1),
      reportsAPI.getStats(activeFilters)
    ])

    tasks.value = reportData.data
    meta.value = reportData.meta
    stats.value = statsData.data
  } catch (error) {
    console.error('Error al cargar reporte:', error)
    alert('Error al cargar el reporte')
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.value = {
    status: [],
    priority: [],
    assignee_id: null,
    flow_id: null,
    date_from: '',
    date_to: '',
    is_milestone: false
  }
  applyFilters()
}

const changePage = async (page) => {
  if (page < 1 || page > meta.value.last_page) return
  
  loading.value = true
  try {
    const activeFilters = {}
    if (filters.value.status.length) activeFilters.status = filters.value.status
    if (filters.value.priority.length) activeFilters.priority = filters.value.priority
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to
    if (filters.value.is_milestone) activeFilters.is_milestone = 1

    const reportData = await reportsAPI.getReport(activeFilters, page)
    tasks.value = reportData.data
    meta.value = reportData.meta
  } catch (error) {
    console.error('Error al cambiar página:', error)
  } finally {
    loading.value = false
  }
}

const exportToCsv = async () => {
  try {
    const activeFilters = {}
    if (filters.value.status.length) activeFilters.status = filters.value.status
    if (filters.value.priority.length) activeFilters.priority = filters.value.priority
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to
    if (filters.value.is_milestone) activeFilters.is_milestone = 1

    await reportsAPI.exportCsv(activeFilters)
  } catch (error) {
    console.error('Error al exportar CSV:', error)
    alert('Error al exportar a CSV')
  }
}

const exportToPdf = async () => {
  try {
    const activeFilters = {}
    if (filters.value.status.length) activeFilters.status = filters.value.status
    if (filters.value.priority.length) activeFilters.priority = filters.value.priority
    if (filters.value.assignee_id) activeFilters.assignee_id = filters.value.assignee_id
    if (filters.value.flow_id) activeFilters.flow_id = filters.value.flow_id
    if (filters.value.date_from) activeFilters.date_from = filters.value.date_from
    if (filters.value.date_to) activeFilters.date_to = filters.value.date_to
    if (filters.value.is_milestone) activeFilters.is_milestone = 1

    await reportsAPI.exportPdf(activeFilters)
  } catch (error) {
    console.error('Error al exportar PDF:', error)
    alert('Error al exportar a PDF')
  }
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
    in_progress: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
    completed: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
    paused: 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400',
    cancelled: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400',
    blocked: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400'
  }
  return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
}

const getStatusText = (status) => {
  const texts = {
    pending: 'Pendiente',
    in_progress: 'En Progreso',
    completed: 'Completada',
    paused: 'Pausada',
    cancelled: 'Cancelada',
    blocked: 'Bloqueada'
  }
  return texts[status] || status
}

const getPriorityClass = (priority) => {
  const classes = {
    low: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
    medium: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
    high: 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400',
    urgent: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400'
  }
  return classes[priority] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
}

const getPriorityText = (priority) => {
  const texts = {
    low: 'Baja',
    medium: 'Media',
    high: 'Alta',
    urgent: 'Urgente'
  }
  return texts[priority] || priority
}

const loadInitialData = async () => {
  try {
    const token = localStorage.getItem('token')
    
    // Cargar usuarios y flujos para los filtros
    const [usersRes, flowsRes] = await Promise.all([
      axios.get('http://localhost:8000/api/v1/users', {
        headers: { Authorization: `Bearer ${token}` }
      }).catch(() => ({ data: { data: [] } })),
      flowsAPI.getAll()
    ])

    users.value = usersRes.data.data || []
    flows.value = flowsRes.data.data || []

    // Cargar reporte inicial
    await applyFilters()
  } catch (error) {
    console.error('Error al cargar datos iniciales:', error)
  }
}

onMounted(() => {
  loadInitialData()
})
</script>
