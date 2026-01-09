<template>
  <Teleport to="body">
    <Transition
      name="modal"
      @enter="onEnter"
      @leave="onLeave"
    >
      <!-- Overlay Backdrop -->
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto p-4"
      >
        <!-- Dark Overlay -->
        <div
          class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
          @click="closeModal"
        ></div>

        <!-- Modal Card -->
        <div
          class="relative z-[110] bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <!-- Header -->
          <div class="sticky top-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-start z-10">
            <div class="flex-1">
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                Nueva Tarea
              </h2>
              <!-- Parent Context Badge -->
              <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 rounded-full border border-blue-200 dark:border-blue-500/30">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">
                  Vinculado a: <span class="font-black">{{ parentName || `${parentType === 'Cases' ? 'Caso' : 'Oportunidad'} #${parentId}` }}</span>
                </span>
              </div>
            </div>
            <button
              @click="closeModal"
              type="button"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg flex-shrink-0"
              aria-label="Cerrar modal"
            >
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="submitForm" class="px-6 py-6 overflow-y-auto max-h-[calc(90vh-180px)]">
            <div class="space-y-6">
              <!-- 1. Título (Requerido) -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                  Título de la Tarea <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.title"
                  type="text"
                  required
                  maxlength="255"
                  placeholder="Ej: Contactar cliente para seguimiento"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                />
                <p v-if="errors.title" class="mt-1 text-sm text-red-500">
                  {{ errors.title }}
                </p>
              </div>

              <!-- 2. Descripción (Opcional) -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                  Descripción
                </label>
                <textarea
                  v-model="formData.description"
                  rows="3"
                  maxlength="2000"
                  placeholder="Proporciona más detalles sobre lo que necesita hacerse..."
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none resize-none"
                ></textarea>
                <div class="mt-1 flex justify-between items-center">
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formData.description?.length || 0 }}/2000 caracteres
                  </p>
                  <p v-if="errors.description" class="text-sm text-red-500">
                    {{ errors.description }}
                  </p>
                </div>
              </div>

              <!-- 3. Fechas (Grid de 2 columnas) -->
              <div class="grid grid-cols-2 gap-4">
                <!-- Fecha de Inicio -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Fecha Inicio <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="formData.dateStart"
                    type="datetime-local"
                    required
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                  />
                  <p v-if="errors.date_start" class="mt-1 text-xs text-red-500">
                    {{ errors.date_start }}
                  </p>
                </div>

                <!-- Fecha de Término -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Fecha Término <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="formData.dateDue"
                    type="datetime-local"
                    required
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                  />
                  <p v-if="errors.date_due" class="mt-1 text-xs text-red-500">
                    {{ errors.date_due }}
                  </p>
                </div>
              </div>

              <!-- 4. Prioridad (Último campo) -->
              <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                  Prioridad <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="formData.priority"
                  required
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                >
                  <option value="">Selecciona una prioridad</option>
                  <option value="High">🔴 Alta</option>
                  <option value="Medium">🟡 Media</option>
                  <option value="Low">🟢 Baja</option>
                </select>
                <p v-if="errors.priority" class="mt-1 text-sm text-red-500">
                  {{ errors.priority }}
                </p>
              </div>

              <!-- Error General -->
              <div v-if="errors.general" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-500/30 rounded-lg">
                <div class="flex gap-2">
                  <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                  <p class="text-sm font-bold text-red-600 dark:text-red-400">
                    {{ errors.general }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Buttons Footer -->
            <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
              <button
                type="button"
                @click="closeModal"
                :disabled="isLoading"
                class="px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="isLoading || !isFormValid"
                class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 dark:disabled:bg-gray-600 rounded-lg transition-colors shadow-md hover:shadow-lg disabled:cursor-not-allowed flex items-center gap-2"
              >
                <svg v-if="!isLoading" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                {{ isLoading ? 'Creando...' : 'Crear Tarea' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTasksStore } from '@/stores/tasks'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  parentId: {
    type: [String, Number],
    required: true,
  },
  parentType: {
    type: String,
    required: true,
    validator: (value) => ['Cases', 'Opportunities'].includes(value),
  },
  parentName: {
    type: String,
    default: null,
    description: 'Nombre del caso u oportunidad padre para mostrar en el badge',
  },
})

const emit = defineEmits(['close', 'task-created', 'success'])

const tasksStore = useTasksStore()
const isLoading = ref(false)
const errors = ref({})

const formData = ref({
  title: '',
  description: '',
  priority: 'Medium',
  dateStart: '',
  dateDue: '',
})

// Validación computed
const isFormValid = computed(() => {
  return (
    formData.value.title.trim() !== '' &&
    formData.value.priority !== '' &&
    formData.value.dateStart !== '' &&
    formData.value.dateDue !== '' &&
    props.parentId &&
    props.parentId !== 'undefined' &&
    props.parentId !== 'null'
  )
})

