<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors font-sans selection:bg-blue-500/30 selection:text-blue-200">
    <Navbar />

    <div v-if="loading" class="flex justify-center items-center h-screen">
       <div class="flex flex-col items-center">
          <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mb-4"></div>
          <p class="text-slate-400 text-lg animate-pulse">Cargando ficha del cliente...</p>
       </div>
    </div>

    <main v-else-if="client" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header / Breadcrumb -->
      <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button @click="router.push('/clients')" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-500/10 rounded-xl transition-all">
                <ChevronLeft :size="20" />
            </button>
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center">
                    Ficha del Cliente
                </h2>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-bold uppercase tracking-widest">{{ client.name }}</p>
            </div>
        </div>
        
        <div class="flex gap-3">
            <button @click="editClient" class="px-4 py-2 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/10 rounded-xl font-bold text-sm hover:border-blue-500/50 hover:text-blue-500 transition-all flex items-center gap-2">
                <Pencil :size="16" /> Editar
            </button>
            <button @click="confirmDelete" class="px-4 py-2 bg-rose-500/10 text-rose-500 border border-rose-500/20 rounded-xl font-bold text-sm hover:bg-rose-500 hover:text-white transition-all flex items-center gap-2">
                <Trash2 :size="16" /> Eliminar
            </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Izquierda: Información del Cliente -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl rounded-3xl shadow-sm border border-slate-200 dark:border-white/5 p-6 sticky top-4">
            <!-- Profile Info -->
            <div class="flex flex-col items-center text-center mb-8">
                <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white text-4xl font-black shadow-2xl shadow-blue-500/30 mb-4 ring-4 ring-white/10">
                  {{ client.name.charAt(0) }}
                </div>
                <h3 class="text-xl font-black text-slate-800 dark:text-white leading-tight mb-1">{{ client.name }}</h3>
                <span :class="client.status === 'active' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20'" class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border mb-4">
                  {{ client.status === 'active' ? 'Activo' : 'Inactivo' }}
                </span>
                
                <div v-if="client.sweetcrm_id" class="px-3 py-1 bg-purple-500/10 text-purple-500 border border-purple-500/20 rounded-lg text-[10px] font-black uppercase tracking-tighter">
                    CRM: {{ client.sweetcrm_id }}
                </div>
            </div>

            <!-- Contact Details -->
            <div class="space-y-4 border-t border-slate-100 dark:border-white/5 pt-6">
                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Información de Contacto</h4>
                
                <div class="flex items-start gap-3 group">
                    <div class="p-2 bg-slate-100 dark:bg-white/5 rounded-lg text-slate-400 group-hover:text-blue-500 transition-colors">
                        <Briefcase :size="16" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter">Industria</p>
                        <p class="text-sm font-black text-slate-700 dark:text-slate-200 truncate">{{ client.industry?.name || client.industry || 'General' }}</p>
                    </div>
                </div>

                <div v-if="client.account_type" class="flex items-start gap-3 group">
                    <div class="p-2 bg-slate-100 dark:bg-white/5 rounded-lg text-slate-400 group-hover:text-blue-500 transition-colors">
                        <Tag :size="16" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter">Tipo de Cuenta</p>
                        <p class="text-sm font-black text-slate-700 dark:text-slate-200 truncate">{{ client.account_type }}</p>
                    </div>
                </div>

                <div v-if="client.email" class="flex items-start gap-3 group">
                    <div class="p-2 bg-slate-100 dark:bg-white/5 rounded-lg text-slate-400 group-hover:text-blue-500 transition-colors">
                        <Mail :size="16" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter">Email</p>
                        <p class="text-sm font-black text-slate-700 dark:text-slate-200 truncate">{{ client.email }}</p>
                    </div>
                </div>

                <div v-if="client.phone" class="flex items-start gap-3 group">
                    <div class="p-2 bg-slate-100 dark:bg-white/5 rounded-lg text-slate-400 group-hover:text-blue-500 transition-colors">
                        <Phone :size="16" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter">Teléfono</p>
                        <p class="text-sm font-black text-slate-700 dark:text-slate-200">{{ client.phone }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-8 pt-6 border-t border-slate-100 dark:border-white/5">
                <div class="bg-blue-600 rounded-2xl p-4 text-white shadow-lg shadow-blue-500/20">
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-70 mb-1">Progreso General</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black">{{ stats.avg_progress || 0 }}%</span>
                        <span class="text-xs font-bold opacity-70">completado</span>
                    </div>
                    <div class="mt-3 w-full bg-white/20 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-white h-full rounded-full transition-all duration-1000 shadow-[0_0_8px_rgba(255,255,255,0.4)]" :style="`width: ${stats.avg_progress}%`"></div>
                    </div>
                </div>
            </div>
          </div>
        </div>

        <!-- Contenido Principal: Flujos, Tareas, etc. -->
        <div class="lg:col-span-3 space-y-8">
          <!-- Estadísticas Superiores -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl p-6 border border-slate-200 dark:border-white/5 shadow-sm group">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-blue-500 transition-colors">Total Flujos</p>
                  <p class="text-3xl font-black text-slate-900 dark:text-white mt-2">{{ stats.total_flows }}</p>
              </div>
              <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl p-6 border border-slate-200 dark:border-white/5 shadow-sm group">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-blue-500 transition-colors">Flujos Activos</p>
                  <p class="text-3xl font-black text-blue-500 mt-2">{{ stats.active_flows }}</p>
              </div>
              <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl p-6 border border-slate-200 dark:border-white/5 shadow-sm group">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-emerald-500 transition-colors">Completados</p>
                  <p class="text-3xl font-black text-emerald-500 mt-2">{{ stats.completed_flows }}</p>
              </div>
              <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl p-6 border border-slate-200 dark:border-white/5 shadow-sm group">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-rose-500 transition-colors">Tareas Pendientes</p>
                  <p class="text-3xl font-black text-rose-500 mt-2">{{ stats.pending_tasks }}</p>
              </div>
          </div>

          <!-- Listado de Flujos -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl rounded-3xl shadow-sm border border-slate-200 dark:border-white/5 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-slate-900/30 flex justify-between items-center">
              <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tighter flex items-center">
                <span class="p-2 bg-blue-500/10 rounded-xl mr-3 text-blue-500 flex items-center justify-center">
                  <Activity :size="20" />
                </span>
                Flujos de Trabajo
              </h3>
              <router-link to="/flows" class="text-sm font-bold text-blue-500 hover:underline">Ver todos</router-link>
            </div>

            <div v-if="client.flows && client.flows.length > 0" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="flow in client.flows" :key="flow.id" class="group bg-slate-50 dark:bg-slate-900/50 p-6 rounded-3xl border border-slate-200 dark:border-white/5 hover:border-blue-500/50 transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                  <div class="min-w-0">
                    <h4 class="text-lg font-black text-slate-800 dark:text-white group-hover:text-blue-500 transition-colors truncate">{{ flow.name }}</h4>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Sincronizado {{ formatDate(flow.created_at) }}</p>
                  </div>
                  <span :class="getStatusClass(flow.status)" class="px-2 py-0.5 text-[10px] font-black rounded-lg uppercase tracking-tight whitespace-nowrap border border-current/20">
                    {{ flow.status }}
                  </span>
                </div>
                
                <div class="space-y-4">
                  <div>
                    <div class="flex justify-between items-center mb-1.5">
                      <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ flow.progress }}% completado</span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700/50 rounded-full h-1.5 overflow-hidden">
                      <div class="h-full bg-blue-600 rounded-full transition-all duration-1000" :style="`width: ${flow.progress}%`"></div>
                    </div>
                  </div>
                  
                  <div class="flex justify-end">
                    <router-link :to="`/flows/${flow.id}`" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/20 active:scale-95">
                      Explorar <ChevronRight :size="14" />
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="p-20 text-center">
              <Activity :size="48" class="mx-auto text-slate-200 dark:text-slate-700 mb-4" />
              <p class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest text-xs">No hay flujos activos</p>
            </div>
          </div>

          <!-- Listado de Casos de CRM -->
          <div v-if="cases.length > 0" class="bg-white dark:bg-slate-800/80 backdrop-blur-xl rounded-3xl shadow-sm border border-slate-200 dark:border-white/5 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-slate-900/30 flex justify-between items-center">
              <h3 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-tighter flex items-center">
                <span class="p-2 bg-purple-500/10 rounded-xl mr-3 text-purple-500 flex items-center justify-center">
                  <Briefcase :size="20" />
                </span>
                Casos de CRM (SweetCRM)
              </h3>
              <router-link to="/cases" class="text-sm font-bold text-purple-500 hover:underline">Ver todos</router-link>
            </div>

            <div class="p-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div 
                  v-for="crmCase in cases.slice(0, 4)" 
                  :key="crmCase.id"
                  class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-white/5 flex items-center justify-between group"
                >
                  <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-white dark:bg-slate-800 rounded-xl flex items-center justify-center shadow-sm border border-slate-100 dark:border-white/10 group-hover:scale-110 transition-transform">
                       <span class="text-[10px] font-black text-slate-400">#{{ crmCase.case_number?.slice(-3) }}</span>
                    </div>
                    <div>
                      <p class="text-sm font-black text-slate-800 dark:text-white line-clamp-1">{{ crmCase.subject }}</p>
                      <div class="flex items-center gap-3 mt-1">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-1">
                           <Clock :size="10" /> {{ formatDate(crmCase.created_at) }}
                        </span>
                        <span class="px-2 py-0.5 bg-purple-500/10 text-purple-500 text-[8px] font-black uppercase rounded shadow-sm">{{ crmCase.status }}</span>
                      </div>
                    </div>
                  </div>
                  <router-link to="/cases" class="p-2 text-slate-400 hover:text-purple-500 transition-colors">
                    <ExternalLink :size="18" />
                  </router-link>
                </div>
              </div>
            </div>
          </div>

          <!-- Tareas y Documentos Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Tareas Activas -->
              <div class="bg-white dark:bg-slate-800/80 rounded-3xl shadow-sm border border-slate-200 dark:border-white/5 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-slate-900/30">
                  <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tighter flex items-center">
                    <span class="p-2 bg-emerald-500/10 rounded-xl mr-3 text-emerald-500 flex items-center justify-center">
                      <CheckSquare :size="18" />
                    </span>
                    Tareas Prioritarias
                  </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                      <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        <tr v-for="task in activeTasks" :key="task.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group">
                          <td class="px-8 py-4">
                            <p class="text-sm font-black text-slate-700 dark:text-slate-200 group-hover:text-blue-500 transition-colors mb-0.5">{{ task.title }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ getFlowName(task.flow_id) }}</p>
                          </td>
                          <td class="px-8 py-4 text-right">
                            <span class="px-2 py-0.5 text-[8px] font-black rounded-lg bg-blue-500/10 text-blue-500 uppercase border border-blue-500/20">
                              {{ task.status === 'in_progress' ? 'En Progreso' : 'Pendiente' }}
                            </span>
                          </td>
                        </tr>
                        <tr v-if="activeTasks.length === 0">
                          <td colspan="2" class="px-8 py-12 text-center text-slate-400 text-xs font-bold uppercase tracking-widest italic">Todo al día</td>
                        </tr>
                      </tbody>
                    </table>
                </div>
              </div>

              <!-- Documentos -->
              <div class="bg-white dark:bg-slate-800/80 rounded-3xl shadow-sm border border-slate-200 dark:border-white/5 overflow-hidden flex flex-col">
                <div class="px-8 py-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-slate-900/30 flex justify-between items-center">
                  <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tighter flex items-center">
                    <span class="p-2 bg-amber-500/10 rounded-xl mr-3 text-amber-500 flex items-center justify-center">
                      <FileText :size="18" />
                    </span>
                    Base Documental
                  </h3>
                  <button @click="openUploadModal" class="p-2 text-blue-500 hover:bg-blue-500/10 rounded-xl transition-all active:scale-90">
                    <Upload :size="18" />
                  </button>
                </div>
                <div class="p-6 space-y-3 flex-1 overflow-y-auto max-h-[300px] scrollbar-thin">
                  <div v-for="doc in client.attachments" :key="doc.id" class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/50 dark:border-white/5 group hover:border-amber-500/30 transition-all">
                    <div class="p-2 bg-amber-500/10 text-amber-500 rounded-xl">
                      <FileText :size="16" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-black text-slate-700 dark:text-slate-200 truncate">{{ doc.name }}</p>
                      <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ doc.category }}</p>
                    </div>
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all">
                       <a :href="getFileUrl(doc.file_path)" target="_blank" class="p-1.5 text-slate-400 hover:text-blue-500 transition-colors"><Download :size="14" /></a>
                       <button @click="deleteAttachment(doc.id)" class="p-1.5 text-slate-400 hover:text-rose-500 transition-colors"><Trash2 :size="14" /></button>
                    </div>
                  </div>
                  <div v-if="!client.attachments || client.attachments.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 py-10">
                    <FileText :size="32" class="opacity-20 mb-2" />
                    <p class="text-[10px] font-black uppercase tracking-widest">Sin archivos</p>
                  </div>
                </div>
              </div>
          </div>

          <!-- Contactos (Full Width) -->
          <div class="bg-white dark:bg-slate-800/80 rounded-3xl shadow-sm border border-slate-200 dark:border-white/5 overflow-hidden">
             <div class="px-8 py-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-slate-900/30 flex justify-between items-center">
              <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tighter flex items-center">
                <span class="p-2 bg-purple-500/10 rounded-xl mr-3 text-purple-500 flex items-center justify-center">
                  <Users :size="18" />
                </span>
                Directorio de Contactos
              </h3>
              <button @click="openContactModal()" class="px-4 py-2 bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all">
                Añadir Contacto
              </button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
               <div v-for="contact in client.contacts" :key="contact.id" class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-white/5 hover:border-purple-500/30 transition-all relative group shadow-sm">
                  <div v-if="contact.is_primary" class="absolute top-5 right-5 text-amber-500" title="Contacto Principal">
                    <Star :size="14" fill="currentColor" />
                  </div>
                  
                  <div class="flex items-center gap-3 mb-4">
                      <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center font-black text-sm">
                          {{ contact.name.charAt(0) }}
                      </div>
                      <div class="min-w-0">
                          <p class="font-black text-slate-800 dark:text-white text-sm truncate leading-none mb-1">{{ contact.name }}</p>
                          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest truncate">{{ contact.role || 'Sin cargo' }}</p>
                      </div>
                  </div>
                  
                  <div class="space-y-2 mb-4">
                      <p v-if="contact.email" class="text-[11px] font-bold text-slate-500 flex items-center gap-2 truncate">
                        <Mail :size="12" class="opacity-50" /> {{ contact.email }}
                      </p>
                      <p v-if="contact.phone" class="text-[11px] font-bold text-slate-500 flex items-center gap-2">
                        <Phone :size="12" class="opacity-50" /> {{ contact.phone }}
                      </p>
                  </div>

                  <div class="flex gap-2">
                      <button @click="openContactModal(contact)" class="flex-1 py-2 bg-white dark:bg-slate-800 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-blue-500 hover:border-blue-500/50 rounded-xl border border-slate-200 dark:border-white/5 transition-all">
                          Editar
                      </button>
                  </div>
               </div>
               <div v-if="!client.contacts || client.contacts.length === 0" class="col-span-1 md:col-span-3 py-10 text-center text-slate-400">
                   <p class="text-[10px] font-black uppercase tracking-widest italic">No hay contactos registrados</p>
               </div>
            </div>
          </div>
        </div>
      </div>
    </main>


    <!-- Modales -->
    <ClientContactModal
      :is-open="showContactModal"
      :contact="editingContact"
      :loading="processing"
      @close="closeContactModal"
      @save="handleSaveContact"
    />

    <ClientAttachmentModal
      :is-open="showAttachmentModal"
      :loading="processing"
      @close="closeAttachmentModal"
      @save="handleSaveAttachment"
    />

    <ClientModal
      :is-open="showEditModal"
      :client="client"
      :loading="processing"
      @close="showEditModal = false"
      @save="handleUpdateClient"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ClientService from '@/services/ClientService'
