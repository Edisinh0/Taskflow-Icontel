<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors">
    <Navbar />
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center">
                <Users class="w-8 h-8 mr-3 text-blue-500" />
                Clientes y Empresas
            </h2>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Gestiona tu cartera de clientes y visualiza su historial operativo</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 font-bold transition-all shadow-lg shadow-blue-500/20 dark:shadow-blue-900/20 hover:scale-105 active:scale-95"
        >
          <Plus class="w-5 h-5 mr-2" />
          Nuevo Cliente
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar de Filtros -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-xl p-6 border border-slate-200 dark:border-white/5 sticky top-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center">
              <Filter class="w-5 h-5 mr-2 text-blue-500" />
              Filtros
            </h3>
            
            <div class="space-y-6">
              <!-- Búsqueda -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Búsqueda
                </label>
                <div class="relative">
                  <Search class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                  <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Nombre, email, ID..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm"
                  />
                </div>
              </div>

              <!-- Estado -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Estado
                </label>
                <select
                  v-model="statusFilter"
                  class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm outline-none"
                >
                  <option value="all">Todos los estados</option>
                  <option value="active">Activo</option>
                  <option v-for="stat in uniqueStatuses" :key="stat" :value="stat">
                    {{ stat }}
                  </option>
                </select>
              </div>

              <!-- Industria -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Industria
                </label>
                <select
                  v-model="industryFilter"
                  class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm outline-none"
                >
                  <option value="all">Todas las industrias</option>
                  <option v-for="ind in uniqueIndustries" :key="ind" :value="ind">
                    {{ ind }}
                  </option>
                </select>
              </div>

              <!-- Tipo de Cuenta -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Tipo de Cuenta
                </label>
                <select
                  v-model="typeFilter"
                  class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm outline-none"
                >
                  <option value="all">Todos los tipos</option>
                  <option v-for="type in uniqueTypes" :key="type" :value="type">
                    {{ type }}
                  </option>
                </select>
              </div>

              <div class="pt-4 border-t border-slate-200 dark:border-white/5">
                <button
                  @click="clearFilters"
                  class="w-full px-4 py-2.5 bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-300 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white font-bold transition-all text-sm border border-slate-200 dark:border-white/10"
                >
                  Limpiar Todo
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Contenido Principal -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Estadísticas -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 dark:bg-blue-500/10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:bg-blue-500/10 dark:group-hover:bg-blue-500/20"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Total Clientes</p>
              <p class="text-4xl font-black text-slate-800 dark:text-white mt-2">{{ stats.total }}</p>
              <div class="mt-4 flex items-center text-xs font-bold text-blue-500">
                  <span class="p-1 bg-blue-500/10 rounded-lg mr-2"><Building2 :size="12" /></span>
                  {{ stats.industries }} Industrias
              </div>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
               <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:bg-emerald-500/10 dark:group-hover:bg-emerald-500/20"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Activos</p>
              <p class="text-4xl font-black text-emerald-500 dark:text-emerald-400 mt-2">{{ stats.active }}</p>
              <div class="mt-4 flex items-center text-xs font-bold text-emerald-500">
                  <span class="p-1 bg-emerald-500/10 rounded-lg mr-2"><CheckCircle2 :size="12" /></span>
                  {{ Math.round((stats.active / (stats.total || 1)) * 100) }}% del total
              </div>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
               <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 dark:bg-purple-500/10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:bg-purple-500/10 dark:group-hover:bg-purple-500/20"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Por Tipo</p>
              <div class="mt-2 space-y-1">
                  <div v-for="(count, type) in stats.byType" :key="type" class="flex justify-between items-center">
                      <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ type }}</span>
                      <span class="text-sm font-black text-slate-700 dark:text-white">{{ count }}</span>
                  </div>
              </div>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
               <div class="absolute top-0 right-0 w-20 h-20 bg-amber-500/5 dark:bg-amber-500/10 rounded-bl-full -mr-4 -mt-4 transition-all group-hover:bg-amber-500/10 dark:group-hover:bg-amber-500/20"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Industrias</p>
              <p class="text-4xl font-black text-amber-500 dark:text-amber-400 mt-2">{{ stats.industries }}</p>
                <div class="mt-4 flex items-center text-xs font-bold text-amber-500">
                  <span class="p-1 bg-amber-500/10 rounded-lg mr-2"><LayoutGrid :size="12" /></span>
                  Diversificado
              </div>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group text-center flex flex-col items-center justify-center border-dashed">
               <button 
                @click="openCreateModal"
                class="w-16 h-16 rounded-full bg-blue-500/10 text-blue-500 flex items-center justify-center hover:scale-110 active:scale-95 transition-all mb-2"
               >
                 <Plus :size="32" class="font-black" />
               </button>
               <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Añadir Nuevo</span>
            </div>
          </div>

          <!-- Tabla de Resultados -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-xl border border-slate-200 dark:border-white/5 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-200 dark:border-white/5 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
              <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tighter">
                Directorio <span class="text-xs font-bold text-slate-400 bg-slate-200 dark:bg-white/10 px-2 py-1 rounded-lg ml-3">{{ filteredClients.length }}</span>
              </h3>
              <div class="flex gap-2">
                  <button @click="fetchClients" class="p-2 text-slate-400 hover:text-blue-500 transition-all active:rotate-180">
                      <RefreshCw :size="18" :class="{'animate-spin': loading}" />
                  </button>
              </div>
            </div>

            <div v-if="loading" class="p-20 text-center">
              <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-500/20 border-b-blue-500"></div>
              <p class="text-slate-500 dark:text-slate-400 mt-4 font-black uppercase tracking-widest text-xs">Sincronizando clientes...</p>
            </div>

            <div v-else-if="filteredClients.length === 0" class="p-20 text-center">
              <div class="bg-slate-100 dark:bg-slate-900/50 p-6 rounded-full inline-block mb-6">
                 <Users class="w-16 h-16 text-slate-300 dark:text-slate-700" />
              </div>
              <p class="text-slate-800 dark:text-white text-2xl font-black tracking-tight">No se encontraron clientes</p>
              <p class="text-slate-500 mt-2">Intenta ajustar los criterios de filtrado</p>
              <button @click="clearFilters" class="mt-6 text-sm font-bold text-blue-500 hover:underline">Limpiar todos los filtros</button>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full text-left">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-900/50">
                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Industria</th>
                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tipo</th>
                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Contacto</th>
                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                    <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                  <tr 
                    v-for="client in filteredClients" 
                    :key="client.id" 
                    class="group hover:bg-slate-50 dark:hover:bg-white/5 transition-all"
                  >
                    <td class="px-8 py-5">
                      <div class="flex items-center space-x-4">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 border border-slate-200 dark:border-white/5 flex items-center justify-center text-blue-600 dark:text-blue-400 font-black text-xs shadow-sm group-hover:scale-110 transition-transform">
                          {{ getInitials(client.name) }}
                        </div>
                        <div>
                          <p 
                            @click="goToClientDetail(client.id)"
                            class="font-black text-slate-800 dark:text-white leading-none mb-1 group-hover:text-blue-500 transition-colors cursor-pointer"
                          >
                            {{ client.name }}
                          </p>
                          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">ID #{{ client.id }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-8 py-5">
                      <div class="flex items-center text-slate-600 dark:text-slate-300">
                          <span class="p-1.5 bg-slate-100 dark:bg-white/5 rounded-lg mr-2 text-slate-400"><Briefcase :size="12" /></span>
                          <span class="text-sm font-bold">{{ client.industry || 'General' }}</span>
                      </div>
                    </td>
                    <td class="px-8 py-5">
                      <div v-if="client.account_type" class="flex items-center">
                          <span class="px-2 py-0.5 bg-purple-500/10 text-purple-600 dark:text-purple-400 text-[10px] font-black uppercase tracking-widest rounded-md border border-purple-500/20">
                            {{ client.account_type }}
                          </span>
                      </div>
                      <span v-else class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">-</span>
                    </td>
                    <td class="px-8 py-5">
                      <div class="space-y-1">
                          <p v-if="client.email" class="text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center gap-2">
                              <Mail :size="12" class="text-slate-400" />
                              {{ client.email }}
                          </p>
                          <p v-if="client.phone" class="text-xs font-medium text-slate-400 flex items-center gap-2">
                              <Phone :size="12" class="text-slate-400" />
                              {{ client.phone }}
                          </p>
                      </div>
                    </td>
                    <td class="px-8 py-5">
                      <span 
                        :class="getStatusClass(client.status)"
                        class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border"
                      >
                        {{ formatStatus(client.status) }}
                      </span>
                    </td>
                    <td class="px-8 py-5">
                      <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-all translate-x-4 group-hover:translate-x-0">
                        <button
                          @click="goToClientDetail(client.id)"
                          class="p-2 text-blue-500 hover:bg-blue-500/10 rounded-xl transition-all active:scale-90 border border-transparent hover:border-blue-500/20"
                          title="Ficha 360"
                        >
                          <Eye :size="18" />
                        </button>
                        <button
                          @click="openEditModal(client)"
                          class="p-2 text-amber-500 hover:bg-amber-500/10 rounded-xl transition-all active:scale-90 border border-transparent hover:border-amber-500/20"
                          title="Editar"
                        >
                          <Edit2 :size="18" />
                        </button>
                        <button
                          @click="confirmDelete(client)"
                          class="p-2 text-rose-500 hover:bg-rose-500/10 rounded-xl transition-all active:scale-90 border border-transparent hover:border-rose-500/20"
                          title="Eliminar"
                        >
                          <Trash2 :size="18" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Formulario -->
    <ClientModal
      :is-open="showModal"
      :client="selectedClient"
      :loading="processing"
      @close="closeModal"
      @save="handleSave"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Navbar from '@/components/AppNavbar.vue'
