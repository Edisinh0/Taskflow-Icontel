<template>
  <Transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 z-10">
          <!-- Header -->
          <div class="flex justify-between items-start mb-6">
            <h3 class="text-2xl font-bold text-gray-800">
              {{ isEditMode ? 'Editar Flujo' : 'Nuevo Flujo' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Formulario -->
          <form @submit.prevent="handleSubmit">
            <!-- Nombre -->
            <div class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Nombre del Flujo <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ej: Instalación Cliente XYZ"
              />
            </div>

            <!-- Descripción -->
            <div class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Descripción
              </label>
              <textarea
                v-model="formData.description"
                rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Describe el flujo de trabajo..."
              ></textarea>
            </div>

            <!-- Grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-4 mb-4">
              <!-- Plantilla -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Plantilla Base
                </label>
                <select
                  v-model="formData.template_id"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option :value="null">Sin plantilla</option>
                  <option v-for="template in templates" :key="template.id" :value="template.id">
                    {{ template.name }} (v{{ template.version }})
                  </option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                  Opcional: Selecciona una plantilla para pre-cargar tareas
                </p>
              </div>

              <!-- Estado -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Estado
                </label>
                <select
                  v-model="formData.status"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="active">Activo</option>
                  <option value="paused">Pausado</option>
                  <option value="completed">Completado</option>
                  <option value="cancelled">Cancelado</option>
                </select>
              </div>
            </div>

            <!-- Información de plantilla seleccionada -->
            <div v-if="selectedTemplate" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <h4 class="font-semibold text-blue-800 mb-2">📋 Plantilla: {{ selectedTemplate.name }}</h4>
              <p class="text-sm text-blue-700">{{ selectedTemplate.description }}</p>
              <div class="mt-2 text-xs text-blue-600">
                <span class="font-semibold">Versión:</span> {{ selectedTemplate.version }} |
                <span class="font-semibold">Duración estimada:</span> {{ selectedTemplate.config?.estimated_duration_days || 'N/A' }} días
              </div>
            </div>

            <!-- Error -->
            <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
              {{ error }}
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50"
              >
                {{ loading ? 'Guardando...' : (isEditMode ? 'Actualizar Flujo' : 'Crear Flujo') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { flowsAPI } from '@/services/api'

const props = defineProps({
  isOpen: Boolean,
  flow: Object,
  templates: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const error = ref(null)

const isEditMode = computed(() => !!props.flow)

const formData = ref({
  name: '',
  description: '',
  template_id: null,
  status: 'active'
})

const selectedTemplate = computed(() => {
  if (!formData.value.template_id) return null
  return props.templates.find(t => t.id === formData.value.template_id)
})

watch(() => props.flow, (newFlow) => {
  if (newFlow) {
    formData.value = {
      name: newFlow.name || '',
      description: newFlow.description || '',
      template_id: newFlow.template_id,
      status: newFlow.status || 'active'
    }
  } else {
    formData.value = {
      name: '',
      description: '',
      template_id: null,
      status: 'active'
    }
  }
}, { immediate: true })

const closeModal = () => {
  error.value = null
  emit('close')
}

const handleSubmit = async () => {
  try {
    loading.value = true
    error.value = null

    if (isEditMode.value) {
      await flowsAPI.update(props.flow.id, formData.value)
    } else {
      await flowsAPI.create(formData.value)
    }

    emit('saved')
    closeModal()
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar el flujo'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
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