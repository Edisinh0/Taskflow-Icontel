<template>
  <Transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-3xl w-full p-6 z-10">
          <!-- Header -->
          <div class="flex justify-between items-start mb-6">
            <div>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                Gestionar Dependencias
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Tarea: {{ task?.title }}
              </p>
            </div>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Dependencias actuales -->
          <div class="mb-6">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
              Dependencias Actuales
            </h4>
            
            <div v-if="dependencies.length > 0" class="space-y-2">
              <div 
                v-for="dep in dependencies" 
                :key="dep.id"
                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ dep.depends_on_task?.title }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Tipo: {{ getDependencyTypeText(dep.dependency_type) }}
                    <span v-if="dep.lag_days !== 0"> • Retraso: {{ dep.lag_days }} días</span>
                  </p>
                </div>
                <button
                  @click="removeDependency(dep.id)"
                  class="ml-4 p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                  title="Eliminar dependencia"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>

            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
              <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <p>No hay dependencias configuradas</p>
            </div>
          </div>

          <!-- Agregar nueva dependencia -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
              Agregar Nueva Dependencia
            </h4>

            <form @submit.prevent="addDependency">
              <div class="grid grid-cols-2 gap-4 mb-4">
                <!-- Tarea dependiente -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Depende de la tarea
                  </label>
                  <select
                    v-model="newDependency.depends_on_task_id"
                    required
                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                  >
                    <option value="">Seleccionar tarea...</option>
                    <option 
                      v-for="t in availableTasks" 
                      :key="t.id" 
                      :value="t.id"
                    >
                      {{ t.title }}
                    </option>
                  </select>
                </div>

                <!-- Tipo de dependencia -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Tipo de Dependencia
                  </label>
                  <select
                    v-model="newDependency.dependency_type"
                    required
                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                  >
                    <option value="FS">Fin-Inicio (FS)</option>
                    <option value="SS">Inicio-Inicio (SS)</option>
                    <option value="FF">Fin-Fin (FF)</option>
                    <option value="SF">Inicio-Fin (SF)</option>
                  </select>
                </div>
              </div>

              <!-- Explicación del tipo -->
              <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                  {{ getDependencyExplanation(newDependency.dependency_type) }}
                </p>
              </div>

              <!-- Retraso -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Retraso (días) - Opcional
                </label>
                <input
                  v-model.number="newDependency.lag_days"
                  type="number"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                  placeholder="0"
                />
              </div>

              <!-- Error -->
              <div v-if="error" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 rounded-lg text-sm">
                {{ error }}
              </div>

              <!-- Botones -->
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium"
                >
                  Cerrar
                </button>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50"
                >
                  {{ loading ? 'Agregando...' : 'Agregar Dependencia' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  isOpen: Boolean,
  task: Object,
  availableTasks: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'updated'])

const dependencies = ref([])
const loading = ref(false)
const error = ref(null)

const newDependency = ref({
  depends_on_task_id: '',
  dependency_type: 'FS',
  lag_days: 0
})

watch(() => props.task, async (newTask) => {
  if (newTask) {
    await loadDependencies()
  }
}, { immediate: true })

const loadDependencies = async () => {
  if (!props.task) return
  
  try {
    const token = localStorage.getItem('token')
    const response = await axios.get(
      `http://localhost:8000/api/v1/tasks/${props.task.id}/dependencies`,
      { headers: { Authorization: `Bearer ${token}` } }
    )
    dependencies.value = response.data.data
  } catch (err) {
    console.error('Error cargando dependencias:', err)
  }
}

const addDependency = async () => {
  try {
    loading.value = true
    error.value = null

    const token = localStorage.getItem('token')
    await axios.post(
      `http://localhost:8000/api/v1/tasks/${props.task.id}/dependencies`,
      newDependency.value,
      { headers: { Authorization: `Bearer ${token}` } }
    )

    // Recargar dependencias
    await loadDependencies()
    
    // Limpiar formulario
    newDependency.value = {
      depends_on_task_id: '',
      dependency_type: 'FS',
      lag_days: 0
    }

    emit('updated')
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al agregar dependencia'
  } finally {
    loading.value = false
  }
}

const removeDependency = async (id) => {
  if (!confirm('¿Estás seguro de eliminar esta dependencia?')) return

  try {
    const token = localStorage.getItem('token')
    await axios.delete(
      `http://localhost:8000/api/v1/dependencies/${id}`,
      { headers: { Authorization: `Bearer ${token}` } }
    )

    await loadDependencies()
    emit('updated')
  } catch (err) {
    error.value = 'Error al eliminar dependencia'
  }
}

const closeModal = () => {
  error.value = null
  emit('close')
}

const getDependencyTypeText = (type) => {
  const types = {
    FS: 'Fin-Inicio',
    SS: 'Inicio-Inicio',
    FF: 'Fin-Fin',
    SF: 'Inicio-Fin'
  }
  return types[type] || type
}

const getDependencyExplanation = (type) => {
  const explanations = {
    FS: 'Esta tarea no puede iniciar hasta que la tarea seleccionada termine.',
    SS: 'Esta tarea y la tarea seleccionada deben iniciar al mismo tiempo.',
    FF: 'Esta tarea y la tarea seleccionada deben terminar al mismo tiempo.',
    SF: 'Esta tarea no puede terminar hasta que la tarea seleccionada inicie.'
  }
  return explanations[type] || ''
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