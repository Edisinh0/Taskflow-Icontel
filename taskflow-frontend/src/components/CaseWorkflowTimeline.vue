<template>
  <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-white/10">
    <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-6">
      Historial de Workflow
    </h4>

    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="animate-spin">
        <Loader2 class="w-6 h-6 text-blue-500" />
      </div>
    </div>

    <div v-else-if="history.length > 0" class="space-y-6">
      <div
        v-for="(record, index) in history"
        :key="record.id"
        class="flex gap-4"
      >
        <!-- Timeline line -->
        <div class="flex flex-col items-center gap-2">
          <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="getStatusColor(record.action)">
            <component :is="getActionIcon(record.action)" class="w-5 h-5 text-white" />
          </div>
          <div v-if="index < history.length - 1" class="w-1 h-12 bg-gradient-to-b from-slate-300 to-slate-200 dark:from-slate-600 dark:to-slate-700"></div>
        </div>

        <!-- Timeline content -->
        <div class="flex-1 pb-4">
          <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <!-- Header -->
            <div class="flex items-start justify-between mb-2">
              <div>
                <p class="text-sm font-bold text-slate-800 dark:text-white">
                  {{ getActionLabel(record.action) }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                  {{ formatDate(record.created_at) }}
                </p>
              </div>
              <span class="px-2 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg">
                {{ record.to_status }}
              </span>
            </div>

            <!-- Usuario -->
            <p v-if="record.performed_by" class="text-xs text-slate-600 dark:text-slate-400 mb-2">
              Por: <span class="font-bold text-slate-800 dark:text-slate-200">{{ record.performed_by.name }}</span>
            </p>

            <!-- Notas -->
            <p v-if="record.notes" class="text-sm text-slate-700 dark:text-slate-300 mb-2">
              {{ record.notes }}
            </p>

            <!-- Razón (para rechazos) -->
            <div v-if="record.reason" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-500/20">
              <p class="text-xs font-bold text-red-700 dark:text-red-400 mb-1">Razón:</p>
              <p class="text-sm text-red-800 dark:text-red-300">{{ record.reason }}</p>
            </div>

            <!-- Sync status -->
            <div v-if="record.sweetcrm_sync_status" class="mt-3 flex items-center gap-2 text-xs">
              <span class="px-2 py-1 rounded-lg font-bold" :class="getSyncStatusColor(record.sweetcrm_sync_status)">
                {{ getSyncStatusLabel(record.sweetcrm_sync_status) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8">
      <p class="text-slate-500 dark:text-slate-400">
        Sin historial de cambios disponible
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCasesStore } from '@/stores/cases'
import {
  Loader2,
  Hand,
  CheckCircle2,
  XCircle,
  History,
  Upload
} from 'lucide-vue-next'

const props = defineProps({
  caseId: {
    type: Number,
    required: true
  }
})

const casesStore = useCasesStore()
const history = ref([])
const loading = ref(false)

onMounted(async () => {
  await loadHistory()
})

const loadHistory = async () => {
  loading.value = true
  try {
    const result = await casesStore.getWorkflowHistory(props.caseId)
    history.value = result.history || []
  } catch (error) {
    console.error('Error loading workflow history:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-CL', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getActionIcon = (action) => {
  const icons = {
    'handover_to_validation': Upload,
    'approve': CheckCircle2,
    'reject': XCircle,
    'delegate': Hand,
    'task_completed': CheckCircle2,
    'default': History
  }
  return icons[action] || icons['default']
}

const getActionLabel = (action) => {
  const labels = {
    'handover_to_validation': 'Enviado a Validación',
    'approve': 'Caso Aprobado',
    'reject': 'Caso Rechazado',
    'delegate': 'Tarea Delegada',
    'task_completed': 'Tarea Completada'
  }
  return labels[action] || action
}

const getStatusColor = (action) => {
  const colors = {
    'handover_to_validation': 'bg-blue-500',
    'approve': 'bg-green-500',
    'reject': 'bg-red-500',
    'delegate': 'bg-purple-500',
    'task_completed': 'bg-emerald-500',
    'default': 'bg-slate-400'
  }
  return colors[action] || colors['default']
}

const getSyncStatusColor = (status) => {
  const colors = {
    'synced': 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400',
    'pending': 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400',
    'failed': 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400'
  }
  return colors[status] || 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300'
}

const getSyncStatusLabel = (status) => {
  const labels = {
    'synced': '✓ Sincronizado con SugarCRM',
    'pending': '⏳ Pendiente de sincronización',
    'failed': '✗ Error en sincronización'
  }
  return labels[status] || status
}
</script>
