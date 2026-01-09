<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors pb-12">
    <Navbar />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Título y Botón Nueva Tarea -->
      <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight">Dashboard</h2>
          <p class="text-slate-500 dark:text-slate-400 mt-1 text-lg">Bienvenido de nuevo, <span class="text-blue-500 dark:text-blue-400 font-semibold">{{ authStore.currentUser?.name }}</span></p>
        </div>
        <button
          @click="openTaskCreationModal"
          class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl hover:from-blue-700 hover:to-indigo-700 font-bold transition-all shadow-lg shadow-blue-500/20 dark:shadow-blue-900/20 hover:scale-105 active:scale-95 whitespace-nowrap"
        >
          <Plus class="w-5 h-5 mr-2" />
          Nueva Tarea
        </button>
      </div>

      <!-- Estadísticas Principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Flujos Activos -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-blue-500/20 transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Flujos Activos</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ stats.activeFlows }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">+{{ stats.flowsThisWeek }} esta semana</p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-500/10 p-3 rounded-xl border border-blue-100 dark:border-blue-500/20 group-hover:bg-blue-100 dark:group-hover:bg-blue-500/20 transition-colors">
              <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Pendientes -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-amber-500/20 transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Pendientes</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-colors">{{ stats.pendingTasks }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ stats.urgentTasks }} urgentes</p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-500/10 p-3 rounded-xl border border-amber-100 dark:border-amber-500/20 group-hover:bg-amber-100 dark:group-hover:bg-amber-500/20 transition-colors">
              <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Completadas -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-emerald-500/20 transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Completadas Hoy</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors">{{ stats.completedToday }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ stats.completionRate }}% tasa de éxito</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-500/10 p-3 rounded-xl border border-emerald-100 dark:border-emerald-500/20 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-500/20 transition-colors">
              <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Vencidas -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-rose-500/20 transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Vencidas</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-rose-500 dark:group-hover:text-rose-400 transition-colors">{{ stats.overdueTasks }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">Acción requerida</p>
            </div>
            <div class="bg-rose-50 dark:bg-rose-500/10 p-3 rounded-xl border border-rose-100 dark:border-rose-500/20 group-hover:bg-rose-100 dark:group-hover:bg-rose-500/20 transition-colors">
              <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Tareas Delegadas -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 hover:border-purple-500/20 transition-all group cursor-pointer" @click="scrollToDelegated">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm font-medium uppercase tracking-wider">Delegadas</p>
              <p class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1 group-hover:text-purple-500 dark:group-hover:text-purple-400 transition-colors">{{ stats.delegatedTasks }}</p>
              <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 font-medium">{{ stats.delegatedPending }} pendientes</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-500/10 p-3 rounded-xl border border-purple-100 dark:border-purple-500/20 group-hover:bg-purple-100 dark:group-hover:bg-purple-500/20 transition-colors">
              <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8m0 8l-6-2m6 2l6-2" />
              </svg>
            </div>
          </div>
        </div>
      </div>


      <!-- Resumen de Productividad -->
      <!-- Resumen de Productividad -->
      <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-8 mb-8 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
        <!-- Decorative Background Gradient -->
        <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-blue-500/20 transition-all duration-700"></div>
        <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-indigo-500/20 transition-all duration-700"></div>

        <div class="flex flex-col md:flex-row items-center justify-between relative z-10">
          <div class="mb-6 md:mb-0 max-w-lg">
             <div class="flex items-center space-x-4 mb-3">
                <div class="p-3 bg-blue-50 dark:bg-blue-500/10 rounded-2xl border border-blue-100 dark:border-blue-500/20 shadow-sm">
                   <Rocket class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                   <h3 class="text-2xl font-bold text-slate-800 dark:text-white">Productividad Semanal</h3>
                   <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Tu rendimiento los últimos 7 días</p>
                </div>
             </div>
            <p class="text-slate-600 dark:text-slate-300 text-lg leading-relaxed">
              Has completado <strong class="text-blue-600 dark:text-blue-400 font-bold bg-blue-50 dark:bg-blue-500/10 px-2 py-0.5 rounded-md mx-1">{{ stats.completedThisWeek }}</strong> 
              tareas de <strong class="text-slate-800 dark:text-white font-bold">{{ stats.totalThisWeek }}</strong> asignadas.
              <span v-if="stats.completedThisWeek > 0" class="block mt-1 text-sm text-slate-500">¡Sigue así! Estás avanzando hacia tus objetivos.</span>
            </p>
          </div>

          <div class="flex items-center space-x-8">
             <div class="text-right">
                 <p class="text-6xl font-black tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
                   {{ Math.round((stats.completedThisWeek / stats.totalThisWeek) * 100) || 0 }}<span class="text-3xl font-bold text-slate-400">%</span>
                 </p>
                 <p class="text-slate-400 dark:text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Efectividad Global</p>
             </div>
          </div>
        </div>
        
        <!-- Modern Progress Bar -->
        <div class="mt-8 relative">
          <div class="flex justify-between mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
            <span>0%</span>
            <span>Meta: 100%</span>
          </div>
          <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-4 overflow-hidden shadow-inner">
            <div 
              class="h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden"
              :class="[
                'bg-gradient-to-r from-blue-500 to-indigo-600',
                Math.round((stats.completedThisWeek / stats.totalThisWeek) * 100) >= 100 ? 'shadow-[0_0_20px_rgba(79,70,229,0.5)]' : ''
              ]"
              :style="`width: ${Math.round((stats.completedThisWeek / stats.totalThisWeek) * 100) || 0}%`"
            >
              <!-- Shine effect -->
              <div class="absolute top-0 right-0 bottom-0 left-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 animate-shimmer"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dashboard Especializado Operaciones/Soporte -->
      <div v-if="userDepartment !== 'Ventas' && userDepartment !== 'General'" class="mb-10">
        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6 flex items-center">
          <span class="w-2 h-6 bg-indigo-500 rounded-sm mr-3 shadow-[0_0_10px_rgba(99,102,241,0.5)]"></span>
          Mi Agenda Operativa
        </h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Tareas de Levantamiento (Urgentes) -->
          <div class="space-y-4">
            <h4 class="text-sm font-black text-rose-500 uppercase tracking-widest flex items-center gap-2 px-1">
              <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
              Levantamientos Técnicos
            </h4>
            <div v-for="task in allTasks.filter(t => t.title.includes('Levantamiento'))" :key="task.id" 
                 class="bg-white dark:bg-slate-800 border-l-4 border-rose-500 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                 @click="handleTaskClick(task)">
              <div class="flex justify-between items-start">
                <div>
                  <h5 class="font-bold text-slate-800 dark:text-white group-hover:text-rose-500 transition-colors">{{ task.title }}</h5>
                  <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ task.description }}</p>
                </div>
                <span class="text-[10px] font-black bg-rose-50 text-rose-600 px-2 py-1 rounded-lg uppercase border border-rose-100">Urgente</span>
              </div>
              <div class="mt-4 flex items-center justify-between text-[11px] font-bold text-slate-400">
                <span class="flex items-center gap-1"><Briefcase :size="12" /> {{ task.crm_case?.client?.name || 'S/C' }}</span>
                <span class="flex items-center gap-1"><Clock :size="12" /> {{ formatDate(task.estimated_end_at) }}</span>
              </div>
            </div>
            <div v-if="!allTasks.filter(t => t.title.includes('Levantamiento')).length" class="text-xs text-slate-400 italic px-1">No hay levantamientos pendientes.</div>
          </div>

          <!-- Tareas de Ejecución -->
          <div class="space-y-4">
            <h4 class="text-sm font-black text-blue-500 uppercase tracking-widest flex items-center gap-2 px-1">
              <span class="w-2 h-2 rounded-full bg-blue-500"></span>
              Tareas de Ejecución
            </h4>
            <div v-for="task in allTasks.filter(t => t.title.includes('Ejecución'))" :key="task.id" 
                 class="bg-white dark:bg-slate-800 border-l-4 border-blue-500 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all cursor-pointer group"
                 @click="handleTaskClick(task)">
               <div class="flex justify-between items-start">
                <div>
                  <h5 class="font-bold text-slate-800 dark:text-white group-hover:text-blue-500 transition-colors">{{ task.title }}</h5>
                  <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ task.description }}</p>
                </div>
                <span class="text-[10px] font-black bg-blue-50 text-blue-600 px-2 py-1 rounded-lg uppercase border border-blue-100">En Curso</span>
              </div>
              <div class="mt-4 flex items-center justify-between text-[11px] font-bold text-slate-400">
                <span class="flex items-center gap-1"><Briefcase :size="12" /> {{ task.crm_case?.client?.name || 'S/C' }}</span>
                <span class="flex items-center gap-1"><Clock :size="12" /> {{ formatDate(task.estimated_end_at) }}</span>
              </div>
            </div>
             <div v-if="!allTasks.filter(t => t.title.includes('Ejecución')).length" class="text-xs text-slate-400 italic px-1">No hay ejecuciones pendientes.</div>
          </div>
        </div>
      </div>

      <!-- Gráficos y Métricas (Sólo para general/admin) -->
      <div v-if="userDepartment === 'General'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Tendencia de Tareas (Últimos 7 días) -->
        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center">
             <span class="w-2 h-6 bg-blue-500 rounded-sm mr-3"></span>
             Tendencia de Tareas
          </h3>
          <div class="h-64">
            <Line v-if="taskTrendData.datasets[0].data.length > 0" :data="taskTrendData" :options="chartOptions" />
            <p v-else class="text-slate-400 dark:text-slate-500 text-center pt-20">No hay datos disponibles</p>
          </div>
        </div>

        <!-- Estado de Tareas por Prioridad -->
        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6 flex items-center">
             <span class="w-2 h-6 bg-amber-500 rounded-sm mr-3"></span>
             Distribución por Prioridad
          </h3>
          <div class="h-64">
            <Doughnut v-if="priorityChartData.datasets[0].data.some(val => val > 0)" :data="priorityChartData" :options="doughnutOptions" />
            <p v-else class="text-slate-400 dark:text-slate-500 text-center pt-20">No hay datos disponibles</p>
          </div>
        </div>
      </div>



      <!-- Tareas -->
      <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 mb-6">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center">
            <span class="w-2 h-6 bg-blue-500 rounded-sm mr-3"></span>
            Tareas
          </h3>
          <span class="text-xs font-semibold bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 px-2 py-1 rounded-md border border-blue-100 dark:border-blue-500/20">
            {{ allTasks.length }} tareas
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-white/5">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Tarea</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Caso/Proyecto</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Cliente</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Fecha Inicio</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Fecha Término</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Días Restantes</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
              <tr
                v-for="task in allTasks"
                :key="task.id"
                @click="handleTaskClick(task)"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors"
              >
                <td class="px-6 py-4">
                  <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ task.title }}</h4>
                  <p v-if="task.crm_case" class="text-[10px] text-blue-600 dark:text-blue-400 font-mono mt-0.5">#{{ task.crm_case.case_number }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                    {{ task.crm_case ? task.crm_case.subject : task.flow?.name }}
                  </p>
                  <p v-if="task.crm_case" class="text-[10px] text-slate-400 mt-0.5">Caso CRM</p>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-500">{{ task.crm_case?.client?.name || '-' }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-500">{{ formatDate(task.estimated_start_at) }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-500">{{ formatDate(task.estimated_end_at) }}</p>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 text-xs font-bold rounded-lg" :class="getDaysRemainingClass(task.estimated_end_at)">
                    {{ getDaysRemaining(task.estimated_end_at) }}
                  </span>
                </td>
              </tr>
              <tr v-if="allTasks.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                  No hay tareas disponibles.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Proyectos SweetCRM (Jerárquico) -->
      <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 mb-8 overflow-hidden">
        <!-- Header con Toggles -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center">
            <span class="w-2 h-6 bg-indigo-500 rounded-sm mr-3"></span>
            Casos SweetCRM
            <span v-if="dashboardStore.loading" class="ml-3 text-xs font-normal text-slate-400 animate-pulse">Sincronizando...</span>
          </h3>
          
          <div class="flex items-center gap-3">
             <!-- Scope Toggle -->
             <div class="bg-slate-100 dark:bg-slate-700/50 p-1 rounded-lg flex items-center">
                <button 
                  @click="dashboardStore.setScope('my')"
                  class="px-3 py-1.5 text-xs font-bold rounded-md transition-all"
                  :class="dashboardStore.scope === 'my' 
                    ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-400 shadow-sm' 
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                >
                  Mis Casos
                </button>
                <div class="w-px h-4 bg-slate-200 dark:bg-slate-600 mx-1"></div>
                <button 
                  @click="dashboardStore.setScope('area')"
                  class="px-3 py-1.5 text-xs font-bold rounded-md transition-all"
                  :class="dashboardStore.scope === 'area' 
                    ? 'bg-white dark:bg-slate-600 text-indigo-600 dark:text-indigo-400 shadow-sm' 
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                >
                  Área
                </button>
             </div>

             <!-- View Mode Toggle -->
             <button 
                @click="dashboardStore.toggleViewMode()" 
                class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700/50 rounded-lg transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-600"
                :title="dashboardStore.viewMode === 'cases' ? 'Ver como Lista Plana' : 'Ver Jerarquía'"
             >
                <layout-list v-if="dashboardStore.viewMode === 'cases'" class="w-4 h-4" />
                <list v-else class="w-4 h-4" />
             </button>
          </div>
        </div>

        <!-- Contenido -->
        <div class="overflow-x-auto min-h-[200px]">
           
           <!-- Loading State -->
           <div v-if="dashboardStore.loading" class="flex items-center justify-center h-48 text-slate-400 text-sm">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Cargando datos de SweetCRM...
           </div>

           <!-- Empty State -->
           <div v-else-if="!dashboardStore.cases.length && !dashboardStore.orphanTasks.length" class="flex flex-col items-center justify-center h-48 text-slate-400 text-sm">
              <Folder class="w-8 h-8 opacity-20 mb-2" />
              No hay elementos para mostrar en esta vista.
           </div>

           <!-- Hierarchy View (Cases) -->
           <div v-else-if="dashboardStore.viewMode === 'cases'" class="divide-y divide-slate-100 dark:divide-white/5">
              <div v-for="crmCase in dashboardStore.cases" :key="crmCase.id" class="group">
                 <!-- Case Row -->
                 <div class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors flex items-start justify-between cursor-pointer" @click="handleCaseClick(crmCase, $event)">
                    <div class="flex items-start gap-3">
                       <button class="mt-0.5 text-slate-400 hover:text-indigo-500 transition-colors" @click.stop="dashboardStore.toggleCase(crmCase.id)">
                          <chevron-right :class="{'rotate-90': crmCase.expanded}" class="w-4 h-4 transition-transform duration-200" />
                       </button>
                       <div>
                          <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                             {{ crmCase.title }}
                             <span class="text-xs font-mono font-normal text-slate-400 px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded">#{{ crmCase.case_number }}</span>
                          </h4>
                          <div class="flex flex-col gap-1 mt-1.5 text-xs text-slate-500">
                             <div class="flex items-center gap-3">
                                 <span class="flex items-center gap-1" title="Cliente"><Briefcase class="w-3 h-3" /> {{ crmCase.client_id || 'Sin Cliente' }}</span>
                                 <span :class="getStatusClass(crmCase.status, 'case')" class="font-medium px-1.5 rounded">{{ crmCase.status }}</span>
                             </div>
                             <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                <span v-if="crmCase.created_by_name">Creado por: {{ crmCase.created_by_name }}</span>
                                <span v-if="crmCase.assigned_user_name" class="flex items-center gap-1">
                                    • Asignado a: <span class="font-medium text-slate-500 dark:text-slate-300">{{ crmCase.assigned_user_name }}</span>
                                </span>
                             </div>
                          </div>
                       </div>
                    </div>
                    <!-- Case Actions/Meta -->
                    <div class="flex flex-col items-end gap-2">
                       <span class="text-[10px] uppercase font-bold text-slate-400 border border-slate-200 dark:border-slate-700 px-2 py-0.5 rounded-full">{{ crmCase.priority || 'Normal' }}</span>
                       <span class="text-xs text-indigo-500 font-medium" v-if="crmCase.tasks?.length">{{ crmCase.tasks.length }} tareas</span>
                    </div>
                 </div>

                 <!-- Nested Tasks -->
                 <div v-show="crmCase.expanded" class="bg-slate-50/50 dark:bg-slate-800/30 border-y border-slate-100 dark:border-white/5 pl-12 pr-6 py-2 space-y-1 transition-all duration-300">
                    <div v-if="!crmCase.tasks?.length" class="py-2 text-xs text-slate-400 italic">No hay tareas asociadas en TaskFlow.</div>
                    <div 
                        v-for="task in crmCase.tasks" 
                        :key="task.id" 
                        @click="handleTaskClick(task)"
                        class="flex items-center justify-between py-2 px-3 rounded-md hover:bg-white dark:hover:bg-slate-700/50 transition-colors group/task border border-transparent hover:border-slate-200 dark:hover:border-white/5 cursor-pointer"
                    >
                        <div class="flex items-center gap-3">
                            <!-- Visual Inheritance -->
                            <div v-if="crmCase.status === 'Blocket' || task.is_blocked" class="text-rose-500" title="Tarea Bloqueada por Dependencia">
                                <lock class="w-3.5 h-3.5" />
                            </div>
                            <div v-else class="w-3.5 h-3.5 rounded-full border-2 border-slate-300 dark:border-slate-600 group-hover/task:border-indigo-500 transition-colors"></div>
                            
                            <span class="text-sm text-slate-600 dark:text-slate-300 decoration-slate-400 group-hover/task:text-indigo-600 dark:group-hover/task:text-indigo-400 transition-colors" :class="{'line-through opacity-50': task.status === 'Completed', 'font-medium text-slate-800 dark:text-slate-100': task.status !== 'Completed'}">
                                {{ task.title }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-mono text-slate-400">
                            <span v-if="task.due_date">{{ formatDate(task.due_date) }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-sans text-[10px]">{{ task.status }}</span>
                        </div>
                    </div>
                 </div>
              </div>
           </div>

           <!-- Flat List View -->
           <div v-else class="divide-y divide-slate-100 dark:divide-white/5">
                <div 
                    v-for="task in dashboardStore.allTasksFlat" 
                    :key="task.id"
                    @click="handleTaskClick(task)"
                    class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors flex items-center justify-between cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <check-square class="w-4 h-4 text-slate-400" />
                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ task.title }}</p>
                            <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                <span v-if="task.crm_case?.case_number">En Caso #{{ task.crm_case.case_number }}</span>
                                <span v-else-if="task.case_id">En Caso #{{ task.case_id }}</span>
                                <span v-else-if="task.opportunity?.name">En Oportunidad: {{ task.opportunity.name }}</span>
                                <span v-else-if="task.opportunity_id">En Oportunidad #{{ task.opportunity_id }}</span>
                                <span v-else-if="task.flow?.name">En Flujo: {{ task.flow.name }}</span>
                                <span v-else>Tarea Independiente</span>
                            </p>
                        </div>
                    </div>
                     <span :class="getStatusClass(task.status, 'task')" class="text-xs px-2 py-1 rounded-md font-bold uppercase">{{ task.status }}</span>
                </div>
           </div>

        </div>
      </div>


      <!-- Tareas Urgentes y Flujos Recientes -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Tareas Urgentes -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 flex flex-col">
          <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center">
                <span class="w-2 h-2 rounded-full bg-rose-500 mr-2 animate-pulse"></span>
                Tareas Urgentes
            </h3>
            <span class="text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 px-2 py-1 rounded-md border border-rose-100 dark:border-rose-500/20">
                {{ urgentTasks.length }}
            </span>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-white/5">
            <div
              v-for="task in urgentTasks"
              :key="task.id"
              @click="handleTaskClick(task)"
              class="block px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors group"
            >
              <div class="flex items-start justify-between mb-2">
                <div class="flex-1">
                  <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ task.title }}</h4>
                  <p class="text-xs text-slate-500 mt-1 flex items-center">
                    <svg class="w-3 h-3 mr-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    {{ task.crm_case?.title || task.flow?.name || 'Tarea de CRM' }}
                  </p>
                </div>
                <span class="px-2.5 py-1 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-lg border border-rose-100 dark:border-rose-500/20 shadow-sm shrink-0 ml-3">
                  {{ getDaysRemaining(task.estimated_end_at) }}
                </span>
              </div>
              <div class="flex items-center gap-4 text-[10px] text-slate-400">
                <span>Inicio: {{ formatDate(task.estimated_start_at) }}</span>
                <span>•</span>
                <span>Término: {{ formatDate(task.estimated_end_at) }}</span>
              </div>
            </div>
            <div v-if="urgentTasks.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                ¡Todo bajo control! No hay tareas urgentes.
            </div>
          </div>
        </div>

        <!-- Flujos Recientes -->
        <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 flex flex-col">
          <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center">
              <Folder class="w-5 h-5 mr-2 text-blue-500" />
              Casos Recientes
            </h3>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-white/5">
            <router-link
              v-for="flow in recentFlows"
              :key="flow.id"
              :to="`/flows/${flow.id}`"
              class="block px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group"
            >
              <div class="flex items-center justify-between mb-2">
                <div class="flex-1 min-w-0 mr-4">
                  <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate">{{ flow.name }}</h4>
                  <p class="text-xs text-slate-500 mt-1">{{ flow.tasks?.length || 0 }} tareas</p>
                </div>
                <div class="flex flex-col items-end space-y-2 shrink-0">
                  <span :class="getStatusClass(flow.status)" class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border border-current/20">
                    {{ getStatusText(flow.status) }}
                  </span>
                  <div class="w-24">
                    <div class="w-full bg-slate-200 dark:bg-slate-700/50 rounded-full h-1.5 overflow-hidden">
                      <div
                        class="bg-blue-500 h-1.5 rounded-full transition-all duration-500"
                        :style="`width: ${calculateProgress(flow)}%`"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-4 text-[10px] text-slate-400">
                <span>Inicio: {{ formatDate(flow.created_at) }}</span>
                <span>•</span>
                <span>Actualizado: {{ formatDate(flow.updated_at) }}</span>
              </div>
            </router-link>
            <div v-if="recentFlows.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                No hay flujos recientes.
            </div>
          </div>
        </div>
      </div>

      <!-- Tareas Delegadas a Otros - Sección Completa -->
      <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-sm dark:shadow-lg border border-slate-200 dark:border-white/5 mb-8" ref="delegatedSection">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 flex items-center justify-between">
          <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center">
            <span class="w-2 h-6 bg-purple-500 rounded-sm mr-3 shadow-[0_0_10px_rgba(168,85,247,0.5)]"></span>
            Tareas y Casos Delegados
          </h3>
          <span class="text-sm font-semibold bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 px-3 py-1.5 rounded-lg border border-purple-100 dark:border-purple-500/20">
            {{ delegatedTasks.length }} total • {{ stats.delegatedPending }} pendientes
          </span>
        </div>

        <!-- Content -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-white/5">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Asunto / Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Tipo</th>
                <th class="px-6 py-3 text-left text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Asignado a</th>
                <th class="px-6 py-3 text-left text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Prioridad</th>
                <th class="px-6 py-3 text-left text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Estado</th>
                <th class="px-6 py-3 text-left text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Creado</th>
                <th class="px-6 py-3 text-left text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Finaliza</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
              <tr 
                v-for="task in delegatedTasks" 
                :key="task.id"
                @click="handleTaskClick(task)"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors group"
              >
                <!-- Asunto / Nombre -->
                <td class="px-6 py-4">
                  <div class="space-y-1">
                    <p class="text-sm font-semibold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                      {{ task.title }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      <span v-if="task.crm_case?.case_number">#{{ task.crm_case.case_number }}</span>
                      <span v-else-if="task.opportunity_id">#OPP-{{ task.opportunity_id }}</span>
                      <span v-else>#TAREA-{{ task.id }}</span>
                    </p>
                  </div>
                </td>

                <!-- Tipo (Caso, Oportunidad, Tarea) -->
                <td class="px-6 py-4">
                  <span v-if="task.crm_case" class="inline-block px-2 py-1 text-xs font-bold rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-500/20">
                    Caso
                  </span>
                  <span v-else-if="task.opportunity" class="inline-block px-2 py-1 text-xs font-bold rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-500/20">
                    Oportunidad
                  </span>
                  <span v-else class="inline-block px-2 py-1 text-xs font-bold rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-100 dark:border-slate-600">
                    Tarea
                  </span>
                </td>

                <!-- Asignado a -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold">
                      {{ task.assignee?.name?.charAt(0) || 'U' }}
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                      {{ task.assignee?.name || 'Sin asignar' }}
                    </span>
                  </div>
                </td>

                <!-- Prioridad -->
                <td class="px-6 py-4">
                  <span :class="getPriorityBadgeClass(task.priority)" class="inline-block px-2 py-1 text-xs font-bold rounded-lg border">
                    {{ formatPriority(task.priority) }}
                  </span>
                </td>

                <!-- Estado -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="getStatusColor(task.status)"></span>
                    <span :class="getStatusBadgeClass(task.status)" class="text-xs font-bold rounded px-2 py-1 border">
                      {{ formatTaskStatus(task.status) }}
                    </span>
                  </div>
                </td>

                <!-- Creado -->
                <td class="px-6 py-4">
                  <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                    {{ formatDateShort(task.created_at) }}
                  </span>
                </td>

                <!-- Finaliza -->
                <td class="px-6 py-4">
                  <span 
                    v-if="task.estimated_end_at || task.due_date"
                    :class="isOverdue(task.estimated_end_at || task.due_date) ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-600 dark:text-slate-300'"
                    class="text-xs font-medium"
                  >
                    {{ formatDateShort(task.estimated_end_at || task.due_date) }}
                  </span>
                  <span v-else class="text-xs text-slate-400">-</span>
                </td>
              </tr>

              <tr v-if="delegatedTasks.length === 0">
                <td colspan="7" class="px-6 py-8 text-center">
                  <p class="text-slate-400 dark:text-slate-500 text-sm">No hay tareas delegadas a otros.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Task Creation Modal -->
    <TaskCreationModal
      :isOpen="showTaskCreationModal"
      :users="users"
      @close="showTaskCreationModal = false"
      @created="handleTaskCreated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import TaskCreationModal from '@/components/TaskCreationModal.vue'
