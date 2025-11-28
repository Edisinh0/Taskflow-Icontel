<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar (reutilizar del dashboard) -->
    <nav class="bg-white shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-blue-600">Icontel</h1>
          </div>
          <div class="flex items-center space-x-4">
            <router-link to="/dashboard" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600">Dashboard</router-link>
            <router-link to="/flows" class="px-3 py-2 rounded-md text-sm font-medium text-blue-600 bg-blue-50">Flujos</router-link>
            <router-link to="/tasks" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600">Tareas</router-link>
            <router-link to="/templates" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600">Plantillas</router-link>
            <button @click="handleLogout" class="ml-4 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Salir</button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h2 class="text-3xl font-bold text-gray-800 mb-6">Flujos de Trabajo</h2>

      <!-- Grid de Flujos -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="flow in flows" :key="flow.id" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
          <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ flow.name }}</h3>
          <p class="text-gray-600 text-sm mb-4">{{ flow.description }}</p>
          
          <div class="flex items-center justify-between mb-4">
            <span :class="getStatusClass(flow.status)" class="px-3 py-1 text-xs font-semibold rounded-full">
              {{ getStatusText(flow.status) }}
            </span>
            <span class="text-sm text-gray-500">{{ flow.tasks?.length || 0 }} tareas</span>
          </div>

          <router-link 
            :to="`/flows/${flow.id}`"
            class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors"
          >
            Ver Detalles
          </router-link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { flowsAPI } from '@/services/api'

const router = useRouter()
const authStore = useAuthStore()
const flows = ref([])

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

onMounted(async () => {
  try {
    const response = await flowsAPI.getAll()
    flows.value = response.data.data
  } catch (error) {
    console.error('Error cargando flujos:', error)
  }
})
</script>