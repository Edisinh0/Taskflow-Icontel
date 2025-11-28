<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-blue-600">TaskFlow</h1>
          </div>
          <div class="flex items-center space-x-4">
            <router-link to="/dashboard" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600">Dashboard</router-link>
            <router-link to="/flows" class="px-3 py-2 rounded-md text-sm font-medium text-blue-600">Flujos</router-link>
            <button @click="handleLogout" class="ml-4 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Salir</button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="text-xl text-gray-600">Cargando flujo...</div>
    </div>

    <!-- Contenido -->
    <main v-else-if="flow" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header del Flujo -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h2 class="text-3xl font-bold text-gray-800">{{ flow.name }}</h2>
            <p class="text-gray-600 mt-2">{{ flow.description }}</p>
          </div>
          <span :class="getStatusClass(flow.status)" class="px-4 py-2 text-sm font-semibold rounded-full">
            {{ getStatusText(flow.status) }}
          </span>
        </div>

        <!-- Info adicional -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Plantilla</p>
            <p class="text-lg font-semibold text-gray-800">{{ flow.template?.name || 'Sin plantilla' }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Creado por</p>
            <p class="text-lg font-semibold text-gray-800">{{ flow.creator?.name }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Total Tareas</p>
            <p class="text-lg font-semibold text-gray-800">{{ flow.tasks?.length || 0 }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Progreso General</p>
            <div class="flex items-center mt-1">
              <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${calculateOverallProgress()}%`"></div>
              </div>
              <span class="text-sm font-semibold">{{ calculateOverallProgress() }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Milestones -->
      <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">🎯 Milestones (Hitos)</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div 
            v-for="milestone in milestones" 
            :key="milestone.id"
            class="bg-white rounded-lg shadow p-6 border-l-4"
            :class="getMilestoneClass(milestone)"
          >
            <div class="flex items-start justify-between mb-3">
              <h4 class="text-lg font-semibold text-gray-800">{{ milestone.title }}</h4>
              <span class="text-2xl">{{ getMilestoneIcon(milestone.status) }}</span>
            </div>
            <p class="text-sm text-gray-600 mb-3">{{ milestone.description }}</p>
            
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Responsable:</span>
                <span class="font-medium">{{ milestone.assignee?.name || 'Sin asignar' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Progreso:</span>
                <span class="font-medium">{{ milestone.progress }}%</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Subtareas:</span>
                <span class="font-medium">{{ milestone.subtasks?.length || 0 }}</span>
              </div>
            </div>

            <!-- Subtareas del milestone -->
            <div v-if="milestone.subtasks && milestone.subtasks.length > 0" class="mt-4 pt-4 border-t">
              <p class="text-xs font-semibold text-gray-500 mb-2">TAREAS:</p>
              <div class="space-y-2">
                <div 
                  v-for="subtask in milestone.subtasks" 
                  :key="subtask.id"
                  class="flex items-center text-sm"
                >
                  <span :class="getTaskIcon(subtask.status)" class="mr-2">
                    {{ getTaskIconSymbol(subtask.status) }}
                  </span>
                  <span class="flex-1" :class="{'line-through text-gray-400': subtask.status === 'completed'}">
                    {{ subtask.title }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Todas las Tareas (Vista de Árbol) -->
      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-2xl font-bold text-gray-800">📋 Todas las Tareas</h3>
          <button
            @click="openNewTaskModal"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium flex items-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Tarea
          </button>
        </div>
        
        <div class="space-y-3">
          <TaskTreeItem 
            v-for="task in rootTasks" 
            :key="task.id"
            :task="task"
            :level="0"
            @edit="openEditTaskModal"
          />
        </div>
      </div>
    </main>

    <!-- Modal de Tarea -->
    <TaskModal
      :is-open="showTaskModal"
      :task="selectedTask"
      :flow-id="flow?.id"
      :users="users"
      @close="closeTaskModal"
      @saved="handleTaskSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { flowsAPI } from '@/services/api'
import TaskTreeItem from '@/components/TaskTreeItem.vue'
import TaskModal from '@/components/TaskModal.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const flow = ref(null)
const loading = ref(true)
const showTaskModal = ref(false)
const selectedTask = ref(null)

// Lista de usuarios para el modal (simulado, deberías cargarlo de la API)
const users = ref([
  { id: 1, name: 'Admin TaskFlow' },
  { id: 2, name: 'Juan Pérez' },
  { id: 3, name: 'María González' },
  { id: 4, name: 'Carlos Rodríguez' }
])

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const openNewTaskModal = () => {
  selectedTask.value = null
  showTaskModal.value = true
}

const openEditTaskModal = (task) => {
  selectedTask.value = task
  showTaskModal.value = true
}

const closeTaskModal = () => {
  showTaskModal.value = false
  selectedTask.value = null
}

const handleTaskSaved = async () => {
  // Recargar el flujo después de guardar
  await loadFlow()
}

// Computed
const milestones = computed(() => {
  if (!flow.value?.tasks) return []
  return flow.value.tasks.filter(task => task.is_milestone)
})

const rootTasks = computed(() => {
  if (!flow.value?.tasks) return []
  return flow.value.tasks.filter(task => !task.parent_task_id)
})

// Métodos
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

const getMilestoneClass = (milestone) => {
  if (milestone.status === 'completed') return 'border-green-500 bg-green-50'
  if (milestone.status === 'in_progress') return 'border-blue-500'
  if (milestone.status === 'blocked') return 'border-red-500 bg-red-50'
  return 'border-gray-300'
}

const getMilestoneIcon = (status) => {
  if (status === 'completed') return '✅'
  if (status === 'in_progress') return '🔄'
  if (status === 'blocked') return '🔒'
  return '⏳'
}

const getTaskIcon = (status) => {
  const classes = {
    completed: 'text-green-600',
    in_progress: 'text-blue-600',
    blocked: 'text-red-600',
    pending: 'text-gray-400'
  }
  return classes[status] || 'text-gray-400'
}

const getTaskIconSymbol = (status) => {
  if (status === 'completed') return '✓'
  if (status === 'in_progress') return '→'
  if (status === 'blocked') return '🔒'
  return '○'
}

const calculateOverallProgress = () => {
  if (!flow.value?.tasks || flow.value.tasks.length === 0) return 0
  const totalProgress = flow.value.tasks.reduce((sum, task) => sum + task.progress, 0)
  return Math.round(totalProgress / flow.value.tasks.length)
}

// Cargar datos
const loadFlow = async () => {
  try {
    loading.value = true
    const response = await flowsAPI.getOne(route.params.id)
    flow.value = response.data.data
  } catch (error) {
    console.error('Error cargando flujo:', error)
    alert('Error al cargar el flujo')
    router.push('/flows')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadFlow()
})
</script>