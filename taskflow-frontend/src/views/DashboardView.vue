<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <!-- Logo y nombre -->
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-blue-600">TaskFlow</h1>
          </div>

          <!-- Navegación -->
          <div class="flex items-center space-x-4">
            <router-link
              to="/dashboard"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
            >
              Dashboard
            </router-link>
            <router-link
              to="/flows"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
            >
              Flujos
            </router-link>
            <router-link
              to="/tasks"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
            >
              Tareas
            </router-link>
            <router-link
              to="/templates"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50"
            >
              Plantillas
            </router-link>

            <!-- Usuario -->
            <div class="flex items-center space-x-3 border-l pl-4">
              <span class="text-sm text-gray-700">{{ authStore.currentUser?.name }}</span>
              <button
                @click="handleLogout"
                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium"
              >
                Salir
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenido Principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Título -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
        <p class="text-gray-600 mt-1">Bienvenido, {{ authStore.currentUser?.name }}</p>
      </div>

      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Total Flujos -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Flujos Activos</p>
              <p class="text-3xl font-bold text-blue-600">{{ stats.activeFlows }}</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Card 2: Tareas Pendientes -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Tareas Pendientes</p>
              <p class="text-3xl font-bold text-yellow-600">{{ stats.pendingTasks }}</p>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
              <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Card 3: Tareas Completadas -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Completadas</p>
              <p class="text-3xl font-bold text-green-600">{{ stats.completedTasks }}</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Card 4: Plantillas -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm">Plantillas</p>
              <p class="text-3xl font-bold text-purple-600">{{ stats.templates }}</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
              <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Flujos Recientes -->
      <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-800">Flujos Recientes</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tareas</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progreso</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="flow in recentFlows" :key="flow.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ flow.name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(flow.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                    {{ getStatusText(flow.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ flow.tasks?.length || 0 }} tareas
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                      <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${calculateProgress(flow)}%`"></div>
                    </div>
                    <span class="text-sm text-gray-600">{{ calculateProgress(flow) }}%</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <router-link 
                    :to="`/flows/${flow.id}`"
                    class="text-blue-600 hover:text-blue-800 font-medium"
                  >
                    Ver detalles
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

const router = useRouter()
const authStore = useAuthStore()

const stats = ref({
  activeFlows: 0,
  pendingTasks: 0,
  completedTasks: 0,
  templates: 0
})

const recentFlows = ref([])

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

  } catch (error) {
    console.error('Error cargando datos:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>