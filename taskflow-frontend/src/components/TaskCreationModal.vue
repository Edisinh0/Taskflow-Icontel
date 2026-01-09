<template>
  <Transition name="modal">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4 border border-slate-200 dark:border-white/10">
        <!-- Header -->
        <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-white/5 px-6 py-4 flex justify-between items-center z-10">
          <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
            Nueva Tarea
          </h2>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>

        <!-- Form -->
        <div class="px-6 pb-6 pt-6">
          <form @submit.prevent="handleSubmit">

            <!-- Title -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Título <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="formData.title"
                type="text"
                required
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="Nombre de la tarea"
              />
            </div>

            <!-- Description -->
            <div class="mb-5">
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                Descripción <span class="text-rose-500">*</span>
              </label>
              <textarea
                v-model="formData.description"
                required
                rows="3"
                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                placeholder="Describe la tarea..."
              ></textarea>
            </div>

            <!-- Parent Linking Section -->
            <div class="mb-6 p-5 bg-purple-50 dark:bg-purple-900/10 rounded-xl border border-purple-200 dark:border-purple-500/20">
              <h4 class="text-sm font-bold text-purple-700 dark:text-purple-300 mb-4 flex items-center">
                <Link2 class="w-4 h-4 mr-2" />
                Vincular a Caso u Oportunidad <span class="text-rose-500 ml-1">*</span>
              </h4>

              <!-- Parent Type Selector -->
              <div class="mb-4">
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                  Tipo de Vínculo
                </label>
                <div class="grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    @click="formData.parent_type = 'Cases'"
                    :class="[
                      'px-4 py-3 rounded-xl border-2 font-bold transition-all',
                      formData.parent_type === 'Cases'
                        ? 'border-purple-500 bg-purple-500 text-white shadow-lg'
                        : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-purple-400'
                    ]"
                  >
                    <Briefcase class="w-5 h-5 mx-auto mb-1" />
                    Caso
                  </button>
                  <button
                    type="button"
                    @click="formData.parent_type = 'Opportunities'"
                    :class="[
                      'px-4 py-3 rounded-xl border-2 font-bold transition-all',
                      formData.parent_type === 'Opportunities'
                        ? 'border-purple-500 bg-purple-500 text-white shadow-lg'
                        : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-purple-400'
                    ]"
                  >
                    <TrendingUp class="w-5 h-5 mx-auto mb-1" />
                    Oportunidad
                  </button>
                </div>
              </div>

              <!-- Search Input -->
              <div v-if="formData.parent_type" class="mb-4">
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                  Buscar {{ formData.parent_type === 'Cases' ? 'Caso' : 'Oportunidad' }}
                </label>
                <div class="relative">
                  <Search class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                  <input
                    v-model="searchQuery"
                    type="text"
                    @input="handleSearch"
                    placeholder="Escribe al menos 2 caracteres..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm"
                  />
                  <div v-if="searching" class="absolute right-3 top-3">
                    <Loader2 class="w-4 h-4 animate-spin text-purple-500" />
                  </div>
                </div>
              </div>

              <!-- Search Results Dropdown -->
              <div v-if="searchResults.length > 0" class="mb-4 max-h-60 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900">
                <button
                  v-for="result in searchResults"
                  :key="result.id"
                  type="button"
                  @click="selectParent(result)"
                  class="w-full px-4 py-3 text-left hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-b-0"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-bold text-slate-800 dark:text-white">
                        {{ result.name }}
                      </p>
                      <p class="text-xs text-slate-500 dark:text-slate-400">
                        <span v-if="formData.parent_type === 'Cases'">
                          #{{ result.case_number }} - {{ result.status }}
                        </span>
                        <span v-else>
                          {{ result.sales_stage }} - {{ result.probability }}%
                        </span>
                      </p>
                    </div>
                    <CheckCircle2 v-if="formData.parent_id === result.id" class="w-5 h-5 text-purple-500" />
                  </div>
                </button>
              </div>

              <!-- Selected Parent Display -->
              <div v-if="selectedParent" class="p-4 bg-purple-100 dark:bg-purple-900/30 rounded-xl border border-purple-300 dark:border-purple-500/30">
                <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider mb-1">
                  Seleccionado
                </p>
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-white">{{ selectedParent.name }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                      {{ formData.parent_type === 'Cases' ? `#${selectedParent.case_number}` : selectedParent.sales_stage }}
                    </p>
                  </div>
                  <button
                    type="button"
                    @click="clearParent"
                    class="text-rose-500 hover:text-rose-600 transition-colors"
                  >
                    <XCircle class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <!-- No results message -->
              <div v-if="searchQuery && searchQuery.length >= 2 && searchResults.length === 0 && !searching" class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                  No se encontraron resultados para "{{ searchQuery }}"
                </p>
              </div>
            </div>

            <!-- Grid: Assignee + Priority -->
            <div class="grid grid-cols-2 gap-5 mb-5">
              <!-- Assignee -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Responsable <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="formData.assignee_id"
                  required
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option :value="null" disabled>Selecciona...</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <!-- Priority -->
              <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                  Prioridad <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="formData.priority"
                  required
                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
                  <option value="low">Baja</option>
                  <option value="medium">Media</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mb-6 p-4 bg-rose-900/20 border border-rose-500/30 text-rose-400 rounded-xl text-sm flex items-start">
              <AlertCircle class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" />
              {{ error }}
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 border-t border-slate-200 dark:border-white/5 pt-6">
              <button
                type="button"
                @click="closeModal"
                class="px-6 py-2.5 border border-slate-300 dark:border-slate-600/50 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white font-bold transition-all"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="loading || !canSubmit"
                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-bold shadow-lg shadow-blue-900/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              >
                <span v-if="loading" class="mr-2">
                  <Loader2 class="w-4 h-4 animate-spin" />
                </span>
                Crear Tarea
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import {
  X,
  Link2,
  Briefcase,
  TrendingUp,
  Search,
  Loader2,
  CheckCircle2,
  XCircle,
  AlertCircle
} from 'lucide-vue-next'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  users: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'created'])

