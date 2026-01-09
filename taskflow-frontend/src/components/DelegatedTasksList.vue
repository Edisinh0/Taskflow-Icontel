<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-2xl font-bold text-slate-800 dark:text-white">
          Tareas Delegadas a Ti
        </h3>
        <p class="text-slate-600 dark:text-slate-400 mt-1">
          Tareas de Ventas que requieren tu atención en Operaciones
        </p>
      </div>
      <span class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-lg font-bold">
        {{ delegatedTasks.length }}
      </span>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin">
        <Loader2 class="w-8 h-8 text-blue-500" />
      </div>
      <p class="ml-4 text-slate-600 dark:text-slate-400 font-bold">
        Cargando tareas delegadas...
      </p>
    </div>

    <!-- Empty state -->
    <div v-else-if="delegatedTasks.length === 0" class="flex flex-col items-center justify-center py-12 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900/20 dark:to-slate-800/20 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700">
      <CheckCircle2 class="w-12 h-12 text-green-500 mb-3" />
      <p class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-1">
        ¡Sin tareas pendientes!
      </p>
      <p class="text-slate-600 dark:text-slate-400">
        No tienes tareas delegadas de Ventas en este momento
      </p>
    </div>

    <!-- Tasks list -->
    <div v-else class="space-y-4">
      <div
        v-for="task in delegatedTasks"
        :key="task.id"
        class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-white/10 hover:shadow-lg dark:hover:shadow-xl transition-shadow"
      >
        <!-- Header del task -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">
              {{ task.title }}
            </h4>
            <p class="text-slate-600 dark:text-slate-400 text-sm line-clamp-2">
              {{ task.description }}
            </p>
          </div>
          <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-full text-xs font-bold whitespace-nowrap ml-2">
            {{ task.priority }}
          </span>
        </div>

        <!-- Información del delegador -->
        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-500/20">
          <p class="text-xs font-bold text-blue-600 dark:text-blue-400 mb-1 uppercase tracking-wide">
            Delegado por (Ventas)
          </p>
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
              <User class="w-4 h-4 text-white" />
            </div>
            <div>
              <p class="text-sm font-bold text-slate-800 dark:text-white">
                {{ task.original_sales_user?.name || 'Sin información' }}
              </p>
              <p class="text-xs text-blue-600 dark:text-blue-400">
                {{ task.original_sales_user?.email || '' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Caso asociado -->
        <div v-if="task.case" class="mb-4 p-3 bg-purple-50 dark:bg-purple-900/10 rounded-xl border border-purple-200 dark:border-purple-500/20">
          <p class="text-xs font-bold text-purple-600 dark:text-purple-400 mb-1 uppercase tracking-wide">
            Caso CRM
          </p>
          <div class="flex items-center gap-2">
            <Briefcase class="w-4 h-4 text-purple-500" />
            <span class="font-bold text-purple-700 dark:text-purple-300">#{{ task.case.case_number }}</span>
            <span class="text-sm text-purple-600 dark:text-purple-400 line-clamp-1">{{ task.case.subject }}</span>
          </div>
        </div>

        <!-- Razón de delegación -->
        <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-500/20">
          <p class="text-xs font-bold text-amber-600 dark:text-amber-400 mb-1 uppercase tracking-wide">
            Razón
          </p>
          <p class="text-sm text-amber-800 dark:text-amber-300">
            {{ task.delegation_reason }}
          </p>
        </div>

        <!-- Timeline -->
        <div class="mb-4 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
          <Clock class="w-4 h-4" />
          <span>Delegado hace {{ getTimeAgo(task.delegated_at) }}</span>
        </div>

        <!-- Acciones -->
        <div class="flex gap-3">
          <button
            @click="completeDelegation(task)"
            :disabled="isCompleting === task.id"
            class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white font-bold rounded-xl transition-colors text-sm flex items-center justify-center gap-2"
          >
            <CheckCircle2 class="w-4 h-4" />
            {{ isCompleting === task.id ? 'Completando...' : 'Marcar Completada' }}
          </button>
          <button
            @click="viewDetails(task)"
            class="flex-1 px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-white font-bold rounded-xl transition-colors text-sm flex items-center justify-center gap-2"
          >
            <Eye class="w-4 h-4" />
            Ver Detalles
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import {
  Loader2,
  CheckCircle2,
  Hand,
  Clock,
  Briefcase,
  User,
  Eye
} from 'lucide-vue-next'

const emit = defineEmits(['task-completed', 'view-details'])

const tasksStore = useTasksStore()
const delegatedTasks = ref([])
const loading = ref(false)
const isCompleting = ref(null)

onMounted(async () => {
  await loadDelegatedTasks()
})

const loadDelegatedTasks = async () => {
  loading.value = true
  try {
    const result = await tasksStore.getDelegatedTasks()
    delegatedTasks.value = result.data || []
  } catch (error) {
    console.error('Error loading delegated tasks:', error)
  } finally {
    loading.value = false
  }
}

const completeDelegation = async (task) => {
  isCompleting.value = task.id
  try {
    await tasksStore.completeDelegatedTask(task.id)
    // Remover de la lista
    delegatedTasks.value = delegatedTasks.value.filter(t => t.id !== task.id)
    emit('task-completed', task)
  } catch (error) {
    console.error('Error completing delegation:', error)
  } finally {
    isCompleting.value = null
  }
}

const viewDetails = (task) => {
  emit('view-details', task)
}

const getTimeAgo = (dateString) => {
  if (!dateString) return 'recientemente'
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) return 'hace menos de un minuto'
  if (diffMins < 60) return `hace ${diffMins} minuto${diffMins !== 1 ? 's' : ''}`
  if (diffHours < 24) return `hace ${diffHours} hora${diffHours !== 1 ? 's' : ''}`
  if (diffDays < 7) return `hace ${diffDays} día${diffDays !== 1 ? 's' : ''}`
  return date.toLocaleDateString('es-CL')
}
</script>
