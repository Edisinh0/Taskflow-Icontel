<template>
  <div class="min-h-screen bg-slate-900 transition-colors">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex justify-between items-center mb-8">
        <div>
          <h2 class="text-3xl font-bold text-white tracking-tight">Flujos de Trabajo</h2>
          <p class="text-slate-400 mt-1 text-lg">Gestiona tus proyectos y flujos</p>
        </div>
        <button
          @click="openNewFlowModal"
          class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-bold flex items-center shadow-lg shadow-blue-900/20 transition-all hover:-translate-y-0.5"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nuevo Flujo
        </button>
      </div>

      <!-- Grid de Flujos -->
      <div v-if="flows.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="flow in flows" 
          :key="flow.id" 
          class="bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-lg hover:shadow-2xl hover:bg-slate-800 transition-all p-6 border border-white/5 group relative overflow-hidden flex flex-col"
        >
          <!-- Background accent -->
          <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none group-hover:bg-blue-500/20 transition-all"></div>

          <div class="flex items-start justify-between mb-4 relative z-10">
            <h3 class="text-xl font-bold text-white group-hover:text-blue-400 transition-colors line-clamp-1 pr-4">
              {{ flow.name }}
            </h3>
            <span :class="getStatusClass(flow.status)" class="px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full whitespace-nowrap border border-current/20">
              {{ getStatusText(flow.status) }}
            </span>
          </div>
          
          <p class="text-slate-400 text-sm mb-6 line-clamp-2 h-10 flex-grow">
            {{ flow.description || 'Sin descripción' }}
          </p>
          
          <!-- Estadísticas -->
          <div class="flex items-center justify-between mb-3 text-sm mt-auto">
            <div class="flex items-center text-slate-500 font-medium font-mono text-xs uppercase tracking-wide">
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              <span>{{ flow.tasks?.length || 0 }} tareas</span>
            </div>
            <div class="text-slate-300 font-bold text-xs uppercase tracking-wide">
              <span class="text-blue-400 text-sm mr-1">{{ flow.progress || 0 }}%</span> completado
            </div>
          </div>

          <!-- Barra de progreso -->
          <div class="w-full bg-slate-700/50 rounded-full h-1.5 mb-6 overflow-hidden">
            <div 
              class="bg-blue-500 h-1.5 rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(59,130,246,0.3)]"
              :style="`width: ${flow.progress || 0}%`"
              :class="{ 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]': (flow.progress || 0) === 100 }"
            ></div>
          </div>

          <!-- Botones -->
          <div class="flex space-x-3 pt-5 border-t border-white/5 relative z-10">
            <router-link 
              :to="`/flows/${flow.id}`"
              class="flex-1 text-center bg-blue-600/10 text-blue-400 py-2.5 rounded-xl hover:bg-blue-600 hover:text-white transition-all font-bold text-sm border border-blue-600/20 uppercase tracking-wide"
            >
              Ver Detalles
            </router-link>
            <button
              @click.prevent="openEditFlowModal(flow)"
              class="px-3 py-2.5 bg-slate-700/30 text-slate-400 rounded-xl hover:bg-slate-700 hover:text-white transition-colors border border-white/5"
              title="Editar flujo"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </button>
            <button
              @click.prevent="deleteFlow(flow)"
              class="px-3 py-2.5 bg-rose-500/10 text-rose-400 rounded-xl hover:bg-rose-500 hover:text-white transition-colors border border-rose-500/20"
              title="Eliminar flujo"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Card vacía si no hay flujos -->
      <div v-else class="col-span-full flex flex-col items-center justify-center py-20 bg-slate-800/30 rounded-3xl border-2 border-dashed border-slate-700/50 backdrop-blur-sm">
        <div class="bg-slate-800 p-6 rounded-full mb-6 border border-white/5 shadow-xl">
            <svg class="w-16 h-16 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
        </div>
        <p class="text-white text-2xl font-bold mb-3">No hay flujos creados</p>
        <p class="text-slate-400 max-w-md text-center">Crea tu primer flujo de trabajo para comenzar a organizar y monitorear tus tareas de manera eficiente.</p>
        <button
          @click="openNewFlowModal"
          class="mt-8 px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold transition-all shadow-lg hover:shadow-blue-600/20"
        >
          Crear Primer Flujo
        </button>
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

const deleteFlow = async (flow) => {
  if (!confirm(`¿Estás seguro de eliminar el flujo "${flow.name}"? Esto eliminará todas las tareas asociadas.`)) {
    return
  }

  try {
    const token = localStorage.getItem('token')
    await fetch(`http://localhost:8000/api/v1/flows/${flow.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    await loadData()
  } catch (error) {
    console.error('Error eliminando flujo:', error)
    alert('Error al eliminar el flujo')
  }
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    paused: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    completed: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    cancelled: 'bg-rose-500/10 text-rose-400 border-rose-500/20'
  }
  return classes[status] || 'bg-slate-700/50 text-slate-400 border-slate-600/30'
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