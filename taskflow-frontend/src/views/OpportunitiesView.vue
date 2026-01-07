<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-12">
    <Navbar />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header Seccion -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
             <div class="p-2 bg-blue-100 dark:bg-blue-500/10 rounded-xl">
                <TrendingUp class="w-8 h-8 text-blue-600 dark:text-blue-400" />
             </div>
             Oportunidades SweetCRM
          </h2>
          <p class="text-slate-500 dark:text-slate-400 mt-2 font-medium flex items-center gap-2">
            Gestiona tus oportunidades de venta y dispara flujos operativos.
          </p>
        </div>
        
        <!-- Stats Rápidas -->
        <div class="flex items-center gap-4">
           <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm text-center min-w-[120px]">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Pipeline</p>
              <p class="text-xl font-black text-slate-800 dark:text-white mt-1">{{ formatCurrency(totalPipeline) }}</p>
           </div>
           <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm text-center min-w-[100px]">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Abiertas</p>
              <p class="text-xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ opportunities.length }}</p>
           </div>
        </div>
      </div>

      <!-- Barra de Filtros -->
      <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 flex-1 min-w-[300px]">
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input 
              v-model="search" 
              type="text" 
              placeholder="Buscar por nombre u oportunidad..." 
              class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all dark:text-white"
            />
          </div>
          <select v-model="filterStage" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-2 text-sm outline-none dark:text-white">
            <option value="">Todas las etapas</option>
            <option value="Prospecting">Prospección</option>
            <option value="Negotiation/Review">Negociación</option>
            <option value="Closed Won">Cerrada Ganada</option>
          </select>
        </div>
        
        <button 
          @click="fetchOpportunities" 
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-blue-500/20 active:scale-95"
          :disabled="loading"
        >
          <RefreshCw :class="{'animate-spin': loading}" class="w-4 h-4" />
          Sincronizar
        </button>
      </div>

      <!-- Grid de Oportunidades -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 6" :key="i" class="h-64 bg-slate-200 dark:bg-slate-800 animate-pulse rounded-2xl"></div>
      </div>

      <div v-else-if="filteredOpportunities.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="opp in filteredOpportunities" 
          :key="opp.id" 
          class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-white/5 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all p-6 flex flex-col group overflow-hidden relative"
        >
          <!-- Decorative background -->
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-500/5 to-transparent rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-700"></div>

          <div class="flex items-start justify-between mb-4 relative z-10">
             <div class="p-2.5 bg-blue-50 dark:bg-blue-500/10 rounded-2xl border border-blue-100 dark:border-blue-500/20">
                <Briefcase class="w-5 h-5 text-blue-600 dark:text-blue-400" />
             </div>
             <span :class="getStageClass(opp.sales_stage)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border">
                {{ translateStage(opp.sales_stage) }}
             </span>
          </div>

          <div class="mb-6 relative z-10">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2 min-h-[3.5rem]">
              {{ opp.name }}
            </h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-2">
               <User class="w-4 h-4" />
               {{ opp.client?.name || 'Cliente no vinculado' }}
            </p>
          </div>

          <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl mb-6 border border-slate-100 dark:border-white/5">
             <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Monto Estimado</p>
                <p class="text-xl font-black text-slate-800 dark:text-white">{{ formatCurrency(opp.amount) }}</p>
             </div>
             <div class="text-right">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Cierre Esperado</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ formatDate(opp.expected_closed_date) }}</p>
             </div>
          </div>

          <!-- Botones de Acción -->
          <div class="grid grid-cols-1 gap-3 mt-auto relative z-10">
             <button 
               @click="sendToOperations(opp)"
               class="w-full flex items-center justify-center gap-2 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20 active:scale-95 transition-all"
               :disabled="sending === opp.id"
             >
               <Rocket v-if="sending !== opp.id" class="w-5 h-5" />
               <RefreshCw v-else class="w-5 h-5 animate-spin" />
               {{ sending === opp.id ? 'Iniciando...' : 'Lanzar Flujo Operativo' }}
             </button>
             
             <div v-if="opp.description" class="mt-4 p-3 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-white/5">
                 <p class="text-xs text-slate-500 line-clamp-2 italic" v-html="opp.description"></p>
             </div>
          </div>
        </div>
      </div>

      <div v-else class="flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-800 rounded-3xl border border-dashed border-slate-300 dark:border-white/10">
          <PackageOpen class="w-16 h-16 text-slate-300 mb-4" />
          <h3 class="text-xl font-bold text-slate-800 dark:text-white">No hay oportunidades</h3>
          <p class="text-slate-500">Sincroniza con SweetCRM para ver tus oportunidades de venta.</p>
      </div>
    </main>

    <!-- Modal de Confirmación -->
    <Transition name="fade">
      <div v-if="showSuccessModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 max-w-sm w-full shadow-2xl border border-slate-200 dark:border-white/10 text-center animate-scale-in">
           <div class="w-20 h-20 bg-emerald-100 dark:bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
              <CheckCircle class="w-10 h-10 text-emerald-600 dark:text-emerald-400" />
           </div>
           <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-2">¡Flujo Iniciado!</h3>
           <p class="text-slate-500 dark:text-slate-400 mb-8">
             Se ha creado una tarea para Operaciones vinculada a la oportunidad <strong>{{ lastTriggeredOppName }}</strong>.
           </p>
           <button @click="showSuccessModal = false" class="w-full py-3 bg-slate-800 dark:bg-white text-white dark:text-slate-900 rounded-xl font-bold hover:opacity-90 transition-all">
             Entendido
           </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { opportunitiesAPI } from '@/services/api'