import Navbar from '@/components/AppNavbar.vue'
import ClientContactModal from '@/components/ClientContactModal.vue'
import ClientAttachmentModal from '@/components/ClientAttachmentModal.vue'
import ClientModal from '@/components/ClientModal.vue'
import api from '@/services/api'
import Swal from 'sweetalert2'
import { 
  Briefcase, Mail, Phone, Pencil, Trash2, Tag, 
  ChevronRight, ChevronLeft, Activity, Users, FileText, 
  Plus, Upload, Download, CheckSquare, Star
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const processing = ref(false)
const client = ref(null)
const stats = ref({})
const cases = ref([])

// Modal States
const showContactModal = ref(false)
const editingContact = ref(null)
const showAttachmentModal = ref(false)
const showEditModal = ref(false)

const loadClientData = async () => {
  try {
    loading.value = true
    const response = await ClientService.getOne(route.params.id)
    client.value = response.data.client
    stats.value = response.data.stats
    loadCases()
  } catch (error) {
    console.error('Error cargando cliente:', error)
    Swal.fire('Error', 'No se pudo cargar la información del cliente', 'error')
    router.push('/clients')
  } finally {
    loading.value = false
  }
}

const loadCases = async () => {
    try {
        const res = await axios.get('/v1/cases', {
            params: { client_id: route.params.id, per_page: 5 }
        })
        cases.value = res.data.data
    } catch (error) {
        console.error('Error cargando casos:', error)
    }
}

// Contact Logic
const openContactModal = (contact = null) => {
  editingContact.value = contact
  showContactModal.value = true
}

const closeContactModal = () => {
  showContactModal.value = false
  editingContact.value = null
}

const handleSaveContact = async (formData) => {
  processing.value = true
  try {
    if (editingContact.value) {
      await ClientService.updateContact(editingContact.value.id, formData)
    } else {
      await ClientService.addContact(client.value.id, formData)
    }
    await loadClientData()
    closeContactModal()
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Contacto guardado',
      showConfirmButton: false,
      timer: 2000
    })
  } catch (error) {
    Swal.fire('Error', 'No se pudo guardar el contacto', 'error')
  } finally {
    processing.value = false
  }
}

