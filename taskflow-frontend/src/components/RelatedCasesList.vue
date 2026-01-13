<template>
  <div class="space-y-4">
    <!-- Loading state -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="h-24 bg-slate-50 dark:bg-white/5 rounded-2xl animate-pulse"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!cases || cases.length === 0" class="text-center py-12 bg-slate-50 dark:bg-white/5 rounded-3xl border border-dashed border-slate-200 dark:border-white/10">
      <Briefcase :size="32" class="text-slate-300 mx-auto mb-3" />
      <p class="text-slate-400 dark:text-slate-500 font-bold text-sm">
        No hay casos relacionados {{ parentType ? `a esta ${parentType.toLowerCase()}` : '' }}
      </p>
    </div>

    <!-- Cases list -->
    <div v-else class="grid grid-cols-1 gap-3">
      <div
        v-for="crmCase in cases"
        :key="crmCase.id"
        @click="$emit('view-case', crmCase)"
        class="bg-white dark:bg-slate-700/50 p-4 rounded-2xl border border-slate-100 dark:border-white/10 hover:shadow-md transition-all group cursor-pointer"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-black text-blue-600 dark:text-blue-400">
                #{{ crmCase.case_number }}
              </span>
              <span :class="getStatusClass(crmCase.status)" class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-full border">
                {{ crmCase.status }}
              </span>
            </div>
            <p class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-blue-500 transition-colors line-clamp-2">
              {{ crmCase.subject || crmCase.name }}
            </p>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 gap-2 text-xs">
          <div class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
            <User :size="12" />
            {{ crmCase.assigned_user?.name || 'Sin asignar' }}
          </div>
          <div class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
            <Building2 :size="12" />
            {{ crmCase.client?.name || crmCase.account_name || 'Sin cliente' }}
          </div>
        </div>

        <!-- Priority badge -->
        <div class="mt-2 flex items-center gap-2">
          <span :class="getPriorityClass(crmCase.priority)" class="px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-md border">
            {{ crmCase.priority || 'N/A' }}
          </span>
          <span v-if="crmCase.tasks_count" class="text-[10px] text-slate-400">
            {{ crmCase.tasks_count }} tareas
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Briefcase, User, Building2 } from 'lucide-vue-next'

defineProps({
  cases: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  parentType: {
    type: String,
    default: ''
  }
})

defineEmits(['view-case'])

const getStatusClass = (status) => {
  const map = {
    'Nuevo': 'bg-blue-500/10 text-blue-500 border-blue-500/20',
    'Asignado': 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20',
    'Cerrado': 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
    'Rechazado': 'bg-rose-500/10 text-rose-500 border-rose-500/20'
  }
  return map[status] || 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}

const getPriorityClass = (priority) => {
  const map = {
    'Alta': 'bg-rose-500/10 text-rose-500 border-rose-500/20',
    'Media': 'bg-amber-500/10 text-amber-500 border-amber-500/20',
    'Baja': 'bg-blue-500/10 text-blue-500 border-blue-500/20'
  }
  return map[priority] || 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}
</script>
