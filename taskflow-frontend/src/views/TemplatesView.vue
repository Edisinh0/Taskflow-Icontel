<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Plantillas de Flujos</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Crea y gestiona plantillas reutilizables</p>
      </div>

      <!-- Grid de Plantillas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="template in templates" 
          :key="template.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-soft dark:shadow-none border border-gray-100 dark:border-gray-700 p-6 hover:shadow-medium transition-all group"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <h3 class="text-xl font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-1">
                {{ template.name }}
              </h3>
              <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                Versión {{ template.version }}
              </span>
            </div>
            <span 
              :class="template.is_active 
                ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400' 
                : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400'"
              class="px-3 py-1 text-xs font-semibold rounded-full"
            >
              {{ template.is_active ? 'Activa' : 'Inactiva' }}
            </span>
          </div>

          <!-- Descripción -->
          <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">
            {{ template.description }}
          </p>

          <!-- Información adicional -->
          <div v-if="template.config" class="space-y-2 mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div v-if="template.config.estimated_duration_days" class="flex items-center text-sm">
              <svg class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-gray-700 dark:text-gray-300">
                Duración: <span class="font-semibold">{{ template.config.estimated_duration_days }} días</span>
              </span>
            </div>
            <div v-if="template.config.required_roles" class="flex items-center text-sm">
              <svg class="w-4 h-4 mr-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span class="text-gray-700 dark:text-gray-300">
                Roles: <span class="font-semibold">{{ template.config.required_roles.join(', ') }}</span>
              </span>
            </div>
          </div>

          <!-- Estadísticas -->
          <div class="flex items-center justify-between text-sm mb-4">
            <div class="flex items-center text-gray-500 dark:text-gray-400">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Por: {{ template.creator?.name }}
            </div>
            <div class="text-gray-500 dark:text-gray-400">
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              {{ getFlowCount(template.id) }} flujos
            </div>
          </div>

          <!-- Botón de acción -->
          <button
            @click="useTemplate(template)"
            class="w-full py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all font-medium shadow-sm flex items-center justify-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Usar Plantilla
          </button>
        </div>

        <!-- Card vacía -->
        <div v-if="templates.length === 0" class="col-span-full flex flex-col items-center justify-center py-12">
          <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">No hay plantillas disponibles</p>
          <p class="text-gray-400 dark:text-gray-500 text-sm">Crea tu primera plantilla</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { templatesAPI, flowsAPI } from '@/services/api'
import Navbar from '@/components/Navbar.vue'

const router = useRouter()
const templates = ref([])
const flows = ref([])

const getFlowCount = (templateId) => {
  return flows.value.filter(f => f.template_id === templateId).length
}

const useTemplate = (template) => {
  // Redirigir a crear flujo con esta plantilla pre-seleccionada
  router.push({
    path: '/flows',
    query: { template: template.id }
  })
}

const loadData = async () => {
  try {
    const [templatesResponse, flowsResponse] = await Promise.all([
      templatesAPI.getAll(),
      flowsAPI.getAll()
    ])
    templates.value = templatesResponse.data.data
    flows.value = flowsResponse.data.data
  } catch (error) {
    console.error('Error cargando plantillas:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>