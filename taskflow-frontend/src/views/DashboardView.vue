<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <Navbar />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Título -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Bienvenido, {{ authStore.currentUser?.name }}</p>
      </div>

      <!-- Estadísticas Principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Flujos Activos -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Flujos Activos</p>
              <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ stats.activeFlows }}</p>
              <p class="text-xs text-gray-400 mt-1">+{{ stats.flowsThisWeek }} esta semana</p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-xl">
              <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Pendientes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Tareas Pendientes</p>
              <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ stats.pendingTasks }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ stats.urgentTasks }} urgentes</p>
            </div>
            <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-xl">
              <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Completadas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Completadas Hoy</p>
              <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.completedToday }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ stats.completionRate }}% tasa de éxito</p>
            </div>
            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-xl">
              <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Vencidas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Tareas Vencidas</p>
              <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">{{ stats.overdueTasks }}</p>
              <p class="text-xs text-gray-400 mt-1">Requieren atención</p>
            </div>
            <div class="bg-red-100 dark:bg-red-900/30 p-3 rounded-xl">
              <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos y Métricas -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Tendencia de Tareas (Últimos 7 días) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-6 border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">📊 Tendencia de Tareas (7 días)</h3>
          <div class="h-64">
            <Line v-if="taskTrendData.datasets[0].data.length > 0" :data="taskTrendData" :options="chartOptions" />
            <p v-else class="text-gray-400 dark:text-gray-500 text-center pt-20">No hay datos disponibles</p>
          </div>
        </div>

        <!-- Estado de Tareas por Prioridad -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft p-6 border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">🎯 Tareas por Prioridad</h3>
          <div class="h-64">
            <Doughnut v-if="priorityChartData.datasets[0].data.some(val => val > 0)" :data="priorityChartData" :options="doughnutOptions" />
            <p v-else class="text-gray-400 dark:text-gray-500 text-center pt-20">No hay datos disponibles</p>
          </div>
        </div>
      </div>

      <!-- Resumen de Productividad -->
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-2xl font-bold mb-2">🚀 Tu Productividad Esta Semana</h3>
            <p class="text-blue-100">Has completado {{ stats.completedThisWeek }} tareas de {{ stats.totalThisWeek }}</p>
          </div>
          <div class="text-right">
            <p class="text-5xl font-bold">{{ Math.round((stats.completedThisWeek / stats.totalThisWeek) * 100) || 0 }}%</p>
            <p class="text-blue-100 text-sm">Tasa de finalización</p>
          </div>
        </div>
        <div class="w-full bg-blue-400/30 rounded-full h-3 mt-4">
          <div 
            class="bg-white h-3 rounded-full transition-all"
            :style="`width: ${Math.round((stats.completedThisWeek / stats.totalThisWeek) * 100) || 0}%`"
          ></div>
        </div>
      </div>

      <!-- Tareas Urgentes y Flujos Recientes -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tareas Urgentes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-100 dark:border-gray-700">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">🔥 Tareas Urgentes</h3>
          </div>
          <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div v-for="task in urgentTasks" :key="task.id" class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ task.title }}</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ task.flow?.name }}</p>
                </div>
                <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 text-xs font-semibold rounded-full">
                  {{ getDaysRemaining(task.estimated_end_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Flujos Recientes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-100 dark:border-gray-700">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">📁 Flujos Recientes</h3>
          </div>
          <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <router-link
              v-for="flow in recentFlows"
              :key="flow.id"
              :to="`/flows/${flow.id}`"
              class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ flow.name }}</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ flow.tasks?.length || 0 }} tareas</p>
                </div>
                <div class="flex flex-col items-end space-y-1">
                  <span :class="getStatusClass(flow.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                    {{ getStatusText(flow.status) }}
                  </span>
                  <div class="w-32"> <!-- Increased width for progress bar container -->
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                      <span>Progreso</span>
                      <span class="font-semibold">{{ calculateProgress(flow) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div
                        class="bg-blue-600 h-2 rounded-full transition-all"
                        :style="`width: ${calculateProgress(flow)}%`"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { flowsAPI, tasksAPI } from '@/services/api'
import { Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title
} from 'chart.js'
import Navbar from '@/components/Navbar.vue'

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, Title)

const authStore = useAuthStore()

const stats = ref({
  activeFlows: 0,
  pendingTasks: 0,
  completedToday: 0,
  overdueTasks: 0,
  urgentTasks: 0,
  flowsThisWeek: 0,
  completedThisWeek: 0,
  totalThisWeek: 0,
  completionRate: 0
})

const urgentTasks = ref([])
const recentFlows = ref([])

const taskTrendData = ref({
  labels: [],
  datasets: [{
    label: 'Completadas',
    data: [],
    borderColor: '#3B82F6',
    backgroundColor: (context) => {
      const ctx = context.chart.ctx;
      const gradient = ctx.createLinearGradient(0, 0, 0, 300);
      gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
      gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
      return gradient;
    },
    tension: 0.4,
    fill: true,
    borderWidth: 3,
    pointBackgroundColor: '#3B82F6',
    pointBorderColor: '#fff',
    pointBorderWidth: 2,
    pointRadius: 5,
    pointHoverRadius: 8,
    pointHoverBackgroundColor: '#3B82F6',
    pointHoverBorderColor: '#fff',
    pointHoverBorderWidth: 3
  }]
})

const priorityChartData = ref({
  labels: ['Baja', 'Media', 'Alta', 'Urgente'],
  datasets: [{
    data: [0, 0, 0, 0],
    backgroundColor: [
      'rgba(59, 130, 246, 0.8)',   // Azul para Baja
      'rgba(252, 211, 77, 0.8)',   // Amarillo para Media
      'rgba(249, 115, 22, 0.8)',   // Naranja para Alta
      'rgba(239, 68, 68, 0.8)'     // Rojo para Urgente
    ],
    borderColor: [
      'rgba(59, 130, 246, 1)',
      'rgba(252, 211, 77, 1)',
      'rgba(249, 115, 22, 1)',
      'rgba(239, 68, 68, 1)'
    ],
    borderWidth: 2,
    hoverBackgroundColor: [
      'rgba(59, 130, 246, 1)',
      'rgba(252, 211, 77, 1)',
      'rgba(249, 115, 22, 1)',
      'rgba(239, 68, 68, 1)'
    ],
    hoverBorderColor: '#fff',
    hoverBorderWidth: 4
  }]
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: { 
      display: true,
      labels: {
        color: '#6B7280',
        font: {
          size: 12,
          weight: 'bold'
        },
        padding: 15,
        usePointStyle: true
      }
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#3B82F6',
      borderWidth: 2,
      padding: 12,
      displayColors: true,
      callbacks: {
        label: function(context) {
          return ` ${context.dataset.label}: ${context.parsed.y} tareas`;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
        color: '#6B7280'
      },
      grid: {
        color: 'rgba(107, 114, 128, 0.1)',
        drawBorder: false
      }
    },
    x: {
      ticks: {
        color: '#6B7280'
      },
      grid: {
        display: false
      }
    }
  },
  animation: {
    duration: 2000,
    easing: 'easeInOutQuart',
    onProgress: function(animation) {
      // Animación suave durante el progreso
    },
    onComplete: function() {
      // Animación completada
    }
  },
  hover: {
    mode: 'nearest',
    intersect: true,
    animationDuration: 400
  },
  elements: {
    line: {
      tension: 0.4,
      borderWidth: 3,
      borderCapStyle: 'round',
      borderJoinStyle: 'round',
      fill: true
    },
    point: {
      radius: 5,
      hoverRadius: 8,
      hitRadius: 10,
      borderWidth: 2,
      hoverBorderWidth: 3
    }
  }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: {
    legend: { 
      position: 'bottom',
      labels: {
        color: '#6B7280',
        font: {
          size: 12,
          weight: 'bold'
        },
        padding: 15,
        usePointStyle: true,
        pointStyle: 'circle'
      }
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#3B82F6',
      borderWidth: 2,
      padding: 12,
      callbacks: {
        label: function(context) {
          const label = context.label || '';
          const value = context.parsed || 0;
          const total = context.dataset.data.reduce((a, b) => a + b, 0);
          const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
          return ` ${label}: ${value} tareas (${percentage}%)`;
        }
      }
    }
  },
  animation: {
    animateRotate: true,
    animateScale: true,
    duration: 2000,
    easing: 'easeInOutQuart'
  },
  hover: {
    mode: 'nearest',
    animationDuration: 400
  },
  elements: {
    arc: {
      borderWidth: 3,
      borderColor: '#fff',
      hoverBorderWidth: 5,
      hoverOffset: 15
    }
  }
}

const getDaysRemaining = (date) => {
  if (!date) return 'Sin fecha'
  const days = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24))
  if (days < 0) return `Vencida hace ${Math.abs(days)}d`
  if (days === 0) return 'Vence hoy'
  return `${days}d restantes`
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
    paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
    completed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  const texts = { active: 'Activo', paused: 'Pausado', completed: 'Completado' }
  return texts[status] || status
}