const tasksStore = useTasksStore()

const loading = ref(false)
const searching = ref(false)
const error = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const selectedParent = ref(null)

// Debounce timer
let searchTimeout = null

const formData = ref({
  title: '',
  description: '',
  parent_type: null, // 'Cases' or 'Opportunities'
  parent_id: null,
  assignee_id: null,
  priority: 'medium'
})

// Can submit if all required fields are filled
const canSubmit = computed(() => {
  return formData.value.title &&
         formData.value.description &&
         formData.value.parent_type &&
         formData.value.parent_id &&
         formData.value.assignee_id &&
         formData.value.priority
})

// Watch parent_type change to reset search
watch(() => formData.value.parent_type, () => {
  searchQuery.value = ''
  searchResults.value = []
  selectedParent.value = null
  formData.value.parent_id = null
})

// Debounced search handler
const handleSearch = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  const query = searchQuery.value.trim()

  // Clear results if query too short
  if (query.length < 2) {
    searchResults.value = []
    return
  }

  searching.value = true

  searchTimeout = setTimeout(async () => {
    try {
      const results = await tasksStore.searchCrmEntities(
        formData.value.parent_type,
        query,
        10
      )
      searchResults.value = results
    } catch (err) {
      console.error('Search error:', err)
      searchResults.value = []
    } finally {
      searching.value = false
    }
  }, 300) // 300ms debounce
}

const selectParent = (parent) => {
  selectedParent.value = parent
  formData.value.parent_id = parent.id
  searchResults.value = []
  searchQuery.value = parent.name
}

const clearParent = () => {
  selectedParent.value = null
  formData.value.parent_id = null
  searchQuery.value = ''
}

const closeModal = () => {
  resetForm()
  emit('close')
}

const resetForm = () => {
  formData.value = {
    title: '',
    description: '',
    parent_type: null,
    parent_id: null,
    assignee_id: null,
    priority: 'medium'
  }
  searchQuery.value = ''
  searchResults.value = []
  selectedParent.value = null
  error.value = null
}

const handleSubmit = async () => {
  if (!canSubmit.value) {
    error.value = 'Por favor completa todos los campos obligatorios'
    return
  }

  try {
    loading.value = true
    error.value = null

    // Prepare data for API
    const taskData = {
      title: formData.value.title,
      description: formData.value.description,
      sweetcrm_parent_type: formData.value.parent_type,
      sweetcrm_parent_id: formData.value.parent_id,
      assignee_id: parseInt(formData.value.assignee_id),
      priority: formData.value.priority
    }

    await tasksStore.createTask(taskData)

    emit('created')
    closeModal()
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al crear la tarea'
    console.error('Error creating task:', err)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.modal-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.6, 1);
}

.modal-enter-from {
  opacity: 0;
}

.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div {
  transform: scale(0.95) translateY(-20px);
  opacity: 0;
}

.modal-leave-to > div {
  transform: scale(0.95) translateY(20px);
  opacity: 0;
}
</style>