import { Plus } from 'lucide-vue-next'
import { useDashboardStore } from '@/stores/dashboard'
import { flowsAPI, tasksAPI } from '@/services/api'
import { Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title
} from 'chart.js'
import Navbar from '@/components/AppNavbar.vue'
import { Rocket, Folder, Briefcase, ChevronRight, Lock, LayoutList, List, CheckSquare } from 'lucide-vue-next'

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, PointElement, LineElement, Title)

const authStore = useAuthStore()
const dashboardStore = useDashboardStore()
const router = useRouter()

const delegatedSection = ref(null)
const showTaskCreationModal = ref(false)
const users = ref([])

const stats = computed(() => {
    // Determine what data to use based on user area
    let activeItemsCount = 0;

    if (dashboardStore.userArea === 'sales') {
        // Para equipo de ventas
        activeItemsCount = dashboardStore.opportunities.length;
    } else {
        // Para otros
        activeItemsCount = dashboardStore.cases.length;
    }

    const crmTasks = dashboardStore.allTasksFlat

    // Determinar delegadas
    const delegatedTotal = dashboardStore.userArea === 'sales'
        ? dashboardStore.delegatedSales.total
        : dashboardStore.delegated.total;
    const delegatedPending = dashboardStore.userArea === 'sales'
        ? dashboardStore.delegatedSales.pending
        : dashboardStore.delegated.pending;

    return {
        activeFlows: activeItemsCount, // Casos para ops, Oportunidades para sales
        pendingTasks: crmTasks.filter(t => t.status !== 'Completed' && t.status !== 'Deferred').length,
        completedToday: crmTasks.filter(t => t.status === 'Completed').length,
        overdueTasks: crmTasks.filter(t => {
             if (!t.date_due) return false;
             return new Date(t.date_due) < new Date() && t.status !== 'Completed';
        }).length,
        urgentTasks: crmTasks.filter(t => t.priority === 'High' || t.priority === 'Urgent').length,
        delegatedTasks: delegatedTotal,
        delegatedPending: delegatedPending,

        // Placeholders o Cálculos para métricas semanales
        flowsThisWeek: 0,
        completedThisWeek: crmTasks.filter(t => t.status === 'Completed').length,
        totalThisWeek: crmTasks.length,
        completionRate: crmTasks.length ? Math.round((crmTasks.filter(t=>t.status==='Completed').length / crmTasks.length) * 100) : 0
    }
})

