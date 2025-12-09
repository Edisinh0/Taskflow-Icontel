<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <Navbar />

    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="text-xl text-gray-600 dark:text-gray-400">Cargando flujo...</div>
    </div>

    <main v-else-if="flow" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header del Flujo -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <div class="flex justify-between items-start mb-4">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <h2 class="text-3xl font-bold text-gray-900 dark:text-white">{{ flow.name }}</h2>
              <button
                @click="deleteFlow"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="Eliminar flujo"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mt-2">{{ flow.description }}</p>
          </div>
          <span :class="getStatusClass(flow.status)" class="px-4 py-2 text-sm font-semibold rounded-full whitespace-nowrap">
            {{ getStatusText(flow.status) }}
          </span>
        </div>

        <!-- Info adicional -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
          <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <p class="text-sm text-gray-500 dark:text-gray-400">Plantilla</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ flow.template?.name || 'Sin plantilla' }}</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <p class="text-sm text-gray-500 dark:text-gray-400">Creado por</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ flow.creator?.name }}</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Tareas</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ flow.tasks?.length || 0 }}</p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <p class="text-sm text-gray-500 dark:text-gray-400">Progreso General</p>
            <div class="flex items-center mt-1">
              <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mr-2">
                <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${calculateOverallProgress()}%`"></div>
              </div>
              <span class="text-sm font-semibold dark:text-white">{{ calculateOverallProgress() }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Milestones -->
      <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-2xl font-bold text-gray-800 dark:text-white">🎯 Milestones (Hitos)</h3>
          <button
            @click="openNewMilestoneModal"
            class="px-4 py-2 border border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 font-medium flex items-center transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
            Nuevo Milestone
          </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div 
            v-for="milestone in milestones" 
            :key="milestone.id"
            class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 relative group"
            :class="getMilestoneClass(milestone)"
          >
            <div class="flex items-start justify-between mb-3">
              <h4 class="text-lg font-semibold text-gray-800 dark:text-white">{{ milestone.title }}</h4>
              <span class="text-2xl">{{ getMilestoneIcon(milestone.status) }}</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ milestone.description }}</p>
            
            <div class="space-y-2 mb-4">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Responsable:</span>
                <span class="font-medium dark:text-white">{{ milestone.assignee?.name || 'Sin asignar' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Progreso:</span>
                <span class="font-medium dark:text-white">{{ milestone.progress }}%</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Subtareas:</span>
                <span class="font-medium dark:text-white">{{ milestone.subtasks?.length || 0 }}</span>
              </div>
            </div>

            <!-- Botón Agregar Tarea en Milestone -->
            <button
              @click="openNewTaskForMilestone(milestone)"
              class="w-full py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition-colors flex items-center justify-center"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Agregar Tarea Secuencial
            </button>

            <div v-if="milestone.subtasks && milestone.subtasks.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">TAREAS:</p>
              <div class="space-y-2">
                <div 
                  v-for="subtask in milestone.subtasks" 
                  :key="subtask.id"
                  class="flex items-center text-sm"
                >
                  <span :class="getTaskIcon(subtask.status)" class="mr-2">
                    {{ getTaskIconSymbol(subtask.status) }}
                  </span>
                  <span class="flex-1 dark:text-gray-300" :class="{'line-through text-gray-400': subtask.status === 'completed'}">
                    {{ subtask.title }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Todas las Tareas con Drag & Drop -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex justify-between items-center mb-4">
          <div>
            <h3 class="text-2xl font-bold text-gray-800 dark:text-white">📋 Todas las Tareas</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              💡 Arrastra las tareas por el icono ≡ para reordenarlas
            </p>
          </div>
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
        
        <!-- Contenedor con Drag & Drop -->
        <div ref="taskListRef" class="space-y-4">
          <!-- Agrupación visual por bloques (Milestone o Tareas Libres) -->
          <div v-for="group in taskGroups" :key="group.id" class="task-group">
             <!-- Encabezado de Grupo si es Milestone -->
            <div v-if="group.isMilestone" class="flex items-center mb-2 px-2">
                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mr-2">
                    Milestone: {{ group.title }}
                </span>
                <div class="h-px flex-1 bg-blue-100 dark:bg-blue-900/40"></div>
            </div>

            <div class="pl-2" :class="{'border-l-2 border-blue-100 dark:border-blue-900/50 pl-4': group.isMilestone}">
                <TaskTreeItem 
                    v-for="task in group.tasks" 
                    :key="task.id"
                    :task="task"
                    :level="0"
                    @edit="openEditTaskModal"
                    @delete="deleteTask"
                    @dependencies="openDependencyModal"
                />
            </div>
          </div>
        </div>

        <div v-if="rootTasks.length === 0" class="text-center py-12">
          <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400">No hay tareas en este flujo</p>
        </div>
      </div>
    </main>

    <!-- Modal de Tarea -->
    <TaskModal
      :is-open="showTaskModal"
      :task="selectedTask"
      :flow-id="flow?.id"
      :users="users"
      :available-tasks="flow?.tasks || []"
      :initial-data="initialTaskData"
      @close="closeTaskModal"
      @saved="handleTaskSaved"
    />

    <!-- Modal de Dependencias -->
    <DependencyManager
      :is-open="showDependencyModal"
      :task="selectedTask"
      :available-tasks="flow?.tasks || []"
      @close="closeDependencyModal"
      @updated="handleDependenciesUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { flowsAPI, tasksAPI } from '@/services/api'
import { useDragAndDrop } from '@/composables/useDragAndDrop'
import TaskTreeItem from '@/components/TaskTreeItem.vue'
import TaskModal from '@/components/TaskModal.vue'
import DependencyManager from '@/components/DependencyManager.vue'
import Navbar from '@/components/Navbar.vue'

const route = useRoute()
const router = useRouter()

const flow = ref(null)
const loading = ref(true)
const showTaskModal = ref(false)
const showDependencyModal = ref(false)
const selectedTask = ref(null)
const initialTaskData = ref(null) // Para pasar datos pre-definidos al crear nueva tarea
const taskListRef = ref(null)

const users = ref([
  { id: 1, name: 'Admin TaskFlow' },
  { id: 2, name: 'Juan Pérez' },
  { id: 3, name: 'María González' },
  { id: 4, name: 'Carlos Rodríguez' }
])

// Computed
const milestones = computed(() => {
  if (!flow.value?.tasks) return []
  return flow.value.tasks.filter(task => task.is_milestone)
})

const rootTasks = computed(() => {
  if (!flow.value?.tasks) return []
  return flow.value.tasks.filter(task => !task.parent_task_id)
})

// Agrupar tareas por Milestone para visualización
const taskGroups = computed(() => {
    if (!rootTasks.value) return []

    const groups = []
    const processedTaskIds = new Set()

    // 1. Grupos de Milestones
    milestones.value.forEach(milestone => {
        // Encontrar tareas que pertenecen a este milestone:
        // 1. Por dependencia directa (legacy/bloqueo estricto)
        // 2. Por ser hijo directo (parent_task_id) - Nueva Estrategia
        
        const milestoneTasks = rootTasks.value.filter(t => 
            (t.depends_on_milestone_id == milestone.id || t.parent_task_id == milestone.id) && 
            !t.is_milestone // Evitar el mismo milestone
        )

        if (milestoneTasks.length > 0) {
            groups.push({
                id: `milestone-${milestone.id}`,
                title: milestone.title,
                isMilestone: true,
                tasks: milestoneTasks
            })
            milestoneTasks.forEach(t => processedTaskIds.add(t.id))
        }
    })

    // 2. Tareas "Sueltas" (incluyendo los propios milestones si son root tasks)
    // Filtramos las que ya están en algún grupo
    const looseTasks = rootTasks.value.filter(t => !processedTaskIds.has(t.id))
    
    if (looseTasks.length > 0) {
        groups.push({
            id: 'general',
            title: 'General',
            isMilestone: false,
            tasks: looseTasks
        })
    }

    // Ordenar grupos: Primero los que tienen milestones, luego general
    return groups.sort((a, b) => {
        if (a.isMilestone && !b.isMilestone) return -1
        if (!a.isMilestone && b.isMilestone) return 1
        return 0
    })
})

// Drag & Drop Setup
useDragAndDrop(taskListRef, {
  onEnd: async (evt) => {
    const movedTaskId = evt.item.dataset.taskId
    const newIndex = evt.newIndex
    
    console.log(`🎯 Tarea ${movedTaskId} movida a posición ${newIndex}`)
    
    try {
      // Actualizar orden en el backend
      const token = localStorage.getItem('token')
      await fetch(`http://localhost:8000/api/v1/tasks/${movedTaskId}/reorder`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ new_order: newIndex })
      })
      
      // Recargar flujo
      await loadFlow()
    } catch (error) {
      console.error('Error reordenando tarea:', error)
      alert('Error al reordenar la tarea')
      await loadFlow() // Revertir cambios
    }
  }
})

