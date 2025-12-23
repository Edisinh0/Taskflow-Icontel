<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-12">
    <AppNavbar />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar de Filtros -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 sticky top-24">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
              <Filter :size="20" class="text-blue-500" />
              Filtros
            </h3>

            <div class="space-y-6">
              <!-- Búsqueda -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Buscar
                </label>
                <div class="relative">
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                  <input 
                    v-model="searchQuery"
                    type="text"
                    placeholder="Número o asunto..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm outline-none"
                  >
                </div>
              </div>

              <!-- Estado -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Estado
                </label>
                <select
                  v-model="statusFilter"
                  class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm outline-none font-medium"
                >
                  <option value="all">Todos los estados</option>
                  <option v-for="status in uniqueStatuses" :key="status" :value="status">{{ status }}</option>
                </select>
              </div>

              <!-- Prioridad -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Prioridad
                </label>
                <select
                  v-model="priorityFilter"
                  class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm outline-none font-medium"
                >
                  <option value="all">Todas las prioridades</option>
                  <option value="Alta">Alta</option>
                  <option value="Media">Media</option>
                  <option value="Baja">Baja</option>
                </select>
              </div>

              <!-- Área -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Área
                </label>
                <select
                  v-model="areaFilter"
                  class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm outline-none font-medium"
                >
                  <option value="all">Todas las áreas</option>
                  <option value="Operaciones">Operaciones</option>
                  <option value="Soporte">Soporte</option>
                  <option value="Atención al Cliente">Atención al Cliente</option>
                  <option value="Ventas">Ventas</option>
                </select>
              </div>

              <div class="pt-4 border-t border-slate-200 dark:border-white/5">
                <button
                  @click="clearFilters"
                  class="w-full px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-300 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white font-bold transition-all text-sm border border-slate-200 dark:border-white/10"
                >
                  Limpiar Filtros
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Contenido Principal -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Estadísticas -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 dark:bg-blue-500/10 rounded-bl-full -mr-4 -mt-4"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Total Casos</p>
              <p class="text-4xl font-black text-slate-800 dark:text-white mt-2">{{ stats.total }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-bl-full -mr-4 -mt-4"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Nuevos/Abiertos</p>
              <p class="text-4xl font-black text-emerald-500 dark:text-emerald-400 mt-2">{{ stats.open }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-20 h-20 bg-amber-500/5 dark:bg-amber-500/10 rounded-bl-full -mr-4 -mt-4"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Tareas de CRM</p>
              <p class="text-4xl font-black text-amber-500 dark:text-amber-400 mt-2">{{ stats.totalTasks }}</p>
            </div>
          </div>

          <!-- Tabla de Casos -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-slate-100 dark:border-white/5">
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Número</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Asunto / Cliente</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Área</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 text-center">Tareas</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Estado</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Prioridad</th>
                    <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                  <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                    <td colspan="6" class="px-8 py-5 bg-slate-50/50 dark:bg-white/5"></td>
                  </tr>
                  <tr v-else-if="filteredCases.length === 0">
                    <td colspan="6" class="px-8 py-12 text-center">
                      <div class="flex flex-col items-center">
                        <Inbox :size="48" class="text-slate-300 dark:text-white/10 mb-4" />
                        <p class="text-slate-500 dark:text-slate-400 font-bold">No se encontraron casos</p>
                        <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Intenta con otros filtros o términos de búsqueda</p>
                      </div>
                    </td>
                  </tr>
                  <tr 
                    v-for="crmCase in filteredCases" 
                    :key="crmCase.id"
                    class="group hover:bg-slate-50 dark:hover:bg-white/5 transition-all cursor-pointer"
                    @click="showCaseDetail(crmCase)"
                  >
                    <td class="px-8 py-5">
                      <span class="text-sm font-black text-blue-600 dark:text-blue-400">#{{ crmCase.case_number }}</span>
                    </td>
                    <td class="px-8 py-5">
                      <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-1">
                          {{ crmCase.subject }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-1">
                          <Building2 :size="12" />
                          {{ crmCase.client?.name }}
                        </span>
                      </div>
                    </td>
                    <td class="px-8 py-5">
                      <div class="flex flex-col">
                        <span 
                          v-if="crmCase.assigned_user?.department"
                          :class="getAreaClass(crmCase.assigned_user.department)"
                          class="px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full border shadow-sm w-fit mb-1"
                        >
                          {{ crmCase.assigned_user.department }}
                        </span>
                        <span v-else class="text-[10px] text-slate-400 dark:text-slate-500 italic mb-1">Sin área</span>
                        <span v-if="crmCase.assigned_user" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                          <User :size="10" /> {{ crmCase.assigned_user.name }}
                        </span>
                      </div>
                    </td>
                    <td class="px-8 py-5 text-center">
                      <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-black rounded-lg border border-slate-200 dark:border-white/10">
                        {{ crmCase.tasks_count || 0 }}
                      </span>
                    </td>
                    <td class="px-8 py-5">
                      <span 
                        :class="getStatusClass(crmCase.status)"
                        class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border shadow-sm"
                      >
                        {{ crmCase.status }}
                      </span>
                    </td>
                    <td class="px-8 py-5">
                      <span 
                        :class="getPriorityClass(crmCase.priority)"
                        class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border shadow-sm"
                      >
                        {{ crmCase.priority }}
                      </span>
                    </td>
                    <td class="px-8 py-5 whitespace-nowrap">
                      <button 
                        @click.stop="showCaseDetail(crmCase)"
                        class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-xl transition-all"
                      >
                        <ExternalLink :size="18" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Paginación -->
            <div v-if="pagination.total > pagination.per_page" class="px-8 py-4 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 flex justify-between items-center">
              <p class="text-xs font-bold text-slate-500">Mostrando {{ filteredCases.length }} de {{ pagination.total }} casos</p>
              <div class="flex gap-2">
                <button 
                  @click="changePage(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                  class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 disabled:opacity-50 transition-all"
                >
                  Anterior
                </button>
                <button 
                  @click="changePage(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.last_page"
                  class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 disabled:opacity-50 transition-all"
                >
                  Siguiente
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal de Detalle de Caso -->
    <Transition name="modal">
        <div v-if="selectedCase" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-8 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-white/5 w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col relative scale-100 transition-all duration-300">
                
                <!-- Header del Modal -->
                <div class="p-8 border-b border-slate-100 dark:border-white/5 relative bg-slate-50/50 dark:bg-white/5">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm font-black text-blue-600 dark:text-blue-400">Caso #{{ selectedCase.case_number }}</span>
                                <span :class="getStatusClass(selectedCase.status)" class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-full border">{{ selectedCase.status }}</span>
                            </div>
                            <h2 class="text-2xl font-black text-slate-800 dark:text-white leading-tight">
                                {{ selectedCase.subject }}
                            </h2>
                            <p class="text-slate-500 dark:text-slate-400 font-bold mt-2 flex items-center gap-2">
                                <Building2 :size="16" />
                                {{ selectedCase.client?.name }}
                            </p>
                        </div>
                        <button @click="selectedCase = null" class="p-3 bg-white dark:bg-slate-700 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-2xl shadow-sm border border-slate-100 dark:border-white/10 transition-all">
                            <X :size="20" />
                        </button>
                    </div>
                </div>

                <!-- Cuerpo del Modal -->
                <div class="flex-1 overflow-y-auto p-8 space-y-8">
                    <!-- Descripción -->
                    <div v-if="selectedCase.description" class="bg-slate-50 dark:bg-white/5 rounded-3xl p-6 border border-slate-100 dark:border-white/5">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Descripción</h4>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-wrap">{{ selectedCase.description }}</p>
                    </div>

                    <!-- Tareas del Caso -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <ListTodo :size="14" class="text-blue-500" />
                            Tareas Relacionadas ({{ caseTasks.length }})
                        </h4>

                        <div v-if="loadingTasks" class="space-y-3">
                            <div v-for="i in 3" :key="i" class="h-16 bg-slate-50 dark:bg-white/5 rounded-2xl animate-pulse"></div>
                        </div>

                        <div v-else-if="caseTasks.length === 0" class="text-center py-8 bg-slate-50 dark:bg-white/5 rounded-3xl border border-dashed border-slate-200 dark:border-white/10">
                            <p class="text-slate-400 dark:text-slate-500 font-bold text-sm">No hay tareas asociadas a este caso</p>
                        </div>

                        <div v-else class="grid grid-cols-1 gap-3">
                            <div 
                                v-for="task in caseTasks" 
                                :key="task.id"
                                class="bg-white dark:bg-slate-700/50 p-4 rounded-2xl border border-slate-100 dark:border-white/10 flex items-center justify-between hover:shadow-md transition-all group"
                            >
                                <div class="flex items-center gap-4">
                                    <div :class="taskStatusClass(task.status)" class="w-2 h-2 rounded-full shadow-lg"></div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-blue-500 transition-colors">{{ task.title }}</p>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span v-if="task.assignee" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                                <User :size="10" /> {{ task.assignee.name }}
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ formatTaskStatus(task.status) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                  <span 
                                        :class="taskPriorityBadge(task.priority)"
                                        class="px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-md border"
                                    >
                                        {{ task.priority }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="p-8 border-t border-slate-100 dark:border-white/5 flex justify-end gap-3 bg-slate-50/50 dark:bg-white/5">
                    <button 
                        @click="selectedCase = null"
                        class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-2xl border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all text-sm"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import api from '@/services/api'
import AppNavbar from '@/components/AppNavbar.vue'
import { 
  Briefcase, 
  Search, 
  Filter, 
  Building2, 
  Inbox, 
  ExternalLink, 
  X, 
  ListTodo, 
  User,
  CheckCircle2,
  Clock,
  AlertCircle
} from 'lucide-vue-next'

const cases = ref([])
const stats = ref({ total: 0, open: 0, totalTasks: 0 })
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('all')
const priorityFilter = ref('all')
const areaFilter = ref('all')
const selectedCase = ref(null)
const caseTasks = ref([])
const loadingTasks = ref(false)

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0
})

// Obtener datos iniciales
const fetchData = async () => {
    loading.value = true
    try {
        const params = {
            page: pagination.value.current_page,
            search: searchQuery.value,
            status: statusFilter.value,
            priority: priorityFilter.value,
            area: areaFilter.value,
            per_page: 20
        }
        
        const [casesRes, statsRes] = await Promise.all([
            api.get('/cases', { params }),
            api.get('/cases/stats')
        ])
        
        cases.value = casesRes.data.data
        pagination.value = {
            current_page: casesRes.data.current_page,
            last_page: casesRes.data.last_page,
            per_page: casesRes.data.per_page,
            total: casesRes.data.total
        }
        
        // Procesar stats
        const s = statsRes.data
        stats.value = {
            total: s.total,
            open: s.by_status.filter(st => ['Nuevo', 'Asignado'].includes(st.status)).reduce((acc, curr) => acc + curr.count, 0),
            totalTasks: 0 // Podríamos traerlo del backend después
        }
    } catch (error) {
        console.error('Error fetching cases:', error)
    } finally {
        loading.value = false
    }
}

// Filtros reactivos
watch([searchQuery, statusFilter, priorityFilter, areaFilter], () => {
    pagination.value.current_page = 1
    fetchData()
})

const changePage = (page) => {
    pagination.value.current_page = page
    fetchData()
}

const clearFilters = () => {
    searchQuery.value = ''
    statusFilter.value = 'all'
    priorityFilter.value = 'all'
    areaFilter.value = 'all'
}

// Detalle de caso
const showCaseDetail = async (crmCase) => {
    selectedCase.value = crmCase
    loadingTasks.value = true
    caseTasks.value = []
    
    try {
        const res = await api.get(`/cases/${crmCase.id}`)
        caseTasks.value = res.data.tasks || []
    } catch (error) {
        console.error('Error fetching case tasks:', error)
    } finally {
        loadingTasks.value = false
    }
}

const uniqueStatuses = [
    'Nuevo', 'Asignado', 'Cerrado', 'Pendiente Datos', 'Rechazado', 'Duplicado'
]

const filteredCases = computed(() => cases.value)

// Clases de estilo
const getStatusClass = (status) => {
    if (status === 'Nuevo') return 'bg-blue-500/10 text-blue-500 border-blue-500/20'
    if (status === 'Asignado') return 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20'
    if (status === 'Cerrado') return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
    if (status === 'Rechazado') return 'bg-rose-500/10 text-rose-500 border-rose-500/20'
    return 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}

const getPriorityClass = (priority) => {
    if (priority === 'Alta') return 'bg-rose-500/10 text-rose-500 border-rose-500/20 font-black'
    if (priority === 'Media') return 'bg-amber-500/10 text-amber-500 border-amber-500/20 font-bold'
    return 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}

const getAreaClass = (area) => {
    if (area === 'Operaciones') return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
    if (area === 'Soporte') return 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20'
    if (area === 'Atención al Cliente') return 'bg-pink-500/10 text-pink-600 dark:text-pink-400 border-pink-500/20'
    if (area === 'Ventas') return 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-500/20'
    return 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}

const taskStatusClass = (status) => {
    if (status === 'completed') return 'bg-emerald-500'
    if (status === 'in_progress') return 'bg-blue-500'
    if (status === 'blocked') return 'bg-rose-500'
    return 'bg-slate-300 dark:bg-slate-600'
}

const formatTaskStatus = (status) => {
    const map = {
        'pending': 'Pendiente',
        'in_progress': 'En progreso',
        'completed': 'Completada',
        'blocked': 'Bloqueada',
        'cancelled': 'Cancelada'
    }
    return map[status] || status
}

const taskPriorityBadge = (priority) => {
    if (priority === 'high') return 'bg-rose-500/10 text-rose-500 border-rose-500/20'
    if (priority === 'medium') return 'bg-amber-500/10 text-amber-500 border-amber-500/20'
    return 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}

onMounted(() => {
    fetchData()
})
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

.modal-enter-active > div,
.modal-leave-active > div {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-enter-from > div,
.modal-leave-to > div {
  transform: scale(0.9) translateY(20px);
}
</style>