// stats ref removed in favor of computed property
const urgentTasks = computed(() => {
    return dashboardStore.allTasksFlat
        .filter(t => (t.priority === 'High' || t.priority === 'Urgent') && t.status !== 'Completed' && t.status !== 'Deferred')
        .slice(0, 5)
})

const delegatedTasks = computed(() => {
    // Combinar casos/oportunidades y tareas delegadas desde el store
    let combined = [];

    if (dashboardStore.userArea === 'sales') {
        // Para equipo de ventas: oportunidades delegadas + tareas delegadas
        combined = [
            ...dashboardStore.delegatedSales.opportunities.map(o => ({
                ...o,
                type: 'opportunity',
                subject: o.title,
                assigned_user: { name: o.assigned_user_name }
            })),
            ...dashboardStore.delegatedSales.tasks.map(t => ({
                ...t,
                type: 'task',
                subject: t.title,
                assigned_user: { name: t.assigned_user_name }
            }))
        ];
    } else {
        // Para otros: casos delegados + tareas delegadas
        combined = [
            ...dashboardStore.delegated.cases.map(c => ({
                ...c,
                type: 'case',
                subject: c.title,
                assigned_user: { name: c.assigned_user_name }
            })),
            ...dashboardStore.delegated.tasks.map(t => ({
                ...t,
                type: 'task',
                subject: t.title,
                assigned_user: { name: t.assigned_user_name }
            }))
        ];
    }

    return combined.sort((a, b) => {
        // Ordenar por estado: primero pendientes, luego en progreso, luego completadas
        const statusOrder = {
            'New': 0, 'Not Started': 0, 'Prospecting': 0, 'Qualification': 0,
            'in_progress': 1, 'In Progress': 1, 'Needs Analysis': 1, 'Value Proposition': 1,
            'completed': 2, 'Completed': 2, 'Closed': 2, 'Closed Won': 2,
            'cancelled': 3, 'Rejected': 3
        }
        return (statusOrder[a.status] || 99) - (statusOrder[b.status] || 99)
    })
})