// Modals
const openNewTaskModal = () => {
  selectedTask.value = null
  initialTaskData.value = null
  showTaskModal.value = true
}

const openNewMilestoneModal = () => {
  selectedTask.value = null
  initialTaskData.value = {
    is_milestone: true,
    title: 'Nuevo Milestone'
  }
  showTaskModal.value = true
}

// 🚀 Lógica clave: Agregar tarea secuencial a un milestone
const openNewTaskForMilestone = (milestone) => {
  selectedTask.value = null
  initialTaskData.value = null
  
  console.log('🔍 Buscando última subtarea para Milestone:', milestone.title, 'ID:', milestone.id)
  
  // 1. Encontrar tareas que son HIJAS de este milestone (parent_task_id)
  // en lugar de depender de él.
  const childTasks = flow.value.tasks.filter(t => 
    t.parent_task_id == milestone.id
  )
  
  console.log('📋 Subtareas encontradas:', childTasks.length)

  // Ordenar por ID descendente para encontrar la última creada
  childTasks.sort((a, b) => b.id - a.id)
  
  const lastTask = childTasks[0]
  
  if (lastTask) {
      console.log('🔗 Última tarea encontrada (para secuencia):', lastTask.title, 'ID:', lastTask.id)
  }
  
  initialTaskData.value = {
    // CAMBIO CLAVE: Usar parent_task_id para que sea una "subtarea" del milestone
    // Esto agrupa visual y lógicamente sin crear bloqueo "Task -> Milestone"
    parent_task_id: milestone.id,
    
    // NO establecer dependency al milestone para evitar bloqueo circular lógico 
    // (el milestone espera a sus tareas, las tareas no pueden esperar al milestone)
    depends_on_milestone_id: null,
    
    // Mantener la secuencia entre hermanas (Task B espera a Task A)
    depends_on_task_id: lastTask ? lastTask.id : null,
    
    priority: 'medium',
    title: `Tarea para ${milestone.title}`
  }
  
  console.log('📦 Initial Data preparado:', initialTaskData.value)

  showTaskModal.value = true
}

