<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
          @click="closeModal"
        ></div>

        <!-- Modal -->
        <div class="flex min-h-screen items-center justify-center p-4">
          <div
            class="relative w-full max-w-md transform rounded-lg bg-white shadow-xl transition-all"
          >
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                  Nueva Tarea
                </h3>
                <button
                  type="button"
                  @click="closeModal"
                  class="text-gray-400 hover:text-gray-500"
                >
                  <span class="sr-only">Cerrar</span>
                  <svg
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
              <p v-if="parentType === 'Cases'" class="mt-1 text-sm text-gray-600">
                Para caso #{{ parentId }}
              </p>
              <p v-else-if="parentType === 'Opportunities'" class="mt-1 text-sm text-gray-600">
                Para oportunidad #{{ parentId }}
              </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="px-6 py-4">
              <div class="space-y-4">
                <!-- Nombre de la tarea -->
                <div>
                  <label for="title" class="block text-sm font-medium text-gray-700">
                    Nombre de la Tarea *
                  </label>
                  <input
                    id="title"
                    v-model="formData.title"
                    type="text"
                    required
                    maxlength="255"
                    placeholder="Ej: Contactar cliente"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
                  />
                  <p v-if="errors.title" class="mt-1 text-sm text-red-600">
                    {{ errors.title }}
                  </p>
                </div>

                <!-- Prioridad -->
                <div>
                  <label for="priority" class="block text-sm font-medium text-gray-700">
                    Prioridad *
                  </label>
                  <select
                    id="priority"
                    v-model="formData.priority"
                    required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
                  >
                    <option value="">Selecciona una prioridad</option>
                    <option value="High">Alta</option>
                    <option value="Medium">Media</option>
                    <option value="Low">Baja</option>
                  </select>
                  <p v-if="errors.priority" class="mt-1 text-sm text-red-600">
                    {{ errors.priority }}
                  </p>
                </div>

                <!-- Fecha de Inicio -->
                <div>
                  <label for="dateStart" class="block text-sm font-medium text-gray-700">
                    Fecha de Inicio *
                  </label>
                  <input
                    id="dateStart"
                    v-model="formData.dateStart"
                    type="datetime-local"
                    required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
                  />
                  <p v-if="errors.date_start" class="mt-1 text-sm text-red-600">
                    {{ errors.date_start }}
                  </p>
                </div>

                <!-- Fecha de Término -->
                <div>
                  <label for="dateDue" class="block text-sm font-medium text-gray-700">
                    Fecha de Término *
                  </label>
                  <input
                    id="dateDue"
                    v-model="formData.dateDue"
                    type="datetime-local"
                    required
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
                  />
                  <p v-if="errors.date_due" class="mt-1 text-sm text-red-600">
                    {{ errors.date_due }}
                  </p>
                </div>

                <!-- Descripción -->
                <div>
                  <label for="description" class="block text-sm font-medium text-gray-700">
                    Descripción
                  </label>
                  <textarea
                    id="description"
                    v-model="formData.description"
                    rows="3"
                    maxlength="2000"
                    placeholder="Describe los detalles de la tarea..."
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
                  ></textarea>
                  <p class="mt-1 text-xs text-gray-500">
                    {{ formData.description?.length || 0 }}/2000 caracteres
                  </p>
                  <p v-if="errors.description" class="mt-1 text-sm text-red-600">
                    {{ errors.description }}
                  </p>
                </div>

              </div>

              <!-- Error general -->
              <p v-if="errors.general" class="mt-4 text-sm text-red-600">
                {{ errors.general }}
              </p>

              <!-- Buttons -->
              <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
                <button
                  type="button"
                  @click="closeModal"
                  :disabled="isLoading"
                  class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="isLoading"
                  class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                  <span v-if="isLoading" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                  {{ isLoading ? 'Guardando...' : 'Crear Tarea' }}
                </button>
              </div>
            </form>
          </div>
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
    type: String,
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

// Inicializar fechas por defecto (hoy y mañana a las 12:00)
watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      const today = new Date()
      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)

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

  // dateTimeLocalString está en formato: 2026-01-09T14:30
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

  // Validación crítica: Parent ID debe existir y ser válido
  if (!props.parentId || props.parentId === 'undefined' || props.parentId === 'null') {
    errors.value.general = 'No se puede crear tarea sin entidad padre asociada. Por favor recarga la página.'
    return
  }

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
      parent_id: props.parentId,
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