const recentFlows = ref([])
const allTasks = computed(() => dashboardStore.allTasksFlat)
// myCases removed in favor of store

const taskTrendData = ref({
  labels: [],
  datasets: [{
    label: 'Completadas',
    data: [],
    borderColor: '#3B82F6',
    backgroundColor: (context) => {
      const ctx = context.chart.ctx;
      const gradient = ctx.createLinearGradient(0, 0, 0, 300);
      gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
      gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
      return gradient;
    },
    tension: 0.4,
    fill: true,
    borderWidth: 3,
    pointBackgroundColor: '#3B82F6',
    pointBorderColor: '#fff',
    pointBorderWidth: 2,
    pointRadius: 5,
    pointHoverRadius: 8,
    pointHoverBackgroundColor: '#3B82F6',
    pointHoverBorderColor: '#fff',
    pointHoverBorderWidth: 3
  }]
})

const priorityChartData = ref({
  labels: ['Baja', 'Media', 'Alta', 'Urgente'],
  datasets: [{
    data: [0, 0, 0, 0],
    backgroundColor: [
      'rgba(59, 130, 246, 0.8)',   // Azul para Baja
      'rgba(252, 211, 77, 0.8)',   // Amarillo para Media
      'rgba(249, 115, 22, 0.8)',   // Naranja para Alta
      'rgba(239, 68, 68, 0.8)'     // Rojo para Urgente
    ],
    borderColor: [
      'rgba(59, 130, 246, 1)',
      'rgba(252, 211, 77, 1)',
      'rgba(249, 115, 22, 1)',
      'rgba(239, 68, 68, 1)'
    ],
    borderWidth: 2,
    hoverBackgroundColor: [
      'rgba(59, 130, 246, 1)',
      'rgba(252, 211, 77, 1)',
      'rgba(249, 115, 22, 1)',
      'rgba(239, 68, 68, 1)'
    ],
    hoverBorderColor: '#fff',
    hoverBorderWidth: 4
  }]
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: { 
      display: true,
      labels: {
        color: '#6B7280',
        font: {
          size: 12,
          weight: 'bold'
        },
        padding: 15,
        usePointStyle: true
      }
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#3B82F6',
      borderWidth: 2,
      padding: 12,
      displayColors: true,
      callbacks: {
        label: function(context) {
          return ` ${context.dataset.label}: ${context.parsed.y} tareas`;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
        color: '#6B7280'
      },
      grid: {
        color: 'rgba(107, 114, 128, 0.1)',
        drawBorder: false
      }
    },
    x: {
      ticks: {
        color: '#6B7280'
      },
      grid: {
        display: false
      }
    }
  },
  animation: {
    duration: 2000,
    easing: 'easeInOutQuart',
    onProgress: function() {
      // Animación suave durante el progreso
    },
    onComplete: function() {
      // Animación completada
    }
  },
  hover: {
    mode: 'nearest',
    intersect: true,
    animationDuration: 400
  },
  elements: {
    line: {
      tension: 0.4,
      borderWidth: 3,
      borderCapStyle: 'round',
      borderJoinStyle: 'round',
      fill: true
    },
    point: {
      radius: 5,
      hoverRadius: 8,
      hitRadius: 10,
      borderWidth: 2,
      hoverBorderWidth: 3
    }
  }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: {
    legend: { 
      position: 'bottom',
      labels: {
        color: '#6B7280',
        font: {
          size: 12,
          weight: 'bold'
        },
        padding: 15,
        usePointStyle: true,
        pointStyle: 'circle'
      }
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#3B82F6',
      borderWidth: 2,
      padding: 12,
      callbacks: {
        label: function(context) {
          const label = context.label || '';
          const value = context.parsed || 0;
          const total = context.dataset.data.reduce((a, b) => a + b, 0);
          const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
          return ` ${label}: ${value} tareas (${percentage}%)`;
        }
      }
    }
  },
  animation: {
    animateRotate: true,
    animateScale: true,
    duration: 2000,
    easing: 'easeInOutQuart'
  },
  hover: {
    mode: 'nearest',
    animationDuration: 400
  },
  elements: {
    arc: {
      borderWidth: 3,
      borderColor: '#fff',
      hoverBorderWidth: 5,
      hoverOffset: 15
    }
  }
}

const getDaysRemaining = (date) => {
  if (!date) return 'Sin fecha'
  const days = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24))
  if (days < 0) return `Vencida hace ${Math.abs(days)}d`
  if (days === 0) return 'Vence hoy'
  return `${days}d restantes`
}