import ClientModal from '@/components/ClientModal.vue'
import ClientService from '@/services/ClientService'
import { 
    Users, Plus, Search, Mail, Phone, MapPin, Database, Edit2, 
    Trash2, Loader2, Eye, Filter, Briefcase, RefreshCw,
    Building2, CheckCircle2, LayoutGrid
} from 'lucide-vue-next'
import Swal from 'sweetalert2'

const router = useRouter()

const clients = ref([])
const loading = ref(true)
const processing = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')
const industryFilter = ref('all')
const typeFilter = ref('all')

const showModal = ref(false)
const selectedClient = ref(null)

// Estadísticas computadas
const stats = computed(() => {
    const byType = {}
    clients.value.forEach(c => {
        const type = c.account_type || 'Manual'
        byType[type] = (byType[type] || 0) + 1
    })

    return {
        total: clients.value.length,
        active: clients.value.filter(c => c.status === 'active').length,
        inactive: clients.value.filter(c => c.status === 'inactive').length,
        industries: new Set(clients.value.map(c => c.industry).filter(Boolean)).size,
        byType
    }
})

// Lista única de industrias para el filtro
const uniqueIndustries = computed(() => {
    return [...new Set(clients.value.map(c => c.industry).filter(Boolean))].sort()
})

// Lista única de tipos para el filtro
const uniqueTypes = computed(() => {
    return [...new Set(clients.value.map(c => c.account_type).filter(Boolean))].sort()
})

