<template>
  <div class="task-item" :style="{ marginLeft: `${level * 24}px` }">
    <!-- Tarea Principal -->
    <div 
      class="flex items-start p-4 rounded-xl border-l-4 mb-3 transition-all group relative overflow-hidden"
      :class="getTaskClass(task)"
      :data-task-id="task.id"
    >
      <!-- Drag Handle -->
      <div class="drag-handle flex-shrink-0 mr-3 mt-1 cursor-move opacity-0 group-hover:opacity-100 transition-opacity">
        <svg class="w-5 h-5 text-slate-500 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
        </svg>
      </div>

      <!-- Icono de estado -->
      <div class="flex-shrink-0 mt-0.5">
        <span class="text-xl mr-3 filter drop-shadow-sm">{{ getTaskIcon(task) }}</span>
      </div>

      <!-- Contenido de la tarea -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div class="flex-1 pr-4">
            <h4 
              class="text-base font-bold transition-colors"
              :class="{
                'text-white group-hover:text-blue-200': task.status !== 'completed',
                'text-slate-500 line-through decoration-slate-600': task.status === 'completed'
              }"
            >
              <span v-if="task.is_milestone" class="text-yellow-400 mr-1 filter drop-shadow-sm">⭐</span>
              {{ task.title }}
            </h4>
            <p class="text-sm text-slate-400 mt-1 line-clamp-2 h-10">{{ task.description }}</p>
          </div>

          <!-- Badges de estado -->
          <div class="flex flex-col items-end space-y-2 flex-shrink-0">
            <div class="flex flex-col space-y-1.5 items-end">
              <!-- Badge de bloqueada -->
              <span v-if="task.is_blocked" class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 shadow-sm">
                🔒 BLOQUEADA
              </span>
              <span :class="getStatusBadgeClass(task.status)" class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20 shadow-sm">
                {{ getStatusText(task.status) }}
              </span>
              <span v-if="task.priority" :class="getPriorityClass(task.priority)" class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20 shadow-sm">
                {{ getPriorityText(task.priority) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Información adicional -->
        <div class="flex items-center space-x-6 mt-4 pt-3 border-t border-black/10 dark:border-white/5">
          <!-- Responsable -->
          <div class="flex items-center text-xs font-semibold uppercase tracking-wider text-slate-500">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {{ task.assignee?.name || 'Sin asignar' }}
          </div>

          <!-- Progreso -->
          <div class="flex items-center flex-1 max-w-xs">
            <div class="flex-1 bg-slate-900 rounded-full h-1.5 mr-3 overflow-hidden">
              <div 
                class="h-1.5 rounded-full transition-all duration-500"
                :class="task.progress === 100 ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.3)]'"
                :style="`width: ${task.progress}%`"
              ></div>
            </div>
            <span class="text-slate-400 text-xs font-bold">{{ task.progress }}%</span>
          </div>

          <!-- Subtareas -->
          <div v-if="task.subtasks && task.subtasks.length > 0" class="flex items-center text-xs font-semibold uppercase tracking-wider text-slate-500">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            {{ task.subtasks.length }} subtareas
          </div>
          
           <!-- Botones de acción (movidos abajo para mejor acceso) -->
           <div class="flex items-center space-x-1 ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click.stop.prevent="handleEdit"
                class="p-1.5 text-blue-400 hover:text-white hover:bg-blue-500/20 rounded-lg transition-colors"
                title="Editar"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
              </button>
              <button
                @click.stop.prevent="handleDependencies"
                class="p-1.5 text-purple-400 hover:text-white hover:bg-purple-500/20 rounded-lg transition-colors"
                title="Dependencias"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
              </button>
              <button
                @click.stop.prevent="handleDelete"
                class="p-1.5 text-rose-400 hover:text-white hover:bg-rose-500/20 rounded-lg transition-colors"
                title="Eliminar"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
              </button>
            </div>
        </div>

        <!-- Información de dependencias -->
        <div v-if="task.is_blocked && (task.depends_on_task_id || task.depends_on_milestone_id)" class="mt-3 p-3 bg-rose-900/20 border border-rose-500/20 rounded-lg text-sm">
          <div class="flex items-start">
            <span class="text-lg mr-2">🔒</span>
            <div class="flex-1">
              <p class="font-bold text-rose-400 mb-1">Tarea Bloqueada</p>
              <p class="text-rose-300/80 text-xs">
                Requiere completar:
              </p>
              <ul class="mt-1 space-y-1 text-xs text-rose-300">
                <li v-if="task.depends_on_task_id" class="flex items-center">
                  <span class="mr-1 opacity-70">📋</span>
                  <span class="font-medium">{{ task.depends_on_task?.title || `Tarea #${task.depends_on_task_id}` }}</span>
                </li>
                <li v-if="task.depends_on_milestone_id" class="flex items-center">
                  <span class="mr-1 opacity-70">⭐</span>
                  <span class="font-medium">{{ task.depends_on_milestone?.title || `Milestone #${task.depends_on_milestone_id}` }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Razón de bloqueo (legacy) -->
        <div v-else-if="task.status === 'blocked' && task.blocked_reason" class="mt-3 p-3 bg-rose-900/20 border border-rose-500/20 rounded-lg text-sm text-rose-300">
          🔒 <strong>Bloqueada:</strong> {{ task.blocked_reason }}
        </div>
      </div>
    </div>

    <!-- Subtareas (recursivo) -->
    <div v-if="task.subtasks && task.subtasks.length > 0" class="ml-6 border-l border-white/5 pl-4 relative">
      <TaskTreeItem 
        v-for="subtask in task.subtasks" 
        :key="subtask.id"
        :task="subtask"
        :level="level + 1"
        @edit="(task) => emit('edit', task)"
        @delete="(task) => emit('delete', task)"
        @dependencies="(task) => emit('dependencies', task)"
      />
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  task: {
    type: Object,
    required: true
  },
  level: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['edit', 'delete', 'dependencies'])

// Funciones handler para evitar problemas de propagación
const handleEdit = () => {
  emit('edit', props.task)
}

const handleDelete = () => {
  emit('delete', props.task)
}

const handleDependencies = () => {
  emit('dependencies', props.task)
}

const getTaskClass = (task) => {
  // Base style
  const base = 'shadow-sm hover:shadow-md border-white/5'
  
  // Prioridad: bloqueada > completada > en progreso > milestone > default
  // Usamos fondos muy sutiles y bordes izquierdos de color
  if (task.is_blocked) return `${base} border-l-rose-500 bg-rose-900/10`
  if (task.status === 'completed') return `${base} border-l-emerald-500 bg-emerald-900/5`
  if (task.status === 'in_progress') return `${base} border-l-blue-500 bg-blue-900/10`
  if (task.status === 'blocked') return `${base} border-l-rose-500 bg-rose-900/10`
  if (task.is_milestone) return `${base} border-l-yellow-500 bg-yellow-900/5`
  return `${base} border-l-slate-600 bg-slate-800`
}

const getTaskIcon = (task) => {
  if (task.is_blocked) return '🔒'
  if (task.is_milestone) return '🎯'
  if (task.status === 'completed') return '✅'
  if (task.status === 'in_progress') return '🔄'
  if (task.status === 'blocked') return '🔒'
  if (task.status === 'paused') return '⏸️'
  return '📋'
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-slate-700/50 text-slate-400 border-slate-600/30',
    blocked: 'bg-rose-500/10 text-rose-400 border-rose-500/20',
    in_progress: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    paused: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    completed: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    cancelled: 'bg-red-500/10 text-red-400 border-red-500/20'
  }
  return classes[status] || 'bg-slate-700/50 text-slate-400'
}

const getStatusText = (status) => {
  const texts = {
    pending: 'Pendiente',
    blocked: 'Bloqueada',
    in_progress: 'En Progreso',
    paused: 'Pausada',
    completed: 'Completada',
    cancelled: 'Cancelada'
  }
  return texts[status] || status
}

const getPriorityClass = (priority) => {
  const classes = {
    low: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    medium: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
    high: 'bg-orange-500/10 text-orange-400 border-orange-500/20',
    urgent: 'bg-rose-500/10 text-rose-400 border-rose-500/20'
  }
  return classes[priority] || 'bg-slate-700/50 text-slate-400'
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
</script>

<style scoped>
.task-item {
  transition: all 0.2s ease;
}
</style>