import Navbar from '@/components/AppNavbar.vue'
import { 
  TrendingUp, Search, RefreshCw, Briefcase, User, 
  Rocket, PackageOpen, CheckCircle, Clock 
} from 'lucide-vue-next'

const opportunities = ref([])
const loading = ref(false)
const search = ref('')
const filterStage = ref('')
const sending = ref(null)
const showSuccessModal = ref(false)
const lastTriggeredOppName = ref('')

const fetchOpportunities = async () => {
  loading.ref = true
  try {
    const response = await opportunitiesAPI.getAll()
    opportunities.value = response.data.data // Laravel Paginate structure
  } catch (error) {
    console.error('Error fetching opportunities:', error)
  } finally {
    loading.value = false
  }
}

const filteredOpportunities = computed(() => {
  return opportunities.value.filter(opp => {
    const matchesSearch = opp.name.toLowerCase().includes(search.value.toLowerCase()) || 
                          (opp.client?.name || '').toLowerCase().includes(search.value.toLowerCase())
    const matchesStage = !filterStage.value || opp.sales_stage === filterStage.value
    return matchesSearch && matchesStage
  })
})

const totalPipeline = computed(() => {
  return filteredOpportunities.value.reduce((acc, opp) => acc + parseFloat(opp.amount || 0), 0)
})

const sendToOperations = async (opp) => {
  sending.value = opp.id
  try {
    await opportunitiesAPI.sendToOperations(opp.id)
    lastTriggeredOppName.value = opp.name
    showSuccessModal.value = true
    // Podríamos recargar o actualizar el estado de la oportunidad localmente
  } catch (error) {
    console.error('Error sending to operations:', error)
    alert('No se pudo enviar la oportunidad a operaciones. Por favor intenta de nuevo.')
  } finally {
    sending.value = null
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(amount)
}

const formatDate = (date) => {
  if (!date) return 'S/F'
  return new Date(date).toLocaleDateString('es-CL')
}

const translateStage = (stage) => {
  const map = {
    'Prospecting': 'Prospección',
    'Qualification': 'Calificación',
    'Needs Analysis': 'Análisis de Necesidades',
    'Value Proposition': 'Propuesta de Valor',
    'Id. Decision Makers': 'Id. Decisores',
    'Perception Analysis': 'Análisis de Percepción',
    'Proposal/Price Quote': 'Propuesta / Cotización',
    'Negotiation/Review': 'Negociación / Revisión',
    'Closed Won': 'Cerrada Ganada',
    'Closed Lost': 'Cerrada Perdida',
  }
  return map[stage] || stage
}

const getStageClass = (stage) => {
  if (stage === 'Closed Won') return 'bg-emerald-50 text-emerald-600 border-emerald-100'
  if (stage === 'Closed Lost') return 'bg-rose-50 text-rose-600 border-rose-100'
  if (stage === 'Negotiation/Review' || stage === 'Proposal/Price Quote') return 'bg-amber-50 text-amber-600 border-amber-100'
  return 'bg-blue-50 text-blue-600 border-blue-100'
}

onMounted(() => {
  fetchOpportunities()
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@keyframes scale-in {
  from { transform: scale(0.9); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
.animate-scale-in { animation: scale-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }

.animate-shimmer {
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}
@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
</style>
