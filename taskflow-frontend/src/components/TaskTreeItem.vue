<template>
  <div class="task-item" :style="{ marginLeft: `${level * 24}px` }">
    <!-- Tarea Principal -->
    <div 
      class="flex items-start p-4 rounded-xl border-l-4 mb-3 transition-all group relative overflow-hidden"
      :class="getTaskClass(task)"
      :data-task-id="task.id"
    >
      <!-- Drag Handle -->
      <div class="drag-handle flex-shrink-0 mr-2 mt-1 cursor-move opacity-0 group-hover:opacity-100 transition-opacity">
        <GripVertical class="w-5 h-5 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-white" />
      </div>

      <!-- Icono de estado -->
      <div class="flex-shrink-0 mt-0.5 mr-3">
        <component :is="taskIcon" class="w-5 h-5" :class="getIconColor(task)" />
      </div>

      <!-- Contenido de la tarea -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div class="flex-1 pr-4">
            <h4 
              class="text-base font-bold transition-colors"
              :class="{
                'text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-200': task.status !== 'completed',
                'text-slate-500 dark:text-slate-500 line-through decoration-slate-400 dark:decoration-slate-600': task.status === 'completed'
              }"
            >
              <Target v-if="task.is_milestone" class="inline w-4 h-4 mr-1 text-yellow-500 dark:text-yellow-400" />
              {{ task.title }}
            </h4>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 line-clamp-2 h-10">{{ task.description }}</p>
          </div>

          <!-- Badges de estado -->
          <div class="flex flex-col items-end space-y-2 flex-shrink-0">
            <div class="flex flex-col space-y-1.5 items-end">
              <!-- Badge de bloqueada -->
              <span v-if="task.is_blocked" class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-rose-100 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20 shadow-sm">
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
        <div class="flex items-center space-x-6 mt-4 pt-3 border-t border-slate-200 dark:border-white/5">
          <!-- Responsable -->
          <div class="flex items-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-500">
            <User class="w-3.5 h-3.5 mr-1.5" />
            {{ task.assignee?.name || 'Sin asignar' }}
          </div>

          <!-- Progreso -->
          <div class="flex items-center flex-1 max-w-xs">
            <div class="flex-1 bg-slate-200 dark:bg-slate-900 rounded-full h-1.5 mr-3 overflow-hidden">
              <div 
                class="h-1.5 rounded-full transition-all duration-500"
                :class="task.progress === 100 ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'bg-blue-600 dark:bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.3)]'"
                :style="`width: ${task.progress}%`"
              ></div>
            </div>
            <span class="text-slate-600 dark:text-slate-400 text-xs font-bold">{{ task.progress }}%</span>
          </div>

          <!-- Subtareas -->
          <div v-if="task.subtasks && task.subtasks.length > 0" class="flex items-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-500">
            <ListTodo class="w-3.5 h-3.5 mr-1.5" />
            {{ task.subtasks.length }} subtareas
          </div>
          
           <!-- Botones de acción (movidos abajo para mejor acceso) -->
           <div class="flex items-center space-x-1 ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click.stop.prevent="handleEdit"
                class="p-1.5 text-blue-400 hover:text-white hover:bg-blue-500/20 rounded-lg transition-colors"
                title="Editar"
              >
                <Pencil class="w-4 h-4" />
              </button>
              <button
                @click.stop.prevent="handleDependencies"
                class="p-1.5 text-purple-400 hover:text-white hover:bg-purple-500/20 rounded-lg transition-colors"
                title="Dependencias"
              >
                <Link class="w-4 h-4" />
              </button>
              <button
                @click.stop.prevent="handleDelete"
                class="p-1.5 text-rose-400 hover:text-white hover:bg-rose-500/20 rounded-lg transition-colors"
                title="Eliminar"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
        </div>

        <!-- Información de dependencias -->
        <div v-if="task.is_blocked && (task.depends_on_task_id || task.depends_on_milestone_id)" class="mt-3 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-500/20 rounded-lg text-sm">
          <div class="flex items-start">
            <Lock class="w-5 h-5 mr-2 text-rose-500" />
            <div class="flex-1">
              <p class="font-bold text-rose-600 dark:text-rose-400 mb-1">Tarea Bloqueada</p>
              <p class="text-rose-500 dark:text-rose-300/80 text-xs">
                Requiere completar:
              </p>
              <ul class="mt-1 space-y-1 text-xs text-rose-500 dark:text-rose-300">
                <li v-if="task.depends_on_task_id" class="flex items-center">
                  <ClipboardList class="w-3 h-3 mr-1 opacity-70" />
                  <span class="font-medium">{{ task.depends_on_task?.title || `Tarea #${task.depends_on_task_id}` }}</span>
                </li>
                <li v-if="task.depends_on_milestone_id" class="flex items-center">
                  <Target class="w-3 h-3 mr-1 opacity-70" />
                  <span class="font-medium">{{ task.depends_on_milestone?.title || `Milestone #${task.depends_on_milestone_id}` }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Razón de bloqueo (legacy) -->
        <div v-else-if="task.status === 'blocked' && task.blocked_reason" class="mt-3 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-500/20 rounded-lg text-sm text-rose-600 dark:text-rose-300 flex items-start">
          <Lock class="w-4 h-4 mr-2 mt-0.5" />
          <span><strong>Bloqueada:</strong> {{ task.blocked_reason }}</span>
        </div>
      </div>
    </div>

    <!-- Subtareas (recursivo) -->
    <div v-if="task.subtasks && task.subtasks.length > 0" class="ml-6 border-l border-slate-200 dark:border-white/5 pl-4 relative">
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
import { defineProps, defineEmits, computed } from 'vue'
import { 
  GripVertical, User, ListTodo, Pencil, Link, Trash2, 
  Lock, CheckCircle2, RotateCw, PauseCircle, ClipboardList, Target 
} from 'lucide-vue-next'

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

const taskIcon = computed(() => {
  if (props.task.is_blocked) return Lock
  if (props.task.is_milestone) return Target
  if (props.task.status === 'completed') return CheckCircle2
  if (props.task.status === 'in_progress') return RotateCw
  if (props.task.status === 'blocked') return Lock
  if (props.task.status === 'paused') return PauseCircle
  return ClipboardList
})

const getIconColor = (task) => {
   if (task.is_blocked) return 'text-rose-500'
   if (task.is_milestone) return 'text-yellow-500'
   if (task.status === 'completed') return 'text-emerald-500'
   if (task.status === 'in_progress') return 'text-blue-500 animate-spin-slow'
   if (task.status === 'blocked') return 'text-rose-500'
   if (task.status === 'paused') return 'text-yellow-500'
   return 'text-slate-400'
}

const getTaskClass = (task) => {
  // Base style
  const base = 'shadow-sm hover:shadow-md border border-slate-200 dark:border-white/5'
  
  // Prioridad: bloqueada > completada > en progreso > milestone > default
  // Update to use bg-white for light mode and darker backgrounds for heavy states
  if (task.is_blocked) return `${base} border-l-rose-500 bg-rose-50 dark:bg-rose-900/10`
  if (task.status === 'completed') return `${base} border-l-emerald-500 bg-emerald-50 dark:bg-emerald-900/5`
  if (task.status === 'in_progress') return `${base} border-l-blue-500 bg-blue-50 dark:bg-blue-900/10`
  if (task.status === 'blocked') return `${base} border-l-rose-500 bg-rose-50 dark:bg-rose-900/10`
  if (task.is_milestone) return `${base} border-l-yellow-500 bg-yellow-50 dark:bg-yellow-900/5`
  
  // Default Pending
  return `${base} border-l-slate-400 dark:border-l-slate-600 bg-white dark:bg-slate-800`
}



const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-600/30',
    blocked: 'bg-rose-100 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/20',
    in_progress: 'bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
    paused: 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-200 dark:border-yellow-500/20',
    completed: 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
    cancelled: 'bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/20'
  }
  return classes[status] || 'bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400'
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
    low: 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
    medium: 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-500 border-yellow-200 dark:border-yellow-500/20',
    high: 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-500/20',
    urgent: 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/20'
  }
  return classes[priority] || 'bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400'
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