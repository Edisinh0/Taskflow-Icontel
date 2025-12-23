<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors">
    <!-- Navbar profesional -->
    <Navbar />

    <!-- Contenido -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Plantillas de Flujos</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Crea y gestiona plantillas reutilizables para optimizar tu trabajo</p>
      </div>

      <!-- Grid de Plantillas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="template in templates" 
          :key="template.id"
          class="bg-white dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg hover:shadow-xl dark:hover:shadow-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all p-6 border border-slate-200 dark:border-white/5 group"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1 pr-4">
              <h3 class="text-xl font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-1 line-wrap">
                {{ template.name }}
              </h3>
              <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-500 bg-slate-100 dark:bg-slate-900/50 px-2 py-0.5 rounded">
                Versión {{ template.version }}
              </span>
            </div>
            
            <div class="flex flex-col items-end gap-2">
              <span 
                :class="template.is_active 
                  ? 'bg-green-500/10 text-green-500 dark:text-green-400 border border-green-500/20' 
                  : 'bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-600/30'"
                class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full whitespace-nowrap"
              >
                {{ template.is_active ? 'Activa' : 'Inactiva' }}
              </span>

              <button 
                @click.stop="deleteTemplate(template)"
                class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
                title="Eliminar plantilla"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Descripción -->
          <p class="text-slate-500 dark:text-slate-400 text-sm mb-4 line-clamp-3 h-15">
            {{ template.description || 'Sin descripción disponible.' }}
          </p>

          <!-- Industrias Aplicables -->
          <div v-if="template.industries && template.industries.length > 0" class="mb-6">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
              Industrias
            </p>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="industry in template.industries"
                :key="industry.id"
                class="px-3 py-1 bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-full border border-blue-200 dark:border-blue-500/30"
              >
                {{ industry.name }}
              </span>
            </div>
          </div>

          <!-- Información adicional -->
          <div v-if="template.config" class="space-y-3 mb-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-white/5">
            <div v-if="template.config.estimated_duration_days" class="flex items-center text-sm">
              <svg class="w-4 h-4 mr-2.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-slate-500 dark:text-slate-400">
                Duración: <span class="font-bold text-slate-700 dark:text-slate-200">{{ template.config.estimated_duration_days }} días</span>
              </span>
            </div>
            <div v-if="template.config.required_roles" class="flex items-center text-sm">
              <svg class="w-4 h-4 mr-2.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span class="text-slate-500 dark:text-slate-400">
                Roles: <span class="font-bold text-slate-700 dark:text-slate-200">{{ template.config.required_roles.join(', ') }}</span>
              </span>
            </div>
          </div>

          <!-- Estadísticas -->
          <div class="flex items-center justify-between text-xs font-medium uppercase tracking-wider mb-4 border-t border-white/5 pt-4">
            <div class="flex items-center text-slate-500">
              <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Por: {{ template.creator?.name || 'Sistema' }}
            </div>
            <div class="text-slate-500">
              <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              {{ getFlowCount(template.id) }} flujos
            </div>
          </div>

          <!-- Botón de acción -->
          <button
            @click="useTemplate(template)"
            class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all font-bold shadow-lg shadow-blue-900/20 flex items-center justify-center hover:-translate-y-0.5"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Usar Plantilla
          </button>
        </div>

        <!-- Card vacía -->
        <div v-if="templates.length === 0" class="col-span-full flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-800/30 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
          <div class="bg-slate-100 dark:bg-slate-800 p-4 rounded-full mb-4">
             <svg class="w-12 h-12 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
             </svg>
          </div>
          <p class="text-slate-800 dark:text-white text-xl font-bold mb-2">No hay plantillas disponibles</p>
          <p class="text-slate-500 dark:text-slate-400">Crea tu primera plantilla para estandarizar tus procesos</p>
        </div>
      </div>
    </main>

    <!-- Modal de Nueva Plantilla -->
    <TemplateModal
      :isOpen="isTemplateModalOpen"
      @close="isTemplateModalOpen = false"
      @saved="handleTemplateSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { templatesAPI, flowsAPI } from '@/services/api'
import Navbar from '@/components/AppNavbar.vue'
import TemplateModal from '@/components/TemplateModal.vue'

const router = useRouter()
const templates = ref([])
const flows = ref([])
const isTemplateModalOpen = ref(false)



const handleTemplateSaved = async () => {
  await loadData()
}

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

const deleteTemplate = async (template) => {
  if (!confirm(`¿Estás seguro de eliminar la plantilla "${template.name}"?`)) return
  
  try {
    await templatesAPI.delete(template.id)
    await loadData()
  } catch (error) {
    console.error('Error eliminando plantilla:', error)
    alert('Error al eliminar la plantilla')
  }
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