const getDaysRemainingClass = (date) => {
  if (!date) return 'bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400'
  const days = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24))
  if (days < 0) return 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20'
  if (days === 0) return 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-500/20'
  if (days <= 3) return 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-500/20'
  return 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/20'
}

const formatDate = (date) => {
  if (!date) return 'Sin fecha'
  const d = new Date(date)
  const day = d.getDate().toString().padStart(2, '0')
  const month = (d.getMonth() + 1).toString().padStart(2, '0')
  const year = d.getFullYear()
  return `${day}/${month}/${year}`
}

const getStatusClass = (status, type = 'flow') => {
  if (type === 'case') {
      const caseClasses = {
          'New': 'text-blue-600 bg-blue-50 border-blue-100',
          'Assigned': 'text-indigo-600 bg-indigo-50 border-indigo-100',
          'Pending Input': 'text-amber-600 bg-amber-50 border-amber-100',
          'Closed': 'text-emerald-600 bg-emerald-50 border-emerald-100',
          'Rejected': 'text-rose-600 bg-rose-50 border-rose-100',
      }
      return caseClasses[status] || 'text-slate-600 bg-slate-50 border-slate-100'
  }
  
  const classes = {
    active: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
    paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
    completed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
    'Pending Input': 'bg-amber-100 text-amber-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  const texts = { active: 'Activo', paused: 'Pausado', completed: 'Completado' }
  return texts[status] || status
}

const calculateProgress = (flow) => {
  if (!flow.tasks?.length) return 0
  const completed = flow.tasks.filter(t => t.status === 'completed').length
  return Math.round((completed / flow.tasks.length) * 100)
}

const handleTaskClick = (task) => {
  // Si es un caso, navegar a la vista de casos
  if (task.type === 'case') {
    router.push({ path: '/cases', query: { caseId: task.id } })
    return
  }

  // Si es una oportunidad, navegar a la vista de oportunidades
  if (task.type === 'opportunity') {
    router.push({ path: '/opportunities', query: { opportunityId: task.id } })
    return
  }

  // Si es una tarea
  const caseId = task.crm_case?.id || task.case_id
  if (caseId) {
    // Si la tarea pertenece a un caso CRM, ir a la vista de casos con el ID del caso
    router.push({ path: '/cases', query: { caseId: caseId, taskId: task.id } })
  } else if (task.flow_id) {
    // Si la tarea pertenece a un flujo, ir al detalle del flujo
    router.push(`/flows/${task.flow_id}`)
  }
  // Si es una tarea sin caso y sin flujo, no navegar (es una tarea delegada aislada)
}

const handleCaseClick = (crmCase, event) => {
  // Si el click fue en el botón de expand, no navegar
  if (event.target.closest('button')) {
    return
  }
  // Navegar al detalle del caso
  if (crmCase.id) {
    router.push({ path: '/cases', query: { caseId: crmCase.id } })
  }
}

const getStatusColor = (status) => {
  const colors = {
    'pending': 'bg-slate-400',
    'in_progress': 'bg-blue-500',
    'completed': 'bg-emerald-500',
    'cancelled': 'bg-rose-500'
  }
  return colors[status] || 'bg-slate-400'
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'pending': 'bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600',
    'in_progress': 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-500/20',
    'completed': 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/20',
    'cancelled': 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20'
  }
  return classes[status] || 'bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600'
}

