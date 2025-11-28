<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex justify-between items-center mb-8">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Flujos de Trabajo</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Gestiona tus proyectos y flujos</p>
        </div>
        <button
          @click="openNewFlowModal"
          class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 font-medium flex items-center shadow-lg transition-all hover:shadow-xl"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nuevo Flujo
        </button>
      </div>

      <!-- Grid de Flujos -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="flow in flows" 
          :key="flow.id" 
          class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none hover:shadow-medium transition-all p-6 border border-gray-100 dark:border-gray-700 group"
        >
          <div class="flex items-start justify-between mb-3">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
              {{ flow.name }}
            </h3>
            <span :class="getStatusClass(flow.status)" class="px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap">
              {{ getStatusText(flow.status) }}
            </span>
          </div>
          
          <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
            {{ flow.description }}
          </p>
          
          <!-- Estadísticas -->
          <div class="flex items-center justify-between mb-4 text-sm">
            <div class="flex items-center text-gray-500 dark:text-gray-400">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              <span>{{ flow.tasks?.length || 0 }} tareas</span>
            </div>
            <div class="text-gray-500 dark:text-gray-400">
              <span class="font-semibold text-blue-600 dark:text-blue-400">{{ calculateProgress(flow) }}%</span>
            </div>
          </div>

          <!-- Barra de progreso -->
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
            <div 
              class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all"
              :style="`width: ${calculateProgress(flow)}%`"
            ></div>
          </div>

          <!-- Botones -->
          <div class="flex space-x-2">
            <router-link 
              :to="`/flows/${flow.id}`"
              class="flex-1 text-center bg-gradient-to-r from-blue-500 to-blue-600 text-white py-2.5 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all font-medium shadow-sm"
            >
              Ver Detalles
            </router-link>
            <button
              @click="openEditFlowModal(flow)"
              class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              title="Editar flujo"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Card vacía si no hay flujos -->
        <div v-if="flows.length === 0" class="col-span-full flex flex-col items-center justify-center py-12">
          <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">No hay flujos creados</p>
          <p class="text-gray-400 dark:text-gray-500 text-sm">Crea tu primer flujo para comenzar</p>
        </div>
      </div>
    </main>

    <!-- Modal de Flujo -->
    <FlowModal
      :is-open="showFlowModal"
      :flow="selectedFlow"
      :templates="templates"
      @close="closeFlowModal"
      @saved="handleFlowSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { flowsAPI, templatesAPI } from '@/services/api'
import FlowModal from '@/components/FlowModal.vue'
import Navbar from '@/components/Navbar.vue'

const router = useRouter()
const authStore = useAuthStore()
const flows = ref([])
const templates = ref([])
const showFlowModal = ref(false)
const selectedFlow = ref(null)

const openNewFlowModal = () => {
  selectedFlow.value = null
  showFlowModal.value = true
}

const openEditFlowModal = (flow) => {
  selectedFlow.value = flow
  showFlowModal.value = true
}

const closeFlowModal = () => {
  showFlowModal.value = false
  selectedFlow.value = null
}

const handleFlowSaved = async () => {
  await loadData()
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
    paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
    completed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
    cancelled: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400'
  }
  return classes[status] || 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-400'
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
    const [flowsResponse, templatesResponse] = await Promise.all([
      flowsAPI.getAll(),
      templatesAPI.getAll()
    ])
    flows.value = flowsResponse.data.data
    templates.value = templatesResponse.data.data
  } catch (error) {
    console.error('Error cargando datos:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>