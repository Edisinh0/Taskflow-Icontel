<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors font-sans selection:bg-blue-500/30 selection:text-blue-200">
    <Navbar />

    <div v-if="loading" class="flex justify-center items-center h-screen">
       <div class="flex flex-col items-center">
          <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mb-4"></div>
          <p class="text-slate-400 text-lg animate-pulse">Cargando flujo...</p>
       </div>
    </div>

    <main v-else-if="flow" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header del Flujo -->
      <div class="rounded-3xl p-8 mb-8 border border-slate-200 dark:border-white/5 bg-white/80 dark:bg-slate-800/40 backdrop-blur-xl shadow-lg dark:shadow-2xl relative overflow-hidden">
        <!-- Background Glow -->
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row justify-between items-start mb-6 relative z-10">
          <div class="flex-1">
            <div class="flex items-center gap-4 mb-2">
              <h2 class="text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">{{ flow.name }}</h2>
              <span :class="getStatusClass(flow.status)" class="px-3 py-1 text-xs font-bold rounded-full tracking-wider uppercase border border-slate-200 dark:border-white/10 shadow-sm">
                {{ getStatusText(flow.status) }}
              </span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-lg max-w-2xl leading-relaxed">{{ flow.description }}</p>
          </div>
          <button
            @click="deleteFlow"
            class="mt-4 md:mt-0 p-2 text-slate-500 hover:text-rose-500 hover:bg-rose-500/10 rounded-xl transition-all duration-300 group"
            title="Eliminar flujo"
          >
            <svg class="w-6 h-6 transform group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 relative z-10">
          <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-white/5 hover:border-blue-500/20 transition-colors group">
            <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-500 font-bold mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Plantilla</p>
            <p class="text-base font-semibold text-slate-800 dark:text-slate-200 truncate">{{ flow.template?.name || 'Personalizado' }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-white/5 hover:border-blue-500/20 transition-colors group">
            <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-500 font-bold mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Creado por</p>
            <p class="text-base font-semibold text-slate-800 dark:text-slate-200 truncate">{{ flow.creator?.name }}</p>
          </div>
          <!-- FIX: Only count Tasks (non-milestones) -->
          <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-white/5 hover:border-blue-500/20 transition-colors group">
            <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-500 font-bold mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Tareas Activas</p>
             <div class="flex items-baseline gap-1">
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ flow.tasks?.filter(t => !t.is_milestone).length || 0 }}</p>
                <span class="text-sm text-slate-500 font-medium">tareas</span>
             </div>
          </div>
          <!-- FIX: Use flow.progress directly from backend -->
          <div class="bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-200 dark:border-white/5 hover:border-blue-500/20 transition-colors group">
            <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-500 font-bold mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Progreso</p>
            <div class="flex flex-col gap-2">
               <div class="flex justify-between items-end">
                  <span class="text-2xl font-bold text-slate-800 dark:text-white">{{ flow.progress || 0 }}%</span>
               </div>
               <div class="w-full bg-slate-200 dark:bg-slate-700/50 rounded-full h-2 overflow-hidden">
                <div 
                    class="h-full rounded-full shadow-[0_0_10px_rgba(59,130,246,0.6)] transition-all duration-1000 ease-out"
                    :class="flow.progress === 100 ? 'bg-emerald-500 shadow-emerald-500/50' : 'bg-blue-500'"
                    :style="`width: ${flow.progress || 0}%`"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Milestones Section -->
      <div class="mb-12">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center">
            <span class="bg-gradient-to-br from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20 text-blue-600 dark:text-blue-400 p-2.5 rounded-xl mr-3 border border-slate-200 dark:border-white/5">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </span>
            Hitos del Proyecto
          </h3>
          <button
            @click="openNewMilestoneModal"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold flex items-center shadow-lg shadow-blue-500/20 dark:shadow-blue-900/40 transition-all hover:scale-105 active:scale-95 border border-blue-500/20"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Nuevo Hito
          </button>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div 
            v-for="milestone in milestones" 
            :key="milestone.id"
            class="bg-white dark:bg-slate-800/80 rounded-3xl p-6 border border-slate-200 dark:border-white/5 shadow-sm dark:shadow-xl hover:shadow-md dark:hover:shadow-2xl hover:border-slate-300 dark:hover:border-slate-600/50 hover:bg-white dark:hover:bg-slate-800 transition-all duration-300 group flex flex-col"
          >
            <!-- Card Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <span :class="getMilestoneIconBg(milestone.status)" class="p-2.5 rounded-xl inline-flex items-center justify-center border border-current/10 shadow-inner">
                    <svg v-if="milestone.status === 'completed'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <svg v-else-if="milestone.status === 'in_progress'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                   <h4 class="text-lg font-bold text-slate-800 dark:text-white leading-tight">{{ milestone.title }}</h4>
                   <span class="text-xs font-semibold text-slate-500 uppercase tracking-widest mt-1 block">
                    {{ getStatusText(milestone.status) }}
                   </span>
                </div>
              </div>
            </div>
            
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed line-clamp-2 min-h-[40px]">
                {{ milestone.description || 'Sin descripción' }}
            </p>
            
            <!-- Metadata Box -->
            <div class="space-y-3 mb-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-200 dark:border-white/5">
              <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500 font-medium">Responsable</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold flex items-center gap-2">
                   <div class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs text-slate-600 dark:text-slate-300">
                     {{ (milestone.assignee?.name || 'U').charAt(0) }}
                   </div>
                   {{ milestone.assignee?.name || 'Sin asignar' }}
                </span>
              </div>
              <div class="flex justify-between items-center text-sm">
                 <span class="text-slate-500 font-medium">Progreso</span>
                 <div class="flex items-center gap-3 flex-1 justify-end">
                    <div class="w-20 bg-slate-200 dark:bg-slate-700/50 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full transition-all duration-500" 
                             :class="milestone.progress === 100 ? 'bg-emerald-500' : 'bg-blue-500'"
                             :style="`width: ${milestone.progress}%`">
                        </div>
                    </div>
                    <span class="text-slate-700 dark:text-slate-200 font-bold text-xs w-8 text-right">{{ milestone.progress }}%</span>
                </div>
              </div>
            </div>

            <!-- Boton Agregar -->
            <button
              @click="openNewTaskForMilestone(milestone)"
              class="w-full py-3.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-700/30 dark:hover:bg-slate-700/60 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white rounded-xl text-sm font-bold transition-all border border-dashed border-slate-300 dark:border-slate-600/50 hover:border-slate-400 dark:hover:border-slate-500 flex items-center justify-center mb-6"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Agregar Tarea Secuencial
            </button>

            <!-- Lista Tareas (Scrollable list if many tasks) -->
            <div class="flex-1 overflow-visible">
                <div v-if="milestone.subtasks && milestone.subtasks.length > 0">
                <div class="flex items-center justify-between mb-3 px-1">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tareas Secuenciales</p>
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80 px-2 py-0.5 rounded-md border border-slate-200 dark:border-white/5">
                        {{ milestone.subtasks.length }}
                    </span>
                </div>
                <div class="space-y-1">
                    <div 
                    v-for="subtask in milestone.subtasks" 
                    :key="subtask.id"
                    class="flex items-center p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-all border border-transparent hover:border-slate-200 dark:hover:border-white/5 group/task cursor-pointer"
                    @click="openEditTaskModal(subtask)"
                    >
                    <!-- Status Icon -->
                    <div class="mr-3 flex-shrink-0">
                        <div v-if="subtask.status === 'completed'" class="w-6 h-6 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 flex items-center justify-center">
                             <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div v-else-if="subtask.status === 'in_progress'" class="w-6 h-6 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/20 flex items-center justify-center animate-pulse-slow">
                             <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div v-else-if="subtask.status === 'blocked'" class="w-6 h-6 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div v-else class="w-6 h-6 rounded-full border-2 border-slate-300 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800/50"></div>
                    </div>
                    
                    <span class="flex-1 text-sm font-medium transition-colors line-clamp-1" :class="subtask.status === 'completed' ? 'text-slate-400 dark:text-slate-500 line-through decoration-slate-400 dark:decoration-slate-600' : 'text-slate-600 dark:text-slate-300 group-hover/task:text-slate-800 dark:group-hover/task:text-white'">
                        {{ subtask.title }}
                    </span>
                    
                     <!-- Edit Icon on Hover -->
                     <div class="opacity-0 group-hover/task:opacity-100 transition-opacity text-slate-400 dark:text-slate-500 hover:text-blue-500 dark:hover:text-blue-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                     </div>
                    </div>
                </div>
                </div>
                <div v-else class="text-center py-6 text-slate-600 text-sm italic">
                    No hay tareas secuenciales aún
                </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer / Additional drag drop zone (Collapsed by default or simplified) -->
      <div class="border-t border-slate-200 dark:border-slate-800 pt-8 mt-12 pb-12">
        <div class="bg-white/50 dark:bg-slate-800/30 rounded-3xl p-6 border border-slate-100 dark:border-white/5 opacity-80 hover:opacity-100 transition-opacity">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                Vista Estructural
            </h3>
             <div ref="taskListRef" class="space-y-4">
                <div v-for="group in taskGroups" :key="group.id" class="task-group">
                    <div v-if="group.isMilestone" class="flex items-center mb-3 px-2 mt-6 first:mt-0">
                        <span class="text-xs font-bold text-blue-400 uppercase tracking-wider mr-3 bg-blue-500/10 px-2 py-1 rounded">
                            Milestone: {{ group.title }}
                        </span>
                        <div class="h-px flex-1 bg-slate-700/50"></div>
                    </div>
                    <div class="pl-2" :class="{'border-l-2 border-slate-700/30 pl-4': group.isMilestone}">
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
  
  const allTasks = flow.value.tasks
  
  return allTasks.filter(task => task.is_milestone).map(milestone => {
    // Manually find tasks that belong to this milestone to ensure 'subtasks' property exists
    // Considers both parent_task_id (nested) and depends_on_milestone_id (legacy/blocking)
    const subtasks = allTasks.filter(t => 
      !t.is_milestone && 
      (t.parent_task_id == milestone.id || t.depends_on_milestone_id == milestone.id)
    )
    
    // Return a new object with the subtasks attached
    return {
      ...milestone,
      subtasks: subtasks
    }
  })
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
  
  console.log('🔍 Creando tarea secuencial para Milestone:', milestone.title, 'ID:', milestone.id)
  
  // 1. Encontrar TODAS las tareas que son hijas de este milestone
  const childTasks = flow.value.tasks.filter(t => 
    t.parent_task_id == milestone.id && !t.is_milestone
  )
  
  console.log('📋 Subtareas encontradas:', childTasks.length, childTasks.map(t => ({ id: t.id, title: t.title })))

  // 2. Ordenar por ID descendente para encontrar la última creada
  childTasks.sort((a, b) => b.id - a.id)
  
  const lastTask = childTasks[0]
  
  if (lastTask) {
    console.log('🔗 Última tarea encontrada (para secuencia):', lastTask.title, 'ID:', lastTask.id)
  } else {
    console.log('ℹ️ No hay tareas previas, esta será la primera tarea del milestone')
  }
  
  // 3. Preparar datos iniciales
  initialTaskData.value = {
    // Usar parent_task_id para agrupar bajo el milestone
    parent_task_id: milestone.id,
    
    // NO establecer depends_on_milestone_id para evitar bloqueo circular
    depends_on_milestone_id: null,
    
    // Establecer dependencia de la última tarea (secuencial)
    // Si no hay tarea previa, depends_on_task_id será null (primera tarea)
    depends_on_task_id: lastTask ? lastTask.id : null,
    
    // Valores por defecto
    priority: 'medium',
    status: 'pending',
    is_milestone: false,
    title: `Tarea ${childTasks.length + 1} - ${milestone.title}`
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

const getMilestoneIconBg = (status) => {
  if (status === 'completed') return 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-500'
  if (status === 'in_progress') return 'bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-500'
  if (status === 'blocked') return 'bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-500'
  return 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'
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