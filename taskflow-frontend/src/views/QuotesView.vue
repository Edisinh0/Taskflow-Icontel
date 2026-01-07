<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-12">
    <Navbar />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
           <div class="p-2 bg-amber-100 dark:bg-amber-500/10 rounded-xl">
              <FileText class="w-8 h-8 text-amber-600 dark:text-amber-400" />
           </div>
           Cotizaciones SweetCRM
        </h2>
        <p class="text-slate-500 dark:text-slate-400 mt-2 font-medium">Revisa el estado de las propuestas comerciales enviadas.</p>
      </div>

      <!-- Barra de Filtros -->
      <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 flex-1">
          <div class="relative flex-1 max-w-md">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input 
              v-model="search" 
              type="text" 
              placeholder="Buscar por número o asunto..." 
              class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-sm outline-none dark:text-white"
            />
          </div>
        </div>
      </div>

      <!-- Tabla de Cotizaciones -->
      <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-white/5 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
           <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-white/5">
              <tr>
                 <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Número</th>
                 <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Asunto / Oportunidad</th>
                 <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Cliente</th>
                 <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Monto</th>
                 <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Estado</th>
                 <th class="px-6 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Sincronizado</th>
              </tr>
           </thead>
           <tbody class="divide-y divide-slate-100 dark:divide-white/5">
              <tr v-for="quote in quotes" :key="quote.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors group">
                 <td class="px-6 py-4 font-mono text-sm text-blue-600 dark:text-blue-400 font-bold">
                    #{{ quote.quote_number }}
                 </td>
                 <td class="px-6 py-4">
                    <p class="font-bold text-slate-800 dark:text-white">{{ quote.subject }}</p>
                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                       <TrendingUp :size="12" /> {{ quote.opportunity?.name || 'S/O' }}
                    </p>
                 </td>
                 <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                    {{ quote.client?.name || '-' }}
                 </td>
                 <td class="px-6 py-4 font-bold text-slate-800 dark:text-white">
                    {{ formatCurrency(quote.total_amount) }}
                 </td>
                 <td class="px-6 py-4">
                    <span :class="getStatusClass(quote.status)" class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-tighter border">
                       {{ quote.status }}
                    </span>
                 </td>
                 <td class="px-6 py-4 text-xs text-slate-400">
                    {{ formatDate(quote.sweetcrm_synced_at) }}
                 </td>
              </tr>
              <tr v-if="!quotes.length">
                 <td colspan="6" class="px-6 py-20 text-center text-slate-400 italic">
                    No hay cotizaciones sincronizadas.
                 </td>
              </tr>
           </tbody>
        </table>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Navbar from '@/components/AppNavbar.vue'
import { FileText, Search, TrendingUp } from 'lucide-vue-next'

const quotes = ref([])
const search = ref('')

const fetchQuotes = async () => {
  try {
    const response = await axios.get('/api/v1/opportunities') // Por ahora listamos via opportunities o creamos endpoint
    // Nota: Como no cree endpoint de quotes directo, por ahora es placeholder
  } catch (error) {
    console.error(error)
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(amount)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('es-CL')
}

const getStatusClass = (status) => {
  if (status === 'Confirmed') return 'bg-emerald-50 text-emerald-600 border-emerald-100'
  if (status === 'Draft') return 'bg-slate-50 text-slate-600 border-slate-100'
  return 'bg-amber-50 text-amber-600 border-amber-100'
}

onMounted(() => {
  // fetchQuotes()
})
</script>