// Attachment Logic
const openUploadModal = () => {
  showAttachmentModal.value = true
}

const closeAttachmentModal = () => {
  showAttachmentModal.value = false
}

const handleSaveAttachment = async (formData) => {
  processing.value = true
  try {
    const data = new FormData()
    data.append('file', formData.file)
    data.append('name', formData.name)
    data.append('category', formData.category)

    await ClientService.addAttachment(client.value.id, data)
    
    await loadClientData()
    closeAttachmentModal()
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Documento subido',
      showConfirmButton: false,
      timer: 2000
    })
  } catch (error) {
    Swal.fire('Error', 'No se pudo subir el documento', 'error')
  } finally {
    processing.value = false
  }
}

const deleteAttachment = async (attachmentId) => {
  const result = await Swal.fire({
    title: '¿Eliminar documento?',
    text: "Esta acción no se puede deshacer.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  })

  if (result.isConfirmed) {
    try {
      await ClientService.deleteAttachment(client.value.id, attachmentId)
      await loadClientData()
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Documento eliminado',
        showConfirmButton: false,
        timer: 2000
      })
    } catch (error) {
      Swal.fire('Error', 'No se pudo eliminar el documento', 'error')
    }
  }
}

const activeTasks = computed(() => {
  if (!client.value?.flows) return []
  return client.value.flows.flatMap(f => f.tasks || [])
    .filter(t => t.status === 'in_progress' || t.status === 'pending')
    .slice(0, 5)
})