const formatTaskStatus = (status) => {
  const map = {
    'pending': 'Pendiente',
    'in_progress': 'En Progreso',
    'completed': 'Completada',
    'cancelled': 'Cancelada'
  }
  return map[status] || status
}

const formatPriority = (priority) => {
  const map = {
    'low': 'Baja',
    'medium': 'Media',
    'high': 'Alta',
    'urgent': 'Urgente'
  }
  return map[priority] || priority
}

const getPriorityBadgeClass = (priority) => {
  const classes = {
    'low': 'bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600',
    'medium': 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-500/20',
    'high': 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-100 dark:border-orange-500/20',
    'urgent': 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20'
  }
  return classes[priority] || 'bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600'
}

const formatDateShort = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('es-ES', { month: 'short', day: 'numeric', year: '2-digit' })
}

const isOverdue = (endDate) => {
  if (!endDate) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const end = new Date(endDate)
  end.setHours(0, 0, 0, 0)
  return end < today
}

const scrollToDelegated = () => {
  if (delegatedSection.value) {
    delegatedSection.value.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const loadData = async () => {
  try {
    console.log('📊 [Dashboard] Starting loadData...');

    // 1. Primero cargar contenido basado en área (AWAIT para esperar respuesta)
    console.log('📊 [Dashboard] Calling fetchAreaBasedContent...');
    await dashboardStore.fetchAreaBasedContent();
    console.log('📊 [Dashboard] fetchAreaBasedContent completed', {
      cases: dashboardStore.cases.length,
      opportunities: dashboardStore.opportunities.length,
      tasks: dashboardStore.orphanTasks.length,
      userArea: dashboardStore.userArea
    });

    // 2. Luego obtener tareas delegadas (ahora userArea está seteado)
    console.log('📊 [Dashboard] Fetching delegated items...');
    if (dashboardStore.userArea === 'sales') {
      await dashboardStore.fetchDelegatedSales();
    } else {
      await dashboardStore.fetchDelegated();
    }
    console.log('📊 [Dashboard] Delegated items fetched');

    // 3. Cargar datos adicionales en paralelo
    console.log('📊 [Dashboard] Fetching flows and tasks...');
    const [flowsRes, tasksRes] = await Promise.all([
      flowsAPI.getAll(), // Los flujos por ahora los dejamos todos o según permisos de backend
      tasksAPI.getAll({ assignee_id: authStore.currentUser?.id })
    ])
    console.log('📊 [Dashboard] Flows and tasks fetched');

    const flows = flowsRes.data.data
    const tasks = tasksRes.data.data
    
    // myCases logic removed

    // Calcular datos reales para los últimos 7 días
    const last7Days = []
    const completedByDay = []
    
    for (let i = 6; i >= 0; i--) {
      const date = new Date()
      date.setDate(date.getDate() - i)
      date.setHours(0, 0, 0, 0)
      
      const nextDay = new Date(date)
      nextDay.setDate(nextDay.getDate() + 1)
      
      // Nombre del día
      const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
      last7Days.push(dayNames[date.getDay()])
      
      // Contar tareas completadas ese día
      const completedCount = tasks.filter(t => {
        if (t.status !== 'completed' || !t.updated_at) return false
        const taskDate = new Date(t.updated_at)
        return taskDate >= date && taskDate < nextDay
      }).length
      
      completedByDay.push(completedCount)
    }
    
    taskTrendData.value = {
      labels: last7Days,
      datasets: [{
        label: 'Completadas',
        data: completedByDay,
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4
      }]
    }

    // Actualizar gráficos con datos reales (solo pendientes o en progreso)
    priorityChartData.value.datasets[0].data = [
      tasks.filter(t => t.priority === 'low' && ['pending', 'in_progress'].includes(t.status)).length,
      tasks.filter(t => t.priority === 'medium' && ['pending', 'in_progress'].includes(t.status)).length,
      tasks.filter(t => t.priority === 'high' && ['pending', 'in_progress'].includes(t.status)).length,
      tasks.filter(t => t.priority === 'urgent' && ['pending', 'in_progress'].includes(t.status)).length
    ]

    console.log('📊 [Dashboard] ✅ loadData completed successfully');
  } catch (error) {
    console.error('❌ [Dashboard] Error cargando datos:', error);
    console.error('Error message:', error.message);
    console.error('Error stack:', error.stack);
  }
}

const isToday = (date) => {
  const today = new Date()
  const d = new Date(date)
  return d.toDateString() === today.toDateString()
}

const isThisWeek = (date) => {
  const d = new Date(date)
  const today = new Date()
  const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
  return d >= weekAgo && d <= today
}

const openTaskCreationModal = () => {
  showTaskCreationModal.value = true
}

const handleTaskCreated = async () => {
  // Reload dashboard data after task creation
  showTaskCreationModal.value = false
  await loadData()
}

const loadUsers = async () => {
  try {
    const response = await fetch('/api/v1/users', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
    if (response.ok) {
      const data = await response.json()
      users.value = data.data || []
    }
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

onMounted(async () => {
  await loadData()
  await loadUsers()
})
</script>