const openEditTaskModal = (task) => {
  selectedTask.value = task
  initialTaskData.value = null
  showTaskModal.value = true
}

const closeTaskModal = () => {
  showTaskModal.value = false
  selectedTask.value = null
  initialTaskData.value = null
}

const openDependencyModal = (task) => {
  selectedTask.value = task
  showDependencyModal.value = true
}

const closeDependencyModal = () => {
  showDependencyModal.value = false
  selectedTask.value = null
}

const handleTaskSaved = async () => {
  await loadFlow()
}

const handleDependenciesUpdated = async () => {
  await loadFlow()
}

// Delete operations
const deleteTask = async (task) => {
  if (!confirm(`¿Estás seguro de eliminar la tarea "${task.title}"?`)) return

  try {
    await tasksAPI.delete(task.id)
    await loadFlow()
  } catch (error) {
    console.error('Error eliminando tarea:', error)
    alert('Error al eliminar la tarea')
  }
}

const deleteFlow = async () => {
  if (!confirm(`¿Estás seguro de eliminar el flujo "${flow.value.name}"? Esto eliminará todas las tareas asociadas.`)) return

  try {
    await flowsAPI.delete(flow.value.id)
    router.push('/flows')
  } catch (error) {
    console.error('Error eliminando flujo:', error)
    alert('Error al eliminar el flujo')
  }
}

// Utility functions
const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
    paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
    completed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
    cancelled: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400'
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
  if (milestone.status === 'completed') return 'border-green-500 bg-green-50 dark:bg-green-900/20'
  if (milestone.status === 'in_progress') return 'border-blue-500 dark:border-blue-600'
  if (milestone.status === 'blocked') return 'border-red-500 bg-red-50 dark:bg-red-900/20'
  return 'border-gray-300 dark:border-gray-600'
}

const getMilestoneIcon = (status) => {
  if (status === 'completed') return '✅'
  if (status === 'in_progress') return '🔄'
  if (status === 'blocked') return '🔒'
  return '⏳'
}

const getTaskIcon = (status) => {
  const classes = {
    completed: 'text-green-600 dark:text-green-400',
    in_progress: 'text-blue-600 dark:text-blue-400',
    blocked: 'text-red-600 dark:text-red-400',
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

// Load data
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