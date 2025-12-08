<template>
  <div class="task-item" :style="{ marginLeft: `${level * 24}px` }">
    <!-- Tarea Principal -->
    <div 
      class="flex items-start p-4 rounded-lg border-l-4 mb-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      :class="getTaskClass(task)"
      :data-task-id="task.id"
    >
      <!-- Drag Handle -->
      <div class="drag-handle flex-shrink-0 mr-2 mt-1 cursor-move">
        <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
        </svg>
      </div>

      <!-- Icono de estado -->
      <div class="flex-shrink-0 mt-1">
        <span class="text-xl mr-3">{{ getTaskIcon(task) }}</span>
      </div>

      <!-- Contenido de la tarea -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h4 
              class="text-base font-semibold"
              :class="{
                'text-gray-800': task.status !== 'completed',
                'text-gray-400 line-through': task.status === 'completed'
              }"
            >
              <span v-if="task.is_milestone" class="text-yellow-500 mr-1">⭐</span>
              {{ task.title }}
            </h4>
            <p class="text-sm text-gray-600 mt-1">{{ task.description }}</p>
          </div>

          <!-- Badges de estado -->
          <div class="flex flex-col items-end space-y-2 ml-4">
            <div class="flex flex-col space-y-1">
              <!-- Badge de bloqueada -->
              <span v-if="task.is_blocked" class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap text-center bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                🔒 BLOQUEADA
              </span>
              <span :class="getStatusBadgeClass(task.status)" class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap text-center">
                {{ getStatusText(task.status) }}
              </span>
              <span v-if="task.priority" :class="getPriorityClass(task.priority)" class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap text-center">
                {{ getPriorityText(task.priority) }}
              </span>
            </div>
            
            <!-- Botones de acción -->
            <div class="flex flex-wrap gap-1 justify-end">
              <button
                @click.stop.prevent="handleEdit"
                class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-semibold rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                type="button"
              >
                ✏️ Editar
              </button>
              <button
                @click.stop.prevent="handleDependencies"
                class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs font-semibold rounded hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors"
                type="button"
                title="Dependencias"
              >
                🔗
              </button>
              <button
                @click.stop.prevent="handleDelete"
                class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                type="button"
                title="Eliminar"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>

        <!-- Información adicional -->
        <div class="grid grid-cols-3 gap-4 mt-3 text-sm">
          <!-- Responsable -->
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-gray-600">{{ task.assignee?.name || 'Sin asignar' }}</span>
          </div>

          <!-- Progreso -->
          <div class="flex items-center">
            <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
              <div 
                class="h-2 rounded-full transition-all"
                :class="task.progress === 100 ? 'bg-green-500' : 'bg-blue-500'"
                :style="`width: ${task.progress}%`"
              ></div>
            </div>
            <span class="text-gray-600 text-xs">{{ task.progress }}%</span>
          </div>

          <!-- Subtareas -->
          <div v-if="task.subtasks && task.subtasks.length > 0" class="flex items-center text-gray-600">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            {{ task.subtasks.length }} subtareas
          </div>
        </div>

        <!-- Información de dependencias -->
        <div v-if="task.is_blocked && (task.depends_on_task_id || task.depends_on_milestone_id)" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-sm">
          <div class="flex items-start">
            <span class="text-lg mr-2">🔒</span>
            <div class="flex-1">
              <p class="font-semibold text-red-800 dark:text-red-400 mb-1">Tarea Bloqueada</p>
              <p class="text-red-700 dark:text-red-300 text-xs">
                Esta tarea no puede iniciarse hasta completar:
              </p>
              <ul class="mt-1 space-y-1 text-xs text-red-700 dark:text-red-300">
                <li v-if="task.depends_on_task_id" class="flex items-center">
                  <span class="mr-1">📋</span>
                  <span class="font-medium">{{ task.depends_on_task?.title || `Tarea #${task.depends_on_task_id}` }}</span>
                </li>
                <li v-if="task.depends_on_milestone_id" class="flex items-center">
                  <span class="mr-1">⭐</span>
                  <span class="font-medium">{{ task.depends_on_milestone?.title || `Milestone #${task.depends_on_milestone_id}` }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Razón de bloqueo (legacy) -->
        <div v-else-if="task.status === 'blocked' && task.blocked_reason" class="mt-3 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-sm text-red-700 dark:text-red-300">
          🔒 <strong>Bloqueada:</strong> {{ task.blocked_reason }}
        </div>
      </div>
    </div>

    <!-- Subtareas (recursivo) -->
    <div v-if="task.subtasks && task.subtasks.length > 0" class="ml-6">
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
  // Prioridad: bloqueada > completada > en progreso > milestone > default
  if (task.is_blocked) return 'border-red-500 bg-red-50 dark:bg-red-900/10 dark:border-red-700'
  if (task.status === 'completed') return 'border-green-500 bg-green-50 dark:bg-green-900/10 dark:border-green-700'
  if (task.status === 'in_progress') return 'border-blue-500 bg-blue-50 dark:bg-blue-900/10 dark:border-blue-700'
  if (task.status === 'blocked') return 'border-red-500 bg-red-50 dark:bg-red-900/10 dark:border-red-700'
  if (task.is_milestone) return 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/10 dark:border-yellow-700'
  return 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800'
}

const getTaskIcon = (task) => {
  // Mostrar candado si está bloqueada, independientemente del estado
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
    pending: 'bg-gray-100 text-gray-800',
    blocked: 'bg-red-100 text-red-800',
    in_progress: 'bg-blue-100 text-blue-800',
    paused: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
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
    low: 'bg-blue-100 text-blue-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
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