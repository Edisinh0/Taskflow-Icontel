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
              <!-- Búsqueda con debounce -->
              <div>
                <label class="block text-sm font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">
                  Buscar
                </label>
                <div class="relative">
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                  <input 
                    v-model="localSearch"
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
                  v-model="localFilters.status"
                  @change="applyFilter('status', localFilters.status)"
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
                  v-model="localFilters.priority"
                  @change="applyFilter('priority', localFilters.priority)"
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
                  v-model="localFilters.area"
                  @change="applyFilter('area', localFilters.area)"
                  class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm outline-none font-medium"
                >
                  <option value="all">Todas las áreas</option>
                  <option value="Operaciones">Operaciones</option>
                  <option value="Soporte">Soporte</option>
                  <option value="Atención al Cliente">Atención al Cliente</option>
                  <option value="Ventas">Ventas</option>
                </select>
              </div>

              <!-- Toggle Mis Casos -->
              <div class="pt-4 border-t border-slate-200 dark:border-white/5">
                <label class="flex items-center justify-between cursor-pointer group">
                  <span class="text-sm font-bold text-slate-600 dark:text-slate-300 flex items-center gap-2">
                    <UserCheck :size="16" class="text-blue-500" />
                    Solo Mis Casos
                  </span>
                  <button 
                    @click="toggleMyCases"
                    :class="[
                      'relative w-12 h-6 rounded-full transition-colors',
                      localFilters.assigned_to_me 
                        ? 'bg-blue-500' 
                        : 'bg-slate-200 dark:bg-slate-700'
                    ]"
                  >
                    <span 
                      :class="[
                        'absolute w-5 h-5 bg-white rounded-full top-0.5 transition-transform shadow-sm',
                        localFilters.assigned_to_me ? 'translate-x-6' : 'translate-x-0.5'
                      ]"
                    ></span>
                  </button>
                </label>
                <p class="text-xs text-slate-400 mt-2">Mostrar solo casos asignados a mi cuenta</p>
              </div>

              <div class="pt-4 border-t border-slate-200 dark:border-white/5">
                <button
                  @click="handleClearFilters"
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
              <p class="text-4xl font-black text-slate-800 dark:text-white mt-2">{{ casesStore.stats.total }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-bl-full -mr-4 -mt-4"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Nuevos/Abiertos</p>
              <p class="text-4xl font-black text-emerald-500 dark:text-emerald-400 mt-2">{{ casesStore.stats.open }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-3xl shadow-sm dark:shadow-lg p-6 border border-slate-200 dark:border-white/5 relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-20 h-20 bg-amber-500/5 dark:bg-amber-500/10 rounded-bl-full -mr-4 -mt-4"></div>
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Tareas de CRM</p>
              <p class="text-4xl font-black text-amber-500 dark:text-amber-400 mt-2">{{ casesStore.stats.totalTasks }}</p>
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
                  <!-- Loading skeleton inicial -->
                  <template v-if="casesStore.loading && casesStore.cases.length === 0">
                    <tr v-for="i in 5" :key="'skeleton-' + i" class="animate-pulse">
                      <td colspan="7" class="px-8 py-5 bg-slate-50/50 dark:bg-white/5"></td>
                    </tr>
                  </template>
                  
                  <!-- Empty state -->
                  <tr v-else-if="casesStore.isEmpty">
                    <td colspan="7" class="px-8 py-12 text-center">
                      <div class="flex flex-col items-center">
                        <Inbox :size="48" class="text-slate-300 dark:text-white/10 mb-4" />
                        <p class="text-slate-500 dark:text-slate-400 font-bold">No se encontraron casos</p>
                        <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Intenta con otros filtros o términos de búsqueda</p>
                      </div>
                    </td>
                  </tr>
                                   <!-- Casos Agrupados por Área -->
                  <template v-for="(areaCases, area) in groupedCases" :key="area">
                    <!-- Fila de Encabezado de Área -->
                    <tr class="bg-slate-50/50 dark:bg-white/5 border-y border-slate-100 dark:border-white/5">
                      <td colspan="7" class="px-8 py-3">
                        <div class="flex items-center gap-2">
                          <span :class="getAreaClass(area)" class="px-3 py-1 text-xs font-black uppercase tracking-widest rounded-lg border shadow-sm">
                            {{ area }}
                          </span>
                          <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            ({{ areaCases.length }} casos)
                          </span>
                        </div>
                      </td>
                    </tr>

                    <tr 
                      v-for="crmCase in areaCases" 
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
                            {{ crmCase.client?.name || 'Sin cliente' }}
                          </span>
                        </div>
                      </td>
                      <td class="px-8 py-5">
                        <div class="flex flex-col">
                          <span v-if="crmCase.assigned_user" class="text-xs font-bold text-slate-700 dark:text-white flex items-center gap-2">
                            <User :size="14" class="text-slate-400" /> {{ crmCase.assigned_user.name }}
                          </span>
                          <span v-else class="text-xs text-slate-400 italic">Sin asignar</span>
                          <span v-if="crmCase.assigned_user?.department" class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-tighter">
                            {{ crmCase.assigned_user.department }}
                          </span>
                        </div>
                      </td>
                      <td class="px-8 py-5 text-center">
                        <div class="flex flex-col items-center gap-1">
                          <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-black rounded-lg border border-slate-200 dark:border-white/10">
                            {{ crmCase.tasks_count || 0 }}
                          </span>
                          <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Tareas</span>
                        </div>
                      </td>
                      <td class="px-8 py-5">
                        <span 
                          :class="getStatusClass(crmCase.status)"
                          class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border shadow-sm"
                        >
                          {{ crmCase.status || 'Sin estado' }}
                        </span>
                      </td>
                      <td class="px-8 py-5">
                        <span 
                          :class="getPriorityClass(crmCase.priority)"
                          class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border shadow-sm"
                        >
                          {{ crmCase.priority || 'N/A' }}
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
                  </template>
                  
                  <!-- Loading more indicator -->
                  <tr v-if="casesStore.loadingMore">
                    <td colspan="7" class="px-8 py-4 text-center">
                      <div class="flex items-center justify-center gap-2">
                        <Loader2 :size="18" class="animate-spin text-blue-500" />
                        <span class="text-sm text-slate-500 font-medium">Cargando más casos...</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer con paginación e info -->
            <div class="px-8 py-4 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 flex justify-between items-center">
              <p class="text-xs font-bold text-slate-500">
                Mostrando {{ casesStore.cases.length }} de {{ casesStore.pagination.total }} casos
              </p>
              <div class="flex items-center gap-4">
                <!-- Indicador de scroll infinito -->
                <span v-if="casesStore.hasMore" class="text-xs text-slate-400">
                  Desplázate para cargar más
                </span>
                
                <!-- Botones de paginación alternativa -->
                <div class="flex gap-2">
                  <button 
                    @click="casesStore.goToPage(casesStore.pagination.current_page - 1)"
                    :disabled="casesStore.pagination.current_page === 1 || casesStore.loading"
                    class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 disabled:opacity-50 transition-all hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    <ChevronLeft :size="14" />
                  </button>
                  <span class="px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300">
                    {{ casesStore.pagination.current_page }} / {{ casesStore.pagination.last_page }}
                  </span>
                  <button 
                    @click="casesStore.goToPage(casesStore.pagination.current_page + 1)"
                    :disabled="casesStore.pagination.current_page >= casesStore.pagination.last_page || casesStore.loading"
                    class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 disabled:opacity-50 transition-all hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    <ChevronRight :size="14" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Sentinel para Infinite Scroll -->
    <div ref="infiniteScrollSentinel" class="h-4"></div>

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
                <div class="flex-1 overflow-y-auto flex flex-col">
                    
                    <!-- Alerta de Solicitud de Cierre -->
                    <div v-if="caseDetail?.closure_info?.requested" class="px-8 pt-6 pb-2">
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-2xl p-4 flex items-start gap-3">
                            <AlertCircle class="text-amber-500 shrink-0 mt-0.5" :size="20" />
                            <div class="flex-1">
                                <h4 class="font-bold text-amber-800 dark:text-amber-400 text-sm">Solicitud de Cierre Pendiente</h4>
                                <p class="text-xs text-amber-600 dark:text-amber-500 mt-1">
                                    El usuario <strong>{{ caseDetail.closure_info.requested_by?.name || 'Desconocido' }}</strong> ha solicitado cerrar este caso.
                                </p>
                                <!-- Acciones para el Creador -->
                                <div class="mt-3 flex gap-2">
                                    <button 
                                        @click="approveClosureHandler" 
                                        class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-1"
                                    >
                                        <CheckCircle :size="12" /> Aprobar y Cerrar
                                    </button>
                                    <button 
                                        @click="showRejectModal = true"
                                        class="px-3 py-1.5 bg-white border border-rose-200 text-rose-500 hover:bg-rose-50 text-xs font-bold rounded-lg transition-colors flex items-center gap-1"
                                    >
                                        <XCircle :size="12" /> Rechazar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs de Navegación -->
                    <div class="px-8 pt-4 border-b border-slate-100 dark:border-white/5 flex gap-6">
                        <button 
                            v-for="tab in [
                                { id: 'details', label: 'Detalles', icon: Building2 },
                                { id: 'tasks', label: 'Tareas', icon: ListTodo },
                                { id: 'timeline', label: 'Avances y Actividad', icon: History }
                            ]"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'pb-4 text-sm font-bold flex items-center gap-2 border-b-2 transition-colors',
                                activeTab === tab.id 
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                                    : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
                            ]"
                        >
                            <component :is="tab.icon" :size="16" />
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Contenido de Pestañas -->
                    <div class="p-8 flex-1">
                        
                        <!-- TAB: DETALLES -->
                        <div v-if="activeTab === 'details'" class="space-y-6">
                            <!-- Info Grid -->
                            <div class="grid grid-cols-2 gap-4" v-if="caseDetail">
                                <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Creado por</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                        <User :size="14" class="text-slate-400" />
                                        {{ caseDetail.original_creator_name || 'Desconocido' }}
                                    </p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Asignado a</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                        <UserCheck :size="14" class="text-slate-400" />
                                        {{ caseDetail.assigned_user?.name || caseDetail.assigned_user_name || 'Sin asignar' }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Descripción -->
                            <div v-if="caseDetail?.description" class="bg-slate-50 dark:bg-white/5 rounded-3xl p-6 border border-slate-100 dark:border-white/5">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Descripción del Caso</h4>
                                <div class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed prose prose-sm dark:prose-invert max-w-none" v-html="caseDetail.description"></div>
                            </div>

                            <!-- Botón Solicitar Cierre (para assigned user) -->
                            <div v-if="!caseDetail?.closure_info?.requested && caseDetail?.status !== 'Cerrado'" class="pt-4 border-t border-slate-100 dark:border-white/5">
                                <button 
                                    @click="requestClosureHandler"
                                    class="w-full py-3 border border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-sm font-bold flex items-center justify-center gap-2"
                                >
                                    <CheckCircle :size="16" /> Solicitar Cierre del Caso
                                </button>
                            </div>
                        </div>

                        <!-- TAB: TAREAS -->
                        <div v-else-if="activeTab === 'tasks'" class="space-y-4">
                            <!-- Header con contador y botón Nueva Tarea -->
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                    Tareas ({{ caseDetail?.tasks?.length || 0 }})
                                </h4>
                                <button
                                    @click="showTaskModal = true"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition-colors shadow-md hover:shadow-lg"
                                >
                                    <Plus :size="18" />
                                    Nueva Tarea
                                </button>
                            </div>

                            <!-- Loading skeleton -->
                            <div v-if="loadingDetail" class="space-y-3">
                                <div v-for="i in 3" :key="i" class="h-16 bg-slate-50 dark:bg-white/5 rounded-2xl animate-pulse"></div>
                            </div>

                            <!-- Empty state con botón -->
                            <div v-else-if="!caseDetail?.tasks?.length" class="text-center py-12 bg-slate-50 dark:bg-white/5 rounded-3xl border border-dashed border-slate-200 dark:border-white/10">
                                <ListTodo :size="32" class="text-slate-300 mx-auto mb-3" />
                                <p class="text-slate-400 dark:text-slate-500 font-bold text-sm mb-4">No hay tareas asociadas a este caso</p>
                                <button
                                    @click="showTaskModal = true"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors shadow-md hover:shadow-lg"
                                >
                                    <Plus :size="18" />
                                    Crear Primera Tarea
                                </button>
                            </div>

                            <!-- Lista de tareas -->
                            <div v-else class="grid grid-cols-1 gap-3">
                                <div
                                    v-for="task in caseDetail.tasks"
                                    :key="task.id"
                                    @click="openTaskDetail(task)"
                                    class="bg-white dark:bg-slate-700/50 p-4 rounded-2xl border border-slate-100 dark:border-white/10 flex items-center justify-between hover:shadow-md transition-all group cursor-pointer"
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

                        <!-- TAB: TIMELINE / AVANCES -->
                        <div v-else-if="activeTab === 'timeline'" class="space-y-6 h-full flex flex-col">
                            <!-- Input nuevo avance -->
                            <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 dark:border-slate-700">
                                <textarea 
                                    v-model="newUpdateContent"
                                    placeholder="Escribe un nuevo avance o nota..."
                                    rows="2"
                                    class="w-full bg-transparent border-none focus:ring-0 text-sm p-0 text-slate-700 dark:text-slate-200 placeholder:text-slate-400 resize-none"
                                ></textarea>

                                <!-- File Upload Indicator -->
                                <div v-if="updateAttachments.length" class="mt-2 flex flex-wrap gap-2">
                                    <div v-for="(file, idx) in updateAttachments" :key="idx" class="flex items-center gap-1.5 bg-blue-50 dark:bg-blue-500/10 px-2 py-1 rounded-md border border-blue-100 dark:border-blue-500/20 text-[10px] text-blue-600 dark:text-blue-400 font-bold">
                                        <FileIcon :size="10" /> {{ file.name }}
                                        <button @click="updateAttachments.splice(idx, 1)" class="hover:text-rose-500"><X :size="10" /></button>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-200 dark:border-slate-700/50">
                                    <div class="flex items-center gap-2">
                                        <label class="cursor-pointer p-1.5 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors text-slate-500" title="Adjuntar archivos">
                                            <input type="file" multiple class="hidden" @change="handleCaseFileChange" />
                                            <Paperclip :size="16" />
                                        </label>
                                        <span class="text-xs text-slate-400 font-semibold">Adjuntar archivos</span>
                                    </div>
                                    <button 
                                        @click="sendUpdate"
                                        :disabled="(!newUpdateContent.trim() && !updateAttachments.length) || sendingUpdate"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <Loader2 v-if="sendingUpdate" :size="12" class="animate-spin" />
                                        <Send v-else :size="12" />
                                        Registrar Avance
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de Avances -->
                            <div v-if="caseDetail?.updates?.length" class="space-y-6 relative pl-4 border-l-2 border-slate-100 dark:border-slate-800">
                                <div v-for="update in caseDetail.updates" :key="update.id" class="relative group">
                                    <!-- Punto de timeline -->
                                    <div :class="`absolute -left-[23px] top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 bg-${update.type_color}-500 shadow-sm`"></div>
                                    
                                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-white/5 p-4 shadow-sm hover:shadow-md transition-all">
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-700 dark:text-white">{{ update.user?.name }}</span>
                                                <span class="text-[10px] bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-full text-slate-500 font-mono">{{ update.formatted_date }}</span>
                                            </div>
                                            <button 
                                                v-if="update.user_id === authStore.currentUser?.id || authStore.currentUser?.role === 'admin'"
                                                @click="deleteUpdate(update.id, 'case')"
                                                class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-slate-300 hover:text-rose-500"
                                            >
                                                <Trash2 :size="14" />
                                            </button>
                                        </div>
                                        <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed prose prose-sm dark:prose-invert max-w-none whitespace-pre-line" v-html="update.content"></div>

                                        <!-- Adjuntos del Avance -->
                                        <div v-if="update.attachments?.length" class="mt-3 flex flex-wrap gap-2 pt-3 border-t border-slate-50 dark:border-white/5">
                                            <a v-for="att in update.attachments" :key="att.id" :href="att.url" target="_blank" 
                                               class="flex items-center gap-1.5 bg-slate-50 dark:bg-white/5 px-2 py-1 rounded-md border border-slate-100 dark:border-white/5 text-[10px] text-slate-500 dark:text-slate-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 hover:text-blue-600 transition-all font-bold">
                                                <Paperclip :size="10" /> {{ att.name }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="flex-1 flex flex-col items-center justify-center text-center py-8">
                                <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-full mb-3">
                                    <MessageCircle :size="24" class="text-slate-300" />
                                </div>
                                <p class="text-slate-400 font-bold text-sm">No hay avances registrados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal de Rechazo (Overlay) -->
                <div v-if="showRejectModal" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-8">
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-2xl w-full max-w-md border border-slate-200">
                        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-2">Rechazar Cierre</h3>
                        <p class="text-sm text-slate-500 mb-4">Es obligatorio indicar la razón del rechazo para que el usuario pueda corregirlo.</p>
                        
                        <textarea 
                            v-model="rejectionReason"
                            rows="3"
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl p-3 text-sm focus:ring-2 focus:ring-rose-500 outline-none resize-none mb-4"
                            placeholder="Describe el motivo..."
                        ></textarea>
                        
                        <div class="flex justify-end gap-2">
                            <button @click="showRejectModal = false" class="px-4 py-2 text-slate-500 font-bold text-sm hover:bg-slate-100 rounded-xl">Cancelar</button>
                            <button @click="rejectClosureHandler" class="px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white font-bold text-sm rounded-xl">Confirmar Rechazo</button>
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
                                     
    <!-- Modal de Detalle de Tarea -->
    <Transition name="modal">
        <div v-if="selectedTask" class="fixed inset-0 z-[70] overflow-y-auto px-4 py-8 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-white/5 w-full max-w-2xl overflow-hidden flex flex-col relative scale-100 transition-all duration-300">
                
                <!-- Header del Modal -->
                <div class="p-8 border-b border-slate-100 dark:border-white/5 relative bg-slate-50/50 dark:bg-white/5">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">Detalle de Tarea</span>
                                <span :class="taskPriorityBadge(selectedTask.priority)" class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-full border border-current/20">{{ selectedTask.priority }}</span>
                            </div>
                            <h2 class="text-2xl font-black text-slate-800 dark:text-white leading-tight">
                                {{ selectedTask.title }}
                            </h2>
                        </div>
                        <button @click="selectedTask = null" class="p-3 bg-white dark:bg-slate-700 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-2xl shadow-sm border border-slate-100 dark:border-white/10 transition-all">
                            <X :size="20" />
                        </button>
                     </div>
                 </div>


                <!-- Cuerpo del Modal -->
                <div class="flex-1 overflow-y-auto flex flex-col">
                    
                    <!-- Tabs de Tarea -->
                    <div class="px-8 pt-4 border-b border-slate-100 dark:border-white/5 flex gap-6">
                        <button 
                            @click="taskActiveTab = 'details'"
                            :class="[
                                'pb-4 text-sm font-bold flex items-center gap-2 border-b-2 transition-colors',
                                taskActiveTab === 'details' 
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                                    : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
                            ]"
                        >
                            <Building2 :size="16" /> Detalles
                        </button>
                        <button 
                            @click="taskActiveTab = 'timeline'"
                            :class="[
                                'pb-4 text-sm font-bold flex items-center gap-2 border-b-2 transition-colors',
                                taskActiveTab === 'timeline' 
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
                                    : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
                            ]"
                        >
                            <History :size="16" /> Avances
                        </button>
                    </div>

                    <div class="p-8 space-y-6 overflow-y-auto max-h-[60vh] relative">
                        <!-- Loading Overlay -->
                        <div v-if="loadingTask" class="absolute inset-0 bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm z-10 flex items-center justify-center">
                            <Loader2 class="animate-spin text-blue-500" :size="32" />
                        </div>

                        <!-- TAB: DETALLES -->
                        <div v-if="taskActiveTab === 'details'" class="space-y-6">
                            <!-- Estado -->
                            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-white/5 rounded-2xl border border-slate-100 dark:border-white/5">
                                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Estado</span>
                                <div class="flex items-center gap-2">
                                    <Loader2 v-if="updatingTask" class="animate-spin text-blue-500" :size="14" />
                                    <select 
                                        :value="selectedTask.status" 
                                        @change="updateTask({ status: $event.target.value })"
                                        class="bg-transparent border-none text-xs font-black uppercase tracking-widest focus:ring-0 cursor-pointer appearance-none text-right px-0"
                                        :class="taskStatusTextClass(selectedTask.status)"
                                    >
                                        <option value="pending">Pendiente</option>
                                        <option value="in_progress">En proceso</option>
                                        <option value="completed">Completada</option>
                                        <option value="cancelled">Cancelada</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Botón Completar Rápido -->
                            <button 
                                v-if="selectedTask.status !== 'completed'"
                                @click="updateTask({ status: 'completed' })"
                                :disabled="updatingTask"
                                class="w-full py-4 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white font-black rounded-2xl shadow-lg shadow-emerald-500/20 transition-all text-sm uppercase tracking-widest flex items-center justify-center gap-2"
                            >
                                <CheckCircle v-if="!updatingTask" :size="20" />
                                <Loader2 v-else class="animate-spin" :size="20" />
                                Completar Tarea
                            </button>

                            <!-- Descripción -->
                            <div v-if="selectedTask.description" class="space-y-2">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Descripción</h4>
                                <div class="text-sm text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-white/5 p-4 rounded-2xl border border-slate-100 dark:border-white/5 leading-relaxed" v-html="selectedTask.description"></div>
                            </div>

                            <!-- Grid de Información -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-50 dark:bg-white/5 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Asignado a</p>
                                    <div class="flex items-center gap-2">
                                        <UserCheck :size="14" class="text-slate-400" />
                                        <select 
                                            :value="selectedTask.assignee_id" 
                                            @change="updateTask({ assignee_id: $event.target.value })"
                                            class="w-full bg-transparent border-none text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-0 cursor-pointer p-0 appearance-none"
                                        >
                                            <option :value="null">Sin asignar</option>
                                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="bg-slate-50 dark:bg-white/5 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Progreso</p>
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500 transition-all" :style="`width: ${selectedTask.progress}%`"></div>
                                        </div>
                                        <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ selectedTask.progress }}%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tiempos -->
                            <div class="bg-slate-50 dark:bg-white/5 p-6 rounded-3xl border border-slate-100 dark:border-white/5 space-y-4">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                    <Clock :size="14" /> Tiempos y Fechas
                                </h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-slate-400 font-bold">Inicio Estimado</span>
                                            <span class="text-slate-700 dark:text-slate-200 font-bold border-b border-blue-500/30 pb-0.5">{{ formatDateWithHour(selectedTask.estimated_start_at) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-slate-400 font-bold">Término Estimado</span>
                                            <input 
                                                type="date"
                                                :value="formatDateForInput(selectedTask.estimated_end_at || selectedTask.due_date)"
                                                @change="updateTask({ estimated_end_at: $event.target.value })"
                                                class="bg-transparent border-none text-right font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 text-xs border-b border-blue-500/30"
                                            />
                                        </div>
                                        <div v-if="selectedTask.due_date || selectedTask.sla_due_date" class="flex justify-between items-center text-xs">
                                            <span class="text-rose-400 font-black uppercase tracking-tighter">Fecha Límite (SLA)</span>
                                            <span class="text-rose-600 dark:text-rose-400 font-black">{{ formatDateWithHour(selectedTask.due_date || selectedTask.sla_due_date) }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-slate-400 font-bold italic">Inicio Real</span>
                                            <span class="text-slate-700 dark:text-slate-200 font-bold">{{ formatDateWithHour(selectedTask.actual_start_at) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-slate-400 font-bold italic">Término Real</span>
                                            <span class="text-slate-700 dark:text-slate-200 font-bold">{{ formatDateWithHour(selectedTask.actual_end_at) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs pt-1">
                                            <span class="text-amber-500 font-black uppercase tracking-tighter">Restante</span>
                                            <span :class="getDaysRemainingClass(selectedTask.estimated_end_at || selectedTask.due_date)" class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase">
                                                {{ getDaysRemaining(selectedTask.estimated_end_at || selectedTask.due_date) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-[10px] text-slate-400 flex justify-between px-2 italic">
                                <span>Creado: {{ formatDateWithHour(selectedTask.created_at) }}</span>
                                <span v-if="selectedTask.sweetcrm_synced_at">Sincronizado: {{ formatDateWithHour(selectedTask.sweetcrm_synced_at) }}</span>
                            </div>
                        </div>

                        <!-- TAB: AVANCES (Timeline) -->
                        <div v-else-if="taskActiveTab === 'timeline'" class="space-y-6">
                            <!-- Input nuevo avance -->
                            <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 dark:border-slate-700">
                                <textarea 
                                    v-model="newTaskUpdateContent"
                                    placeholder="Escribe un avance para esta tarea..."
                                    rows="2"
                                    class="w-full bg-transparent border-none focus:ring-0 text-sm p-0 text-slate-700 dark:text-slate-200 placeholder:text-slate-400 resize-none"
                                ></textarea>
                                
                                <!-- File Upload Indicator -->
                                <div v-if="taskUpdateAttachments.length" class="mt-2 flex flex-wrap gap-2">
                                    <div v-for="(file, idx) in taskUpdateAttachments" :key="idx" class="flex items-center gap-1.5 bg-blue-50 dark:bg-blue-500/10 px-2 py-1 rounded-md border border-blue-100 dark:border-blue-500/20 text-[10px] text-blue-600 dark:text-blue-400 font-bold">
                                        <FileIcon :size="10" /> {{ file.name }}
                                        <button @click="taskUpdateAttachments.splice(idx, 1)" class="hover:text-rose-500"><X :size="10" /></button>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-200 dark:border-slate-700/50">
                                    <div class="flex items-center gap-2">
                                        <label class="cursor-pointer p-1.5 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors text-slate-500" title="Adjuntar archivos">
                                            <input type="file" multiple class="hidden" @change="handleTaskFileChange" />
                                            <Paperclip :size="16" />
                                        </label>
                                        <span class="text-xs text-slate-400 font-semibold">Adjuntar evidencias</span>
                                    </div>
                                    <button 
                                        @click="sendTaskUpdate"
                                        :disabled="(!newTaskUpdateContent.trim() && !taskUpdateAttachments.length) || sendingTaskUpdate"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <Loader2 v-if="sendingTaskUpdate" :size="12" class="animate-spin" />
                                        <Send v-else :size="12" />
                                        Registrar Avance
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de Avances -->
                            <div v-if="selectedTask.updates?.length" class="space-y-6 relative pl-4 border-l-2 border-slate-100 dark:border-slate-800">
                                <div v-for="update in selectedTask.updates" :key="update.id" class="relative group">
                                    <div class="absolute -left-[23px] top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 bg-blue-500 shadow-sm"></div>
                                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-white/5 p-4 shadow-sm group-hover:shadow-md transition-all">
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-700 dark:text-white">{{ update.user?.name }}</span>
                                                <span class="text-[10px] bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-full text-slate-400 font-mono">{{ update.formatted_date || formatDateWithHour(update.created_at) }}</span>
                                            </div>
                                            <button 
                                                v-if="update.user_id === authStore.currentUser?.id || authStore.currentUser?.role === 'admin'"
                                                @click="deleteUpdate(update.id, 'task')"
                                                class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-slate-300 hover:text-rose-500"
                                            >
                                                <Trash2 :size="14" />
                                            </button>
                                        </div>
                                        <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed prose prose-sm dark:prose-invert max-w-none whitespace-pre-line" v-html="update.content"></div>
                                        
                                        <!-- Adjuntos del Avance -->
                                        <div v-if="update.attachments?.length" class="mt-3 flex flex-wrap gap-2 pt-3 border-t border-slate-50 dark:border-white/5">
                                            <a v-for="att in update.attachments" :key="att.id" :href="att.url" target="_blank" 
                                               class="flex items-center gap-1.5 bg-slate-50 dark:bg-white/5 px-2 py-1 rounded-md border border-slate-100 dark:border-white/5 text-[10px] text-slate-500 dark:text-slate-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 hover:text-blue-600 transition-all font-bold">
                                                <Paperclip :size="10" /> {{ att.name }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-12">
                                <MessageCircle :size="32" class="text-slate-300 mx-auto mb-3" />
                                <p class="text-slate-400 font-bold text-sm">No hay avances en esta tarea</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="p-8 border-t border-slate-100 dark:border-white/5 flex justify-end bg-slate-50/50 dark:bg-white/5">
                    <button 
                        @click="selectedTask = null"
                        class="px-8 py-3 bg-blue-600 text-white font-black rounded-2xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all text-sm uppercase tracking-widest"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal de creación de tarea para casos -->
    <TaskCreateModal
      :isOpen="showTaskModal"
      :parentId="String(selectedCase?.id)"
      :parentName="caseDetail?.subject || selectedCase?.name || null"
      parentType="Cases"
      @close="showTaskModal = false"
      @task-created="handleTaskCreated"
      @success="handleTaskCreationSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useCasesStore } from '@/stores/cases'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import AppNavbar from '@/components/AppNavbar.vue'
import TaskCreateModal from '@/components/TaskCreateModal.vue'
import {
  Search,
  Filter,
  Building2,
  Inbox,
  ExternalLink,
  X,
  ListTodo,
  User,
  Loader2,
  ChevronLeft,
  ChevronRight,
  UserCheck,
  Clock,
  MessageCircle,
  Send,
  CheckCircle,
  XCircle,
  History,
  AlertCircle,
  Paperclip,
  Trash2,
  FileIcon,
  Plus
} from 'lucide-vue-next'

// Store
const casesStore = useCasesStore()
const authStore = useAuthStore()
const route = useRoute()

// Local state
const users = ref([])
const localSearch = ref('')
const localFilters = ref({
  status: 'all',
  priority: 'all',
  area: 'all',
  assigned_to_me: false
})

const selectedCase = ref(null)
const selectedTask = ref(null)
const caseDetail = ref(null)
const loadingDetail = ref(false)
const loadingTask = ref(false)
const taskActiveTab = ref('details') // details, timeline
const newTaskUpdateContent = ref('')
const sendingTaskUpdate = ref(false)
const updatingTask = ref(false)
const updateAttachments = ref([])
const taskUpdateAttachments = ref([])
const showTaskModal = ref(false)

// Agrupar casos por área
const groupedCases = computed(() => {
  const groups = {}
  
  casesStore.cases.forEach(c => {
    const area = c.assigned_user?.department || 'Sin Área'
    if (!groups[area]) {
      groups[area] = []
    }
    groups[area].push(c)
  })
  
  return groups
})

// Colores para las áreas
const getAreaClass = (area) => {
  const colors = {
    'Soporte': 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
    'Comercial': 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
    'Finanzas': 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
    'Operaciones': 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
    'Sin Área': 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-500/10 dark:text-slate-400 dark:border-slate-500/20'
  }
  return colors[area] || colors['Sin Área']
}

const taskStatusTextClass = (status) => {
  const classes = {
    'pending': 'text-slate-500',
    'in_progress': 'text-blue-500',
    'completed': 'text-emerald-500',
    'cancelled': 'text-rose-500'
  }
  return classes[status] || 'text-slate-400'
}

const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  try {
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return ''
    return date.toISOString().split('T')[0]
  } catch (e) {
    return ''
  }
}

// Estado para detalle de caso
const activeTab = ref('details') // details, tasks, timeline
const newUpdateContent = ref('')
const sendingUpdate = ref(false)
const showRejectModal = ref(false)
const rejectionReason = ref('')

// Infinite Scroll
const infiniteScrollSentinel = ref(null)
let observer = null

// Debounce para búsqueda
let searchTimeout = null
watch(localSearch, (newValue) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    casesStore.setFilter('search', newValue)
  }, 400) // 400ms debounce
})

// Aplicar filtros
const applyFilter = (key, value) => {
  casesStore.setFilter(key, value)
}

const handleClearFilters = () => {
  localSearch.value = ''
  localFilters.value = {
    status: 'all',
    priority: 'all',
    area: 'all',
    assigned_to_me: false
  }
  casesStore.clearFilters()
}

// Toggle para mostrar solo mis casos
const toggleMyCases = () => {
  localFilters.value.assigned_to_me = !localFilters.value.assigned_to_me
  casesStore.setFilter('assigned_to_me', localFilters.value.assigned_to_me)
}

const updateTask = async (data) => {
  if (!selectedTask.value) return
  
  updatingTask.value = true
  try {
    const res = await api.put(`/tasks/${selectedTask.value.id}`, data)
    const updatedTask = res.data.data
    
    // Actualizar en el estado local
    selectedTask.value = { ...selectedTask.value, ...updatedTask }
    
    // Actualizar en la lista de tareas del caso (caseDetail)
    if (caseDetail.value?.tasks) {
      const idx = caseDetail.value.tasks.findIndex(t => t.id === updatedTask.id)
      if (idx !== -1) {
        caseDetail.value.tasks[idx] = { ...caseDetail.value.tasks[idx], ...updatedTask }
      }
    }
  } catch (error) {
    console.error('Error updating task:', error)
    alert(error.response?.data?.message || 'Error al actualizar la tarea')
  } finally {
    updatingTask.value = false
  }
}

// Abrir detalle de tarea
const openTaskDetail = async (task) => {
  selectedTask.value = task
  loadingTask.value = true
  taskActiveTab.value = 'details'

  try {
    const res = await api.get(`/tasks/${task.id}`)
    selectedTask.value = res.data.data || res.data
  } catch (error) {
    console.error('Error fetching task detail:', error)
  } finally {
    loadingTask.value = false
  }
}

const handleTaskCreated = (newTask) => {
  // Validar que newTask es válido y contiene datos
  if (!newTask || typeof newTask !== 'object' || !newTask.id) {
    console.error('Invalid task data received:', newTask)
    return
  }

  // Agregar tarea a la lista de tareas del caso
  if (caseDetail.value) {
    // Inicializar tasks array si no existe
    if (!Array.isArray(caseDetail.value.tasks)) {
      caseDetail.value.tasks = []
    }
    // Verificar que no sea un duplicado
    const isDuplicate = caseDetail.value.tasks.some(t => t.id === newTask.id)
    if (!isDuplicate) {
      caseDetail.value.tasks.unshift(newTask)
    }
  }
  showTaskModal.value = false
}

/**
 * Handler para evento 'success' emitido por TaskCreateModal
 * Dispara un toast de éxito y opcionalmente refresca la lista
 */
const handleTaskCreationSuccess = (successData) => {
  console.log('Task created successfully:', successData)
  // Aquí se puede agregar lógica para mostrar un toast de éxito
  // O disparar eventos de analytics
  // Por ahora solo registramos en consola
}

// Detalle de caso
const showCaseDetail = async (crmCase) => {
  // Si el caso tiene información completa, usarla; si no, mostrar loading
  selectedCase.value = crmCase.case_number ? crmCase : { id: crmCase.id, case_number: '...', subject: 'Cargando...', status: '' }
  loadingDetail.value = true
  caseDetail.value = null
  activeTab.value = 'details' // Reset tab

  try {
    const res = await api.get(`/cases/${crmCase.id}`)
    const detail = res.data.data || res.data

    // Actualizar el caso seleccionado con la información completa del API
    selectedCase.value = {
      ...selectedCase.value,
      case_number: detail.case_number,
      subject: detail.subject,
      status: detail.status,
      client: detail.client,
      assigned_user: detail.assigned_user,
    }

    caseDetail.value = detail
  } catch (error) {
    console.error('Error fetching case detail:', error)
    // Mostrar error en el modal
    selectedCase.value = {
      ...selectedCase.value,
      subject: 'Error al cargar el caso',
    }
  } finally {
    loadingDetail.value = false
  }
}

// Acciones de Caso
const handleCaseFileChange = (e) => {
  updateAttachments.value = Array.from(e.target.files)
}

const handleTaskFileChange = (e) => {
  taskUpdateAttachments.value = Array.from(e.target.files)
}

const sendUpdate = async () => {
  if (!newUpdateContent.value.trim() && updateAttachments.value.length === 0) return
  
  sendingUpdate.value = true
  const formData = new FormData()
  formData.append('content', newUpdateContent.value)
  updateAttachments.value.forEach(file => {
    formData.append('attachments[]', file)
  })

  try {
    const res = await api.post(`/cases/${selectedCase.value.id}/updates`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    // Agregar update a la lista local
    if (caseDetail.value) {
      if (!caseDetail.value.updates) caseDetail.value.updates = []
      caseDetail.value.updates.unshift(res.data.update)
    }
    
    newUpdateContent.value = ''
    updateAttachments.value = []
  } catch (error) {
    console.error('Error sending update:', error)
    alert('Error al enviar el avance')
  } finally {
    sendingUpdate.value = false
  }
}

const sendTaskUpdate = async () => {
  if (!newTaskUpdateContent.value.trim() && taskUpdateAttachments.value.length === 0) return
  
  sendingTaskUpdate.value = true
  const formData = new FormData()
  formData.append('content', newTaskUpdateContent.value)
  taskUpdateAttachments.value.forEach(file => {
    formData.append('attachments[]', file)
  })

  try {
    const res = await api.post(`/tasks/${selectedTask.value.id}/updates`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    if (!selectedTask.value.updates) selectedTask.value.updates = []
    selectedTask.value.updates.unshift(res.data.update)
    
    newTaskUpdateContent.value = ''
    taskUpdateAttachments.value = []
  } catch (error) {
    console.error('Error sending task update:', error)
    alert('Error al enviar el avance de la tarea')
  } finally {
    sendingTaskUpdate.value = false
  }
}

const deleteUpdate = async (updateId, type = 'case') => {
  if (!confirm('¿Estás seguro de eliminar este avance?')) return
  
  try {
    await api.delete(`/updates/${updateId}`)
    
    // Eliminar de la lista local
    if (type === 'case' && caseDetail.value?.updates) {
      caseDetail.value.updates = caseDetail.value.updates.filter(u => u.id !== updateId)
    } else if (type === 'task' && selectedTask.value?.updates) {
      selectedTask.value.updates = selectedTask.value.updates.filter(u => u.id !== updateId)
    }
  } catch (error) {
    console.error('Error deleting update:', error)
    alert(error.response?.data?.message || 'Error al eliminar el avance')
  }
}

const requestClosureHandler = async () => {
  if (!confirm('¿Estás seguro de solicitar el cierre de este caso?')) return

  try {
    const response = await api.post(`/cases/${selectedCase.value.id}/request-closure`, {
      reason: 'Solicitud de cierre del caso',
      completion_percentage: 100
    })

    if (response.data.success) {
      // Recargar detalle para actualizar estado
      await showCaseDetail(selectedCase.value)
      alert('Solicitud enviada a Servicio al Cliente')
    } else {
      alert(response.data.message || 'Error al solicitar cierre')
    }
  } catch (error) {
    console.error('Error requesting closure:', error)
    alert(error.response?.data?.message || 'Error al solicitar cierre')
  }
}

const approveClosureHandler = async () => {
  if (!confirm('¿Aprobar cierre y finalizar el caso?')) return

  try {
    // 1. Obtener ID de la solicitud de cierre
    const closureResponse = await api.get(`/cases/${selectedCase.value.id}/closure-request`)
    const closureRequest = closureResponse.data.closure_request

    if (!closureRequest) {
      alert('No se encontró solicitud de cierre')
      return
    }

    // 2. Aprobar usando el ID de la solicitud
    const response = await api.post(`/closure-requests/${closureRequest.id}/approve`)

    if (response.data.success) {
      await showCaseDetail(selectedCase.value)
      // También actualizar en la lista principal si es posible
      const index = casesStore.cases.findIndex(c => c.id === selectedCase.value.id)
      if (index !== -1) {
        casesStore.cases[index].status = 'Cerrado'
      }
      alert('Caso cerrado exitosamente')
    }
  } catch (error) {
    console.error('Error approving closure:', error)
    alert(error.response?.data?.message || 'Error al aprobar cierre')
  }
}

const rejectClosureHandler = async () => {
  if (!rejectionReason.value.trim()) {
    alert('Debes indicar una razón para el rechazo')
    return
  }

  try {
    // 1. Obtener ID de la solicitud de cierre
    const closureResponse = await api.get(`/cases/${selectedCase.value.id}/closure-request`)
    const closureRequest = closureResponse.data.closure_request

    if (!closureRequest) {
      alert('No se encontró solicitud de cierre')
      return
    }

    // 2. Rechazar usando el ID de la solicitud
    const response = await api.post(`/closure-requests/${closureRequest.id}/reject`, {
      rejection_reason: rejectionReason.value
    })

    if (response.data.success) {
      showRejectModal.value = false
      rejectionReason.value = ''
      await showCaseDetail(selectedCase.value)
      alert('Solicitud de cierre rechazada')
    }
  } catch (error) {
    console.error('Error rejecting closure:', error)
    alert(error.response?.data?.message || 'Error al rechazar cierre')
  }
}

const uniqueStatuses = [
  'Nuevo', 'Asignado', 'Cerrado', 'Pendiente Datos', 'Rechazado', 'Duplicado'
]

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

const formatDateWithHour = (date) => {
  if (!date) return 'Sin fecha'
  const d = new Date(date)
  const day = d.getDate().toString().padStart(2, '0')
  const month = (d.getMonth() + 1).toString().padStart(2, '0')
  const year = d.getFullYear()
  const hours = d.getHours().toString().padStart(2, '0')
  const minutes = d.getMinutes().toString().padStart(2, '0')
  return `${day}/${month}/${year} ${hours}:${minutes}`
}

const getDaysRemaining = (date) => {
  if (!date) return 'Sin fecha'
  const target = new Date(date)
  const today = new Date()
  const diffTime = target - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) return `Vencido (${Math.abs(diffDays)}d)`
  if (diffDays === 0) return 'Vence hoy'
  return `${diffDays}d`
}

const getDaysRemainingClass = (date) => {
  if (!date) return 'bg-slate-100 text-slate-400 dark:bg-slate-800'
  const target = new Date(date)
  const today = new Date()
  const diffTime = target - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays < 0) return 'bg-rose-500 text-white'
  if (diffDays <= 2) return 'bg-amber-500 text-white'
  return 'bg-emerald-500 text-white'
}

const taskPriorityBadge = (priority) => {
  if (priority === 'high') return 'bg-rose-500/10 text-rose-500 border-rose-500/20'
  if (priority === 'medium') return 'bg-amber-500/10 text-amber-500 border-amber-500/20'
  return 'bg-slate-500/10 text-slate-500 border-slate-500/20'
}

// Setup Infinite Scroll Observer
const setupInfiniteScroll = () => {
  if (!infiniteScrollSentinel.value) return
  
  observer = new IntersectionObserver(
    (entries) => {
      const [entry] = entries
      if (entry.isIntersecting && casesStore.hasMore && !casesStore.loadingMore) {
        casesStore.loadMore()
      }
    },
    {
      rootMargin: '100px',
      threshold: 0.1
    }
  )
  
  observer.observe(infiniteScrollSentinel.value)
}

// Observar cambios en la URL para abrir tareas automáticamente
watch(() => [route.query.caseId, route.query.taskId], async ([newCaseId, newTaskId]) => {
  if (newCaseId) {
    const caseId = parseInt(newCaseId)
    const taskId = newTaskId ? parseInt(newTaskId) : null

    // Si no tenemos el detalle o es un caso diferente, cargarlo
    if (!caseDetail.value || caseDetail.value.id !== caseId) {
      // Buscar primero en los casos ya cargados del store
      let crmCase = casesStore.cases.find(c => c.id === caseId)

      // Si no está en el store, usar objeto mínimo (el API lo completará)
      if (!crmCase) {
        crmCase = { id: caseId }
      }

      await showCaseDetail(crmCase)
    }

    // Si tenemos taskId, intentar abrirla después de cargar el detalle
    if (taskId && caseDetail.value?.tasks) {
      const task = caseDetail.value.tasks.find(t => t.id === taskId)
      if (task) {
        openTaskDetail(task)
      }
    }
  }
}, { immediate: true })

onMounted(async () => {
  // Cargar datos iniciales
  await Promise.all([
    casesStore.fetchCases(),
    casesStore.fetchStats(),
    api.get('/users').then(res => users.value = res.data.data)
  ])
  
  // Setup infinite scroll después de montar
  setupInfiniteScroll()
})

onUnmounted(() => {
  // Limpiar observer
  if (observer) {
    observer.disconnect()
  }
  // Limpiar timeout
  clearTimeout(searchTimeout)
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