const calculateProgress = (flow) => {
  if (!flow.tasks?.length) return 0
  const completed = flow.tasks.filter(t => t.status === 'completed').length
  return Math.round((completed / flow.tasks.length) * 100)
}

const loadData = async () => {
  try {
    const [flowsRes, tasksRes] = await Promise.all([
      flowsAPI.getAll(),
      tasksAPI.getAll()
    ])

    const flows = flowsRes.data.data
    const tasks = tasksRes.data.data

    stats.value = {
      activeFlows: flows.filter(f => f.status === 'active').length,
      pendingTasks: tasks.filter(t => ['pending', 'in_progress'].includes(t.status)).length,
      completedToday: tasks.filter(t => t.status === 'completed' && isToday(t.updated_at)).length,
      overdueTasks: tasks.filter(t => t.estimated_end_at && new Date(t.estimated_end_at) < new Date() && t.status !== 'completed').length,
      urgentTasks: tasks.filter(t => t.priority === 'urgent' && t.status !== 'completed').length,
      flowsThisWeek: flows.filter(f => isThisWeek(f.created_at)).length,
      completedThisWeek: tasks.filter(t => t.status === 'completed' && isThisWeek(t.updated_at)).length,
      totalThisWeek: tasks.filter(t => isThisWeek(t.created_at)).length,
      completionRate: Math.round((tasks.filter(t => t.status === 'completed').length / tasks.length) * 100) || 0
    }

    urgentTasks.value = tasks
      .filter(t => t.priority === 'urgent' && t.status !== 'completed')
      .slice(0, 5)

    recentFlows.value = flows.slice(0, 5)

    // Calcular datos reales para los últimos 7 días
    const last7Days = []
    const completedByDay = []
    
    for (let i = 6; i >= 0; i--) {
      const date = new Date()
      date.setDate(date.getDate() - i)
      date.setHours(0, 0, 0, 0)
      
      const nextDay = new Date(date)
      nextDay.setDate(nextDay.getDate() + 1)
      
      // Nombre del día
      const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
      last7Days.push(dayNames[date.getDay()])
      
      // Contar tareas completadas ese día
      const completedCount = tasks.filter(t => {
        if (t.status !== 'completed' || !t.updated_at) return false
        const taskDate = new Date(t.updated_at)
        return taskDate >= date && taskDate < nextDay
      }).length
      
      completedByDay.push(completedCount)
    }
    
    taskTrendData.value = {
      labels: last7Days,
      datasets: [{
        label: 'Completadas',
        data: completedByDay,
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4
      }]
    }

    // Actualizar gráficos con datos reales
    priorityChartData.value.datasets[0].data = [
      tasks.filter(t => t.priority === 'low').length,
      tasks.filter(t => t.priority === 'medium').length,
      tasks.filter(t => t.priority === 'high').length,
      tasks.filter(t => t.priority === 'urgent').length
    ]
  } catch (error) {
    console.error('Error cargando datos:', error)
  }
}

const isToday = (date) => {
  const today = new Date()
  const d = new Date(date)
  return d.toDateString() === today.toDateString()
}

const isThisWeek = (date) => {
  const d = new Date(date)
  const today = new Date()
  const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
  return d >= weekAgo && d <= today
}

onMounted(() => {
  loadData()
})
</script>