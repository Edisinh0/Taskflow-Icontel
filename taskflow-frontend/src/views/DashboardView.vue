<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido Principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Título -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Bienvenido, {{ authStore.currentUser?.name }}</p>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Total Flujos -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none p-6 border border-gray-100 dark:border-gray-700 transition-all hover:shadow-medium">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Flujos Activos</p>
              <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ stats.activeFlows }}</p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-xl">
              <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Card 2: Tareas Pendientes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none p-6 border border-gray-100 dark:border-gray-700 transition-all hover:shadow-medium">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Tareas Pendientes</p>
              <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ stats.pendingTasks }}</p>
            </div>
            <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-xl">
              <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Card 3: Tareas Completadas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none p-6 border border-gray-100 dark:border-gray-700 transition-all hover:shadow-medium">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Completadas</p>
              <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.completedTasks }}</p>
            </div>
            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-xl">
              <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Card 4: Plantillas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none p-6 border border-gray-100 dark:border-gray-700 transition-all hover:shadow-medium">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Plantillas</p>
              <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ stats.templates }}</p>
            </div>
            <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-xl">
              <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Gráfico de Estado de Tareas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none p-6 border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">📊 Estado de Tareas</h3>
          <div class="h-64 flex items-center justify-center">
            <Doughnut 
              v-if="taskStatusChartData.datasets[0].data.some(val => val > 0)"
              :data="taskStatusChartData" 
              :options="chartOptions" 
            />
            <p v-else class="text-gray-400 dark:text-gray-500">No hay datos disponibles</p>
          </div>
        </div>

        <!-- Gráfico de Tareas por Usuario -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none p-6 border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">👥 Tareas por Usuario</h3>
          <div class="h-64 flex items-center justify-center">
            <Bar 
              v-if="tasksByUserChartData.datasets[0].data.length > 0"
              :data="tasksByUserChartData" 
              :options="chartOptions" 
            />
            <p v-else class="text-gray-400 dark:text-gray-500">No hay datos disponibles</p>
          </div>
        </div>
      </div>

      <!-- Tabla de Flujos Recientes -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none border border-gray-100 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Flujos Recientes</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tareas</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progreso</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="flow in recentFlows" :key="flow.id" class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">{{ flow.name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(flow.status)" class="px-3 py-1 text-xs font-semibold rounded-full">
                    {{ getStatusText(flow.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ flow.tasks?.length || 0 }} tareas
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                      <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all" :style="`width: ${calculateProgress(flow)}%`"></div>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ calculateProgress(flow) }}%</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <router-link 
                    :to="`/flows/${flow.id}`"
                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors"
                  >
                    Ver detalles →
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { flowsAPI, tasksAPI, templatesAPI } from '@/services/api'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  BarElement,
  Title
} from 'chart.js'
import { Doughnut, Bar } from 'vue-chartjs'
import Navbar from '@/components/Navbar.vue'

// Registrar componentes de Chart.js
ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title)

const router = useRouter()
const authStore = useAuthStore()

const stats = ref({
  activeFlows: 0,
  pendingTasks: 0,
  completedTasks: 0,
  templates: 0
})

const recentFlows = ref([])

// Datos para gráfico de dona (estado de tareas)
const taskStatusChartData = ref({
  labels: ['Pendientes', 'En Progreso', 'Completadas', 'Bloqueadas'],
  datasets: [{
    data: [3, 4, 5, 1], // Datos hardcodeados temporalmente
    backgroundColor: ['#FCD34D', '#3B82F6', '#10B981', '#EF4444']
  }]
})

// Datos para gráfico de barras (tareas por usuario)
const tasksByUserChartData = ref({
  labels: ['Juan Pérez', 'María González', 'Carlos Rodríguez'],
  datasets: [{
    label: 'Tareas Asignadas',
    data: [5, 4, 3], // Datos hardcodeados temporalmente
    backgroundColor: '#3B82F6'
  }]
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    paused: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  const texts = {
    active: 'Activo',
    paused: 'Pausado',
    completed: 'Completado',
    cancelled: 'Cancelado'
  }
  return texts[status] || status
}

const calculateProgress = (flow) => {
  if (!flow.tasks || flow.tasks.length === 0) return 0
  const completed = flow.tasks.filter(t => t.status === 'completed').length
  return Math.round((completed / flow.tasks.length) * 100)
}

const loadData = async () => {
  try {
    // Cargar flujos
    const flowsResponse = await flowsAPI.getAll()
    const flows = flowsResponse.data.data
    recentFlows.value = flows.slice(0, 5)
    stats.value.activeFlows = flows.filter(f => f.status === 'active').length

    // Cargar tareas
    const tasksResponse = await tasksAPI.getAll()
    const tasks = tasksResponse.data.data
    stats.value.pendingTasks = tasks.filter(t => t.status === 'pending' || t.status === 'in_progress').length
    stats.value.completedTasks = tasks.filter(t => t.status === 'completed').length

    // Cargar plantillas
    const templatesResponse = await templatesAPI.getAll()
    stats.value.templates = templatesResponse.data.data.length

    // Datos para gráfico de estado de tareas
    taskStatusChartData.value.datasets[0].data = [
      tasks.filter(t => t.status === 'pending').length,
      tasks.filter(t => t.status === 'in_progress').length,
      tasks.filter(t => t.status === 'completed').length,
      tasks.filter(t => t.status === 'blocked').length
    ]

    // Datos para gráfico de tareas por usuario
    const tasksByUser = {}
    tasks.forEach(task => {
      if (task.assignee) {
        const name = task.assignee.name
        tasksByUser[name] = (tasksByUser[name] || 0) + 1
      }
    })

    tasksByUserChartData.value.labels = Object.keys(tasksByUser)
    tasksByUserChartData.value.datasets[0].data = Object.values(tasksByUser)

    // Debug - ver en consola
    console.log('📊 Datos del gráfico de estado:', taskStatusChartData.value)
    console.log('📊 Datos del gráfico de usuarios:', tasksByUserChartData.value)
    console.log('📊 Total de tareas cargadas:', tasks.length)

  } catch (error) {
    console.error('Error cargando datos:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>