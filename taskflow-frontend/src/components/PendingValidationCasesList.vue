<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-2xl font-bold text-slate-800 dark:text-white">
          Casos Pendientes de Validación
        </h3>
        <p class="text-slate-600 dark:text-slate-400 mt-1">
          Casos enviados por Ventas que requieren tu validación
        </p>
      </div>
      <span class="px-4 py-2 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-lg font-bold">
        {{ cases.length }}
      </span>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin">
        <Loader2 class="w-8 h-8 text-blue-500" />
      </div>
      <p class="ml-4 text-slate-600 dark:text-slate-400 font-bold">
        Cargando casos pendientes...
      </p>
    </div>

    <!-- Empty state -->
    <div v-else-if="cases.length === 0" class="flex flex-col items-center justify-center py-12 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900/20 dark:to-slate-800/20 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700">
      <CheckCircle2 class="w-12 h-12 text-green-500 mb-3" />
      <p class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-1">
        ¡Todos al día!
      </p>
      <p class="text-slate-600 dark:text-slate-400">
        No hay casos pendientes de validación en este momento
      </p>
    </div>

    <!-- Cases grid -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div
        v-for="caseItem in cases"
        :key="caseItem.id"
        @click="selectCase(caseItem)"
        class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-white/10 hover:shadow-lg dark:hover:shadow-xl hover:border-yellow-300 dark:hover:border-yellow-500/30 cursor-pointer transition-all"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">
              #{{ caseItem.case_number }}
            </h4>
            <p class="text-slate-600 dark:text-slate-400 text-sm line-clamp-2">
              {{ caseItem.subject }}
            </p>
          </div>
          <AlertCircle class="w-6 h-6 text-yellow-500 flex-shrink-0" />
        </div>

        <!-- Cliente -->
        <div class="mb-4 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700">
          <p class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wide mb-1">
            Cliente
          </p>
          <p class="text-sm font-bold text-slate-800 dark:text-white">
            {{ caseItem.client?.name || 'Sin asignar' }}
          </p>
        </div>

        <!-- Solicitante (Ventas) -->
        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-500/20">
          <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-2">
            Solicitante (Ventas)
          </p>
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
              <User class="w-4 h-4 text-white" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-800 dark:text-white">
                {{ caseItem.original_sales_user?.name || 'N/A' }}
              </p>
              <p class="text-xs text-blue-600 dark:text-blue-400">
                {{ caseItem.original_sales_user?.email || '' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Tareas asociadas -->
        <div v-if="caseItem.tasks && caseItem.tasks.length > 0" class="mb-4 p-3 bg-purple-50 dark:bg-purple-900/10 rounded-xl border border-purple-200 dark:border-purple-500/20">
          <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wide mb-2">
            Tareas Asociadas ({{ caseItem.tasks.length }})
          </p>
          <div class="space-y-1">
            <div v-for="task in caseItem.tasks.slice(0, 2)" :key="task.id" class="text-xs text-purple-700 dark:text-purple-300">
              • {{ task.title }}
            </div>
            <p v-if="caseItem.tasks.length > 2" class="text-xs text-purple-600 dark:text-purple-400 italic">
              +{{ caseItem.tasks.length - 2 }} más...
            </p>
          </div>
        </div>

        <!-- Timeline de envío -->
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-4">
          <Clock class="w-4 h-4" />
          <span>Enviado hace {{ getTimeAgo(caseItem.pending_validation_at) }}</span>
        </div>

        <!-- Botones de acción -->
        <div class="flex gap-3">
          <button
            @click.stop="approveCase(caseItem)"
            :disabled="isProcessing === caseItem.id"
            class="flex-1 px-3 py-2 bg-green-500 hover:bg-green-600 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white font-bold rounded-xl transition-colors text-xs sm:text-sm flex items-center justify-center gap-2"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span class="hidden sm:inline">Aprobar</span>
            <span class="sm:hidden">Sí</span>
          </button>
          <button
            @click.stop="openRejectModal(caseItem)"
            :disabled="isProcessing === caseItem.id"
            class="flex-1 px-3 py-2 bg-red-500 hover:bg-red-600 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white font-bold rounded-xl transition-colors text-xs sm:text-sm flex items-center justify-center gap-2"
          >
            <XCircle class="w-4 h-4" />
            <span class="hidden sm:inline">Rechazar</span>
            <span class="sm:hidden">No</span>
          </button>
          <button
            @click.stop="viewDetails(caseItem)"
            class="flex-1 px-3 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-white font-bold rounded-xl transition-colors text-xs sm:text-sm flex items-center justify-center gap-2"
          >
            <Eye class="w-4 h-4" />
            <span class="hidden sm:inline">Detalles</span>
            <span class="sm:hidden">Ver</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de rechazo -->
    <Transition name="modal">
      <div
        v-if="showRejectModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm"
        @click.self="showRejectModal = false"
      >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 border border-slate-200 dark:border-white/10 p-6">
          <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-4">
            Rechazar Caso
          </h3>
          <p class="text-slate-600 dark:text-slate-400 mb-4">
            #{{ selectedCaseForReject?.case_number }} - {{ selectedCaseForReject?.subject }}
          </p>
          <textarea
            v-model="rejectReason"
            rows="4"
            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all mb-4"
            placeholder="Explica por qué rechazas este caso..."
          ></textarea>
          <div class="flex gap-3">
            <button
              @click="showRejectModal = false"
              class="flex-1 px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-white font-bold rounded-xl"
            >
              Cancelar
            </button>
            <button
              @click="confirmReject"
              :disabled="!rejectReason.trim() || isProcessing"
              class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 disabled:bg-slate-300 text-white font-bold rounded-xl disabled:cursor-not-allowed"
            >
              Rechazar
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCasesStore } from '@/stores/cases'
import {
  Loader2,
  CheckCircle2,
  XCircle,
  AlertCircle,
  Clock,
  User,
  Eye
} from 'lucide-vue-next'

const emit = defineEmits(['case-approved', 'case-rejected', 'view-details'])

const casesStore = useCasesStore()
const cases = ref([])
const loading = ref(false)
const isProcessing = ref(null)
const showRejectModal = ref(false)
const selectedCaseForReject = ref(null)
const rejectReason = ref('')

onMounted(async () => {
  await loadPendingCases()
})

const loadPendingCases = async () => {
  loading.value = true
  try {
    const result = await casesStore.getPendingValidationCases()
    cases.value = result.data || []
  } catch (error) {
    console.error('Error loading pending validation cases:', error)
  } finally {
    loading.value = false
  }
}

const approveCase = async (caseItem) => {
  isProcessing.value = caseItem.id
  try {
    await casesStore.approveCaseValidation(caseItem.id)
    cases.value = cases.value.filter(c => c.id !== caseItem.id)
    emit('case-approved', caseItem)
  } catch (error) {
    console.error('Error approving case:', error)
  } finally {
    isProcessing.value = null
  }
}

const openRejectModal = (caseItem) => {
  selectedCaseForReject.value = caseItem
  rejectReason.value = ''
  showRejectModal.value = true
}

const confirmReject = async () => {
  if (!rejectReason.value.trim() || !selectedCaseForReject.value) {
    return
  }

  isProcessing.value = selectedCaseForReject.value.id
  try {
    await casesStore.rejectCaseValidation(
      selectedCaseForReject.value.id,
      rejectReason.value
    )
    cases.value = cases.value.filter(c => c.id !== selectedCaseForReject.value.id)
    emit('case-rejected', {
      caseId: selectedCaseForReject.value.id,
      reason: rejectReason.value
    })
    showRejectModal.value = false
  } catch (error) {
    console.error('Error rejecting case:', error)
  } finally {
    isProcessing.value = null
  }
}

const viewDetails = (caseItem) => {
  emit('view-details', caseItem)
}

const selectCase = (caseItem) => {
  emit('view-details', caseItem)
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

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
