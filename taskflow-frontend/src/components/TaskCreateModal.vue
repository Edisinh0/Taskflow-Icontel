<template>
  <Teleport to="body">
    <Transition
      name="modal"
      @enter="onEnter"
      @leave="onLeave"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm p-4"
        @click.self="closeModal"
      >
        <!-- Modal Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-slate-200 dark:border-white/10">
          <!-- Header -->
          <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-white/5 px-6 py-4 flex justify-between items-center z-10">
            <div>
              <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                Nueva Tarea
              </h2>
              <!-- Parent Context Badge -->
              <div class="mt-2 inline-flex items-center gap-2 px-3 py-1 bg-blue-50 dark:bg-blue-900/30 rounded-full border border-blue-200 dark:border-blue-500/30">
                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">
                  📌 Vinculado a: <span class="font-black">{{ parentType === 'Cases' ? `Caso #${parentId}` : `Oportunidad #${parentId}` }}</span>
                </span>
              </div>
            </div>
            <button
              @click="closeModal"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg"
              aria-label="Cerrar modal"
            >
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="submitForm" class="px-6 py-6">
            <div class="space-y-5">
              <!-- Título -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Título <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="formData.title"
                  type="text"
                  required
                  maxlength="255"
                  placeholder="Nombre de la tarea"
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                />
                <p v-if="errors.title" class="mt-1 text-sm text-rose-500">
                  {{ errors.title }}
                </p>
              </div>

              <!-- Prioridad -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Prioridad <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="formData.priority"
                  required
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                >
                  <option value="">Selecciona una prioridad</option>
                  <option value="High">🔴 Alta</option>
                  <option value="Medium">🟡 Media</option>
                  <option value="Low">🟢 Baja</option>
                </select>
                <p v-if="errors.priority" class="mt-1 text-sm text-rose-500">
                  {{ errors.priority }}
                </p>
              </div>

              <!-- Fecha de Inicio -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Fecha de Inicio <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="formData.dateStart"
                  type="datetime-local"
                  required
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                />
                <p v-if="errors.date_start" class="mt-1 text-sm text-rose-500">
                  {{ errors.date_start }}
                </p>
              </div>

              <!-- Fecha de Término -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Fecha de Término <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="formData.dateDue"
                  type="datetime-local"
                  required
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                />
                <p v-if="errors.date_due" class="mt-1 text-sm text-rose-500">
                  {{ errors.date_due }}
                </p>
              </div>

              <!-- Descripción -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Descripción
                </label>
                <textarea
                  v-model="formData.description"
                  rows="4"
                  maxlength="2000"
                  placeholder="Describe los detalles de la tarea..."
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none resize-none"
                ></textarea>
                <div class="mt-1 flex justify-between items-center">
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ formData.description?.length || 0 }}/2000 caracteres
                  </p>
                  <p v-if="errors.description" class="text-sm text-rose-500">
                    {{ errors.description }}
                  </p>
                </div>
              </div>

              <!-- Error General -->
              <div v-if="errors.general" class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-500/30 rounded-xl">
                <p class="text-sm font-bold text-rose-600 dark:text-rose-400">
                  ⚠️ {{ errors.general }}
                </p>
              </div>
            </div>

            <!-- Buttons -->
            <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
              <button
                type="button"
                @click="closeModal"
                :disabled="isLoading"
                class="px-6 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="isLoading || !isFormValid"
                class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 disabled:bg-slate-400 dark:disabled:bg-slate-600 rounded-xl transition-colors shadow-md hover:shadow-lg disabled:cursor-not-allowed flex items-center gap-2"
              >
                <span v-if="isLoading" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
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
})

const emit = defineEmits(['close', 'task-created'])

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

      // Emitir evento para refrescar lista
      emit('task-created', response.data)
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
