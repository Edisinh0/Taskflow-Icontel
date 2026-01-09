<template>
  <div v-if="caseData" class="space-y-6">
    <!-- Header con información del caso -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-500/20">
      <div class="flex items-start justify-between mb-4">
        <div>
          <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">
            Caso #{{ caseData.case_number }}
          </h3>
          <p class="text-slate-600 dark:text-slate-400">{{ caseData.subject }}</p>
        </div>
        <span class="px-4 py-2 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full text-sm font-bold">
          En Validación
        </span>
      </div>

      <!-- Info del solicitante -->
      <div class="grid grid-cols-2 gap-4 pt-4 border-t border-blue-200 dark:border-blue-500/20">
        <div>
          <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">
            Solicitante (Ventas)
          </p>
          <p class="text-sm font-bold text-slate-800 dark:text-white">
            {{ caseData.original_sales_user?.name || 'N/A' }}
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ caseData.original_sales_user?.email || '' }}
          </p>
        </div>
        <div>
          <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">
            Cliente
          </p>
          <p class="text-sm font-bold text-slate-800 dark:text-white">
            {{ caseData.client?.name || 'Sin asignar' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Descripción del caso -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-white/10">
      <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-4">
        Descripción
      </h4>
      <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
        {{ caseData.description || 'Sin descripción' }}
      </p>
    </div>

    <!-- Tareas asociadas -->
    <div v-if="caseData.tasks && caseData.tasks.length > 0" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-white/10">
      <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-4">
        Tareas Asociadas ({{ caseData.tasks.length }})
      </h4>
      <div class="space-y-2">
        <div
          v-for="task in caseData.tasks"
          :key="task.id"
          class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700"
        >
          <CheckCircle2 v-if="task.status === 'completed'" class="w-5 h-5 text-green-500" />
          <Circle v-else class="w-5 h-5 text-slate-400" />
          <div class="flex-1">
            <p class="text-sm font-bold text-slate-800 dark:text-white">{{ task.title }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Estado: {{ task.status }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Información de validación -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-white/10">
      <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-4">
        Información de Validación
      </h4>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Enviado para validación</p>
          <p class="text-sm font-bold text-slate-800 dark:text-white">
            {{ formatDate(caseData.pending_validation_at) }}
          </p>
        </div>
        <div>
          <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Por</p>
          <p class="text-sm font-bold text-slate-800 dark:text-white">
            {{ caseData.validation_initiated_by?.name || 'Sistema' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Acciones de validación -->
    <div class="bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/10 dark:to-red-900/10 rounded-2xl p-6 border border-orange-200 dark:border-orange-500/20">
      <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-4">
        Decisión de Validación
      </h4>

      <div v-if="!isProcessing" class="space-y-4">
        <!-- Rechazar -->
        <div class="mb-4">
          <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
            Si deseas rechazar, ingresa la razón:
          </label>
          <textarea
            v-model="rejectionReason"
            rows="3"
            class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
            placeholder="Explica por qué rechazas este caso..."
          ></textarea>
        </div>

        <!-- Botones de acción -->
        <div class="flex gap-4">
          <button
            @click="approveCase"
            class="flex-1 px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-bold rounded-xl transition-colors shadow-md hover:shadow-lg"
          >
            <CheckCircle2 class="w-5 h-5 inline mr-2" />
            Aprobar
          </button>
          <button
            @click="rejectCase"
            :disabled="!rejectionReason.trim()"
            class="flex-1 px-6 py-3 bg-red-500 hover:bg-red-600 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white font-bold rounded-xl transition-colors shadow-md hover:shadow-lg disabled:cursor-not-allowed"
          >
            <XCircle class="w-5 h-5 inline mr-2" />
            Rechazar
          </button>
        </div>
      </div>

      <!-- Loading state -->
      <div v-else class="flex items-center justify-center py-6">
        <div class="animate-spin">
          <Loader2 class="w-6 h-6 text-blue-500" />
        </div>
        <p class="ml-3 text-slate-600 dark:text-slate-400 font-bold">
          Procesando decisión...
        </p>
      </div>
    </div>

    <!-- Historial de workflow -->
    <CaseWorkflowTimeline v-if="showHistory" :case-id="caseData.id" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCasesStore } from '@/stores/cases'
import {
  CheckCircle2,
  XCircle,
  Circle,
  Loader2
} from 'lucide-vue-next'
import CaseWorkflowTimeline from './CaseWorkflowTimeline.vue'

const props = defineProps({
  caseId: {
    type: Number,
    required: true
  },
  showHistory: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['approved', 'rejected', 'error'])

const casesStore = useCasesStore()
const caseData = ref(null)
const isProcessing = ref(false)
const rejectionReason = ref('')

onMounted(async () => {
  try {
    // Cargar el caso específico
    caseData.value = casesStore.cases.find(c => c.id === props.caseId)
  } catch (error) {
    console.error('Error loading case:', error)
    emit('error', error)
  }
})

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-CL', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const approveCase = async () => {
  isProcessing.value = true
  try {
    await casesStore.approveCaseValidation(props.caseId)
    emit('approved', caseData.value)
  } catch (error) {
    console.error('Error approving case:', error)
    emit('error', error)
  } finally {
    isProcessing.value = false
  }
}

const rejectCase = async () => {
  if (!rejectionReason.value.trim()) {
    return
  }

  isProcessing.value = true
  try {
    await casesStore.rejectCaseValidation(props.caseId, rejectionReason.value)
    emit('rejected', { caseId: props.caseId, reason: rejectionReason.value })
  } catch (error) {
    console.error('Error rejecting case:', error)
    emit('error', error)
  } finally {
    isProcessing.value = false
  }
}
</script>