const getFlowName = (flowId) => {
  return client.value.flows.find(f => f.id === flowId)?.name || 'Flujo'
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-blue-500/10 text-blue-500 border border-blue-500/20',
    paused: 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
    completed: 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20',
    cancelled: 'bg-rose-500/10 text-rose-500 border border-rose-500/20'
  }
  return classes[status] || 'bg-slate-500/10 text-slate-500'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const getFileUrl = (path) => {
  const baseUrl = import.meta.env.VITE_API_BASE_URL.replace('/api/v1', '')
  return `${baseUrl}/storage/${path}`
}

const confirmDelete = async () => {
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: "Si eliminas este cliente se perderá su historial y documentos.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  })

  if (result.isConfirmed) {
    try {
      await ClientService.delete(client.value.id)
      Swal.fire('Eliminado', 'El cliente ha sido borrado.', 'success')
      router.push('/clients')
    } catch (error) {
      Swal.fire('Error', 'No se pudo eliminar el cliente', 'error')
    }
  }
}

const editClient = () => {
  showEditModal.value = true
}

const handleUpdateClient = async (formData) => {
  processing.value = true
  try {
    await ClientService.update(client.value.id, formData)
    await loadClientData()
    showEditModal.value = false
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Cliente actualizado',
      showConfirmButton: false,
      timer: 2000
    })
  } catch (error) {
    Swal.fire('Error', 'No se pudo actualizar el cliente', 'error')
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  loadClientData()
})
</script>

<style scoped>
.font-black { font-weight: 900; }
</style>