// Lista única de estados para el filtro (excluyendo 'active')
const uniqueStatuses = computed(() => {
    return [...new Set(clients.value.map(c => c.status).filter(s => s && s !== 'active'))].sort()
})

// Computed para filtrar
const filteredClients = computed(() => {
  return clients.value.filter(client => {
    // Filtro texto
    const searchLower = searchQuery.value.toLowerCase()
    const matchSearch = 
      client.name.toLowerCase().includes(searchLower) ||
      (client.email && client.email.toLowerCase().includes(searchLower)) ||
      (client.sweetcrm_id && client.sweetcrm_id.toLowerCase().includes(searchLower))

    // Filtro estado
    const matchStatus = statusFilter.value === 'all' || client.status === statusFilter.value

    // Filtro industria
    const matchIndustry = industryFilter.value === 'all' || client.industry === industryFilter.value

    // Filtro tipo
    const matchType = typeFilter.value === 'all' || client.account_type === typeFilter.value

    return matchSearch && matchStatus && matchIndustry && matchType
  })
})

const fetchClients = async () => {
  loading.value = true
  try {
    const response = await ClientService.getAll()
    clients.value = response.data
  } catch (error) {
    console.error('Error fetching clients:', error)
    Swal.fire('Error', 'No se pudieron cargar los clientes', 'error')
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  selectedClient.value = null
  showModal.value = true
}

const openEditModal = (client) => {
  selectedClient.value = { ...client }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedClient.value = null
}

const handleSave = async (formData) => {
  processing.value = true
  try {
    if (selectedClient.value) {
      // Editar
      const response = await ClientService.update(selectedClient.value.id, formData)
      const index = clients.value.findIndex(c => c.id === response.data.id)
      if (index !== -1) {
        clients.value[index] = response.data
      }
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Cliente actualizado',
        showConfirmButton: false,
        timer: 3000
      })
    } else {
      // Crear
      const response = await ClientService.create(formData)
      clients.value.push(response.data)
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Cliente creado',
        showConfirmButton: false,
        timer: 3000
      })
    }
    closeModal()
  } catch (error) {
    console.error('Error saving client:', error)
    Swal.fire('Error', 'No se pudo guardar el cliente', 'error')
  } finally {
    processing.value = false
  }
}

const confirmDelete = (client) => {
  Swal.fire({
    title: '¿Estás seguro?',
    text: `Eliminarás al cliente "${client.name}". Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await ClientService.delete(client.id)
        clients.value = clients.value.filter(c => c.id !== client.id)
        Swal.fire('Eliminado', 'El cliente ha sido eliminado.', 'success')
      } catch (error) {
        console.error('Error deleting client:', error)
        Swal.fire('Error', 'No se pudo eliminar el cliente', 'error')
      }
    }
  })
}

const clearFilters = () => {
    searchQuery.value = ''
    statusFilter.value = 'all'
    industryFilter.value = 'all'
    typeFilter.value = 'all'
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getStatusClass = (status) => {
    if (status === 'active') return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
    if (['Baja', 'Baja Forzada', 'Suspendido'].includes(status)) return 'bg-rose-500/10 text-rose-500 border-rose-500/20'
    if (['Extrajudicial', 'Cobranza Comercial', 'Acuerdo Cobranza'].includes(status)) return 'bg-amber-500/10 text-amber-500 border-amber-500/20'
    if (status === 'Prospecto') return 'bg-blue-500/10 text-blue-500 border-blue-500/20'
    return 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}

const formatStatus = (status) => {
    if (status === 'active') return 'Activo'
    return status
}

const goToClientDetail = (clientId) => {
  router.push({ name: 'client-detail', params: { id: clientId } })
}

onMounted(() => {
  fetchClients()
})
</script>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
  width: 4px;
}
.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dark .scrollbar-thin::-webkit-scrollbar-thumb {
  background: #334155;
}
</style>