// Inicializar fechas por defecto (hoy y mañana a las 12:00)
watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      const today = new Date()
      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)
      today.setHours(9, 0, 0, 0)
      tomorrow.setHours(9, 0, 0, 0)

      formData.value.dateStart = formatDateTimeLocal(today)
      formData.value.dateDue = formatDateTimeLocal(tomorrow)
      errors.value = {}
    }
  }
)

/**
 * Formatear fecha a datetime-local (YYYY-MM-DDTHH:mm)
 */
function formatDateTimeLocal(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')

  return `${year}-${month}-${day}T${hours}:${minutes}`
}

/**
 * Convertir datetime-local a Y-m-d H:i:s para backend
 */
function formatDateForBackend(dateTimeLocalString) {
  if (!dateTimeLocalString) return null

  const [date, time] = dateTimeLocalString.split('T')
  const [hours, minutes] = time.split(':')

  return `${date} ${hours}:${minutes}:00`
}

async function submitForm() {
  // Prevenir doble submit
  if (isLoading.value) {
    return
  }

  // Limpiar errores previos
  errors.value = {}

  // Validaciones básicas del cliente
  if (!formData.value.title.trim()) {
    errors.value.title = 'El nombre de la tarea es requerido'
    return
  }

  if (!formData.value.priority) {
    errors.value.priority = 'La prioridad es requerida'
    return
  }

  if (!formData.value.dateStart) {
    errors.value.date_start = 'La fecha de inicio es requerida'
    return
  }

  if (!formData.value.dateDue) {
    errors.value.date_due = 'La fecha de término es requerida'
    return
  }

  // Validar que fecha_inicio <= fecha_termino
  const startDate = new Date(formData.value.dateStart)
  const dueDate = new Date(formData.value.dateDue)

  if (startDate > dueDate) {
    errors.value.date_start = 'La fecha de inicio debe ser anterior a la de término'
    return
  }

  // Validación crítica: Parent ID debe existir y ser válido
  if (!props.parentId || props.parentId === 'undefined' || props.parentId === 'null') {
    errors.value.general = 'Error: No se puede crear tarea sin entidad padre asociada. Por favor recarga la página.'
    return
  }

  isLoading.value = true

  try {
    // Preparar payload para backend
    const payload = {
      title: formData.value.title.trim(),
      description: formData.value.description.trim() || null,
      priority: formData.value.priority,
      date_start: formatDateForBackend(formData.value.dateStart),
      date_due: formatDateForBackend(formData.value.dateDue),
      parent_type: props.parentType,
      parent_id: String(props.parentId),
    }

    // Llamar acción del store
    const response = await tasksStore.createTask(payload)

    if (response?.success && response?.data) {
      // Validar que la respuesta contiene datos válidos
      if (!response.data.id) {
        errors.value.general = 'Respuesta inválida del servidor. Por favor intenta de nuevo.'
        return
      }

      // Emitir eventos de éxito
      emit('task-created', response.data)
      emit('success', {
        message: response.message || 'Tarea creada exitosamente',
        data: response.data,
      })
      closeModal()

      // Resetear formulario
      formData.value = {
        title: '',
        description: '',
        priority: 'Medium',
        dateStart: '',
        dateDue: '',
      }
    } else {
      errors.value.general = response?.message || 'Error al crear la tarea'
    }
  } catch (error) {
    console.error('Error creating task:', error)
    // Mejor manejo de errores diferenciando tipos
    if (error.response?.status === 422) {
      errors.value.general = error.response?.data?.message || 'Validación fallida. Verifica los datos.'
    } else if (error.response?.status === 404) {
      errors.value.general = 'La entidad padre no existe. Por favor recarga la página.'
    } else if (error.response?.status >= 500) {
      errors.value.general = 'Error del servidor. Por favor intenta de nuevo más tarde.'
    } else if (!error.response) {
      errors.value.general = 'Error de conexión. Verifica tu conexión a internet.'
    } else {
      errors.value.general = error.message || 'Error al crear la tarea'
    }
  } finally {
    isLoading.value = false
  }
}

function closeModal() {
  emit('close')
  errors.value = {}
  formData.value = {
    title: '',
    description: '',
    priority: 'Medium',
    dateStart: '',
    dateDue: '',
  }
}

function onEnter(el) {
  el.style.animation = 'none'
  el.offsetHeight
  el.style.animation = ''
}

function onLeave(el) {
  el.style.animation = ''
}
</script>

<style scoped>
.modal-enter-active {
  animation: modalFadeIn 0.3s ease-out;
}

.modal-leave-active {
  animation: modalFadeOut 0.2s ease-in;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes modalFadeOut {
  from {
    opacity: 1;
    transform: scale(1);
  }
  to {
    opacity: 0;
    transform: scale(0.95);
  }
}
</style>
