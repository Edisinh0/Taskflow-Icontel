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
            <option value="Levantamiento">Levantamiento</option>
            <option value="Proposal/Price Quote">Propuesta / Cotización</option>
            <option value="Negotiation/Review">Negociación</option>
            <option value="Proyecto">Proyecto</option>
            <option value="Firmar_Contrato">Firmar Contrato</option>
            <option value="Facturacion">Facturación</option>
            <option value="Facturado">Facturado</option>
            <option value="Closed Won">Cerrada Ganada</option>
            <option value="Closed Lost">Cerrada Perdida</option>
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
               @click="showOpportunityDetail(opp)"
               class="w-full flex items-center justify-center gap-2 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-500/20 active:scale-95 transition-all"
             >
               <Eye class="w-5 h-5" />
               Ver Detalles
             </button>
             
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

    <!-- Modal de Detalle de Oportunidad -->
    <Transition name="modal">
      <div v-if="selectedOpportunity" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-8 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-white/5 w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col relative scale-100 transition-all duration-300">
          
          <!-- Header del Modal -->
          <div class="p-8 border-b border-slate-100 dark:border-white/5 relative bg-slate-50/50 dark:bg-white/5">
            <div class="flex justify-between items-start">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span :class="getStageClass(selectedOpportunity.sales_stage)" class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-full border">
                    {{ translateStage(selectedOpportunity.sales_stage) }}
                  </span>
                </div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white leading-tight">
                  {{ selectedOpportunity.name }}
                </h2>
                <p class="text-slate-500 dark:text-slate-400 font-bold mt-2 flex items-center gap-2">
                  <Building2 :size="16" />
                  {{ selectedOpportunity.client?.name || 'Cliente no vinculado' }}
                </p>
              </div>
              <button @click="selectedOpportunity = null" class="p-3 bg-white dark:bg-slate-700 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-2xl shadow-sm border border-slate-100 dark:border-white/10 transition-all">
                <X :size="20" />
              </button>
            </div>
          </div>

          <!-- Cuerpo del Modal -->
          <div class="flex-1 overflow-y-auto flex flex-col">
            
            <!-- Tabs de Navegación -->
            <div class="px-8 pt-4 border-b border-slate-100 dark:border-white/5 flex gap-6">
              <button 
                v-for="tab in [
                  { id: 'details', label: 'Detalles', icon: Building2 },
                  { id: 'tasks', label: 'Tareas', icon: ListTodo },
                  { id: 'timeline', label: 'Avances', icon: History }
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
                <div class="grid grid-cols-2 gap-4" v-if="opportunityDetail">
                  <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Monto</p>
                    <p class="text-lg font-black text-slate-700 dark:text-slate-200">{{ formatCurrency(opportunityDetail.amount) }}</p>
                  </div>
                  <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Cierre Esperado</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ formatDate(opportunityDetail.expected_closed_date) }}</p>
                  </div>
                  <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Etapa</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ translateStage(opportunityDetail.sales_stage) }}</p>
                  </div>
                  <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Moneda</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ opportunityDetail.currency }}</p>
                  </div>
                </div>

                <!-- Descripción -->
                <div v-if="opportunityDetail?.description" class="bg-slate-50 dark:bg-white/5 rounded-3xl p-6 border border-slate-100 dark:border-white/5">
                  <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Descripción</h4>
                  <div class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed prose prose-sm dark:prose-invert max-w-none" v-html="opportunityDetail.description"></div>
                </div>

                <!-- Quotes si existen -->
                <div v-if="opportunityDetail?.quotes?.length" class="bg-slate-50 dark:bg-white/5 rounded-3xl p-6 border border-slate-100 dark:border-white/5">
                  <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Cotizaciones</h4>
                  <div class="space-y-2">
                    <div v-for="quote in opportunityDetail.quotes" :key="quote.id" class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-white/5">
                      <div>
                        <p class="text-sm font-bold text-slate-800 dark:text-white">{{ quote.name || 'Cotización' }}</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Estado: {{ quote.status || 'Sin estado' }}</p>
                      </div>
                      <span class="text-lg font-black text-slate-700 dark:text-slate-200">{{ formatCurrency(quote.amount) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- TAB: TAREAS -->
              <div v-else-if="activeTab === 'tasks'" class="space-y-4">
                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">
                    Tareas ({{ opportunityDetail?.tasks?.length || 0 }})
                  </h4>
                  <button
                    @click="showTaskModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition-colors shadow-md hover:shadow-lg"
                  >
                    <Plus :size="18" />
                    Nueva Tarea
                  </button>
                </div>

                <div v-if="loadingDetail" class="space-y-3">
                  <div v-for="i in 3" :key="i" class="h-16 bg-slate-50 dark:bg-white/5 rounded-2xl animate-pulse"></div>
                </div>

                <div v-else-if="!opportunityDetail?.tasks?.length" class="text-center py-12 bg-slate-50 dark:bg-white/5 rounded-3xl border border-dashed border-slate-200 dark:border-white/10">
                  <ListTodo :size="32" class="text-slate-300 mx-auto mb-3" />
                  <p class="text-slate-400 dark:text-slate-500 font-bold text-sm mb-4">No hay tareas asociadas a esta oportunidad</p>
                  <button
                    @click="showTaskModal = true"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors shadow-md hover:shadow-lg"
                  >
                    <Plus :size="18" />
                    Crear Primera Tarea
                  </button>
                </div>

                <div v-else class="grid grid-cols-1 gap-3">
                  <div
                    v-for="task in opportunityDetail.tasks"
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

                  <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-200 dark:border-slate-700/50">
                    <span class="text-xs text-slate-400 font-semibold">Registra tus avances aquí</span>
                    <button 
                      @click="sendUpdate"
                      :disabled="!newUpdateContent.trim() || sendingUpdate"
                      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <Send v-if="!sendingUpdate" :size="12" />
                      <Loader2 v-else :size="12" class="animate-spin" />
                      Registrar Avance
                    </button>
                  </div>
                </div>

                <!-- Lista de Avances -->
                <div v-if="opportunityDetail?.updates?.length" class="space-y-6 relative pl-4 border-l-2 border-slate-100 dark:border-slate-800">
                  <div v-for="update in opportunityDetail.updates" :key="update.id" class="relative group">
                    <div class="absolute -left-[23px] top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 bg-blue-500 shadow-sm"></div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-white/5 p-4 shadow-sm hover:shadow-md transition-all">
                      <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                          <span class="text-xs font-bold text-slate-700 dark:text-white">{{ update.user?.name }}</span>
                          <span class="text-[10px] bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-full text-slate-500 font-mono">{{ formatDateWithHour(update.created_at) }}</span>
                        </div>
                      </div>
                      <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed prose prose-sm dark:prose-invert max-w-none whitespace-pre-line" v-html="update.content"></div>
                    </div>
                  </div>
                </div>

                <div v-else class="flex-1 flex flex-col items-center justify-center text-center py-8">
                  <MessageCircle :size="24" class="text-slate-300 mb-3" />
                  <p class="text-slate-400 font-bold text-sm">No hay avances registrados</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer del Modal -->
          <div class="p-8 border-t border-slate-100 dark:border-white/5 flex justify-end gap-3 bg-slate-50/50 dark:bg-white/5">
            <button 
              @click="selectedOpportunity = null"
              class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-2xl border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all text-sm"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal de creación de tarea para oportunidades -->
    <TaskCreateModal
      :isOpen="showTaskModal"
      :parentId="String(selectedOpportunity?.id)"
      parentType="Opportunities"
      @close="showTaskModal = false"
      @task-created="handleTaskCreated"
    />

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
              
              <!-- TAB: DETALLES -->
              <div v-if="taskActiveTab === 'details'" class="space-y-6">
                <!-- Estado -->
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-white/5 rounded-2xl border border-slate-100 dark:border-white/5">
                  <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Estado</span>
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
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ selectedTask.assignee?.name || 'Sin asignar' }}</p>
                  </div>
                  <div class="bg-slate-50 dark:bg-white/5 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Progreso</p>
                    <div class="flex items-center gap-3">
                      <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 transition-all" :style="`width: ${selectedTask.progress || 0}%`"></div>
                      </div>
                      <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ selectedTask.progress || 0 }}%</span>
                    </div>
                  </div>
                </div>

                <div class="text-[10px] text-slate-400 flex justify-between px-2 italic">
                  <span>Creado: {{ formatDateWithHour(selectedTask.created_at) }}</span>
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

                  <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-200 dark:border-slate-700/50">
                    <span class="text-xs text-slate-400 font-semibold">Registra tus avances aquí</span>
                    <button 
                      @click="sendTaskUpdate"
                      :disabled="!newTaskUpdateContent.trim() || sendingTaskUpdate"
                      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <Send v-if="!sendingTaskUpdate" :size="12" />
                      <Loader2 v-else :size="12" class="animate-spin" />
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
                          <span class="text-[10px] bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-full text-slate-400 font-mono">{{ formatDateWithHour(update.created_at) }}</span>
                        </div>
                      </div>
                      <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed prose prose-sm dark:prose-invert max-w-none whitespace-pre-line" v-html="update.content"></div>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { opportunitiesAPI } from '@/services/api'
import api from '@/services/api'
import Navbar from '@/components/AppNavbar.vue'
import TaskCreateModal from '@/components/TaskCreateModal.vue'
import {
  TrendingUp, Search, RefreshCw, Briefcase, User,
  Rocket, PackageOpen, CheckCircle, Clock, TrendingDown,
  Eye, X, ListTodo, Building2, History, MessageCircle, Send, Loader2, Plus
} from 'lucide-vue-next'

const opportunities = ref([])
const loading = ref(false)
const loadingDetail = ref(false)
const search = ref('')
const filterStage = ref('')
const sending = ref(null)
const showSuccessModal = ref(false)
const lastTriggeredOppName = ref('')

// Detail modal states
const selectedOpportunity = ref(null)
const opportunityDetail = ref(null)
const activeTab = ref('details')
const selectedTask = ref(null)
const taskActiveTab = ref('details')

// Update/Timeline states
const newUpdateContent = ref('')
const sendingUpdate = ref(false)
const newTaskUpdateContent = ref('')
const sendingTaskUpdate = ref(false)
const updatingTask = ref(false)

// Task creation modal state
const showTaskModal = ref(false)

const fetchOpportunities = async () => {
  loading.value = true
  try {
    const response = await opportunitiesAPI.getAll()
    if (response.data.pagination) {
      opportunities.value = response.data.data
    } else if (Array.isArray(response.data.data)) {
      opportunities.value = response.data.data
    } else if (Array.isArray(response.data)) {
      opportunities.value = response.data
    } else {
      opportunities.value = []
    }
    console.log('Oportunidades cargadas:', opportunities.value.length)
  } catch (error) {
    console.error('Error fetching opportunities:', error)
    opportunities.value = []
  } finally {
    loading.value = false
  }
}

const showOpportunityDetail = async (opp) => {
  selectedOpportunity.value = opp
  opportunityDetail.value = null
  activeTab.value = 'details'
  loadingDetail.value = true
  
  try {
    const response = await api.get(`/api/v1/opportunities/${opp.id}`)
    opportunityDetail.value = response.data.data
    console.log('Oportunidad detallada cargada:', opportunityDetail.value)
  } catch (error) {
    console.error('Error fetching opportunity detail:', error)
    opportunityDetail.value = opp
  } finally {
    loadingDetail.value = false
  }
}

const openTaskDetail = (task) => {
  selectedTask.value = { ...task }
  taskActiveTab.value = 'details'
}

const handleTaskCreated = (newTask) => {
  // Agregar tarea a la lista de tareas de la oportunidad
  if (opportunityDetail.value && opportunityDetail.value.tasks) {
    opportunityDetail.value.tasks.unshift(newTask)
  }
  showTaskModal.value = false
}

const updateTask = async (updates) => {
  if (!selectedTask.value) return
  
  updatingTask.value = true
  try {
    const response = await api.patch(`/api/v1/tasks/${selectedTask.value.id}`, updates)
    selectedTask.value = { ...selectedTask.value, ...updates }
    
    // Actualizar en la lista de tareas también
    if (opportunityDetail.value?.tasks) {
      const taskIndex = opportunityDetail.value.tasks.findIndex(t => t.id === selectedTask.value.id)
      if (taskIndex !== -1) {
        opportunityDetail.value.tasks[taskIndex] = { ...opportunityDetail.value.tasks[taskIndex], ...updates }
      }
    }
  } catch (error) {
    console.error('Error updating task:', error)
    alert('No se pudo actualizar la tarea')
  } finally {
    updatingTask.value = false
  }
}

const sendUpdate = async () => {
  if (!newUpdateContent.value.trim() || !selectedOpportunity.value) return
  
  sendingUpdate.value = true
  try {
    const response = await api.post(`/api/v1/opportunities/${selectedOpportunity.value.id}/updates`, {
      content: newUpdateContent.value
    })
    
    if (!opportunityDetail.value.updates) {
      opportunityDetail.value.updates = []
    }
    
    opportunityDetail.value.updates.unshift(response.data.data)
    newUpdateContent.value = ''
  } catch (error) {
    console.error('Error sending update:', error)
    alert('No se pudo registrar el avance')
  } finally {
    sendingUpdate.value = false
  }
}

const sendTaskUpdate = async () => {
  if (!newTaskUpdateContent.value.trim() || !selectedTask.value) return
  
  sendingTaskUpdate.value = true
  try {
    const response = await api.post(`/api/v1/tasks/${selectedTask.value.id}/updates`, {
      content: newTaskUpdateContent.value
    })
    
    if (!selectedTask.value.updates) {
      selectedTask.value.updates = []
    }
    
    selectedTask.value.updates.unshift(response.data.data)
    newTaskUpdateContent.value = ''
  } catch (error) {
    console.error('Error sending task update:', error)
    alert('No se pudo registrar el avance en la tarea')
  } finally {
    sendingTaskUpdate.value = false
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

const formatDateWithHour = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-CL') + ' ' + date.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' })
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
    'Levantamiento': 'Levantamiento',
    'Proyecto': 'Proyecto',
    'Firmar_Contrato': 'Firmar Contrato',
    'Facturacion': 'Facturación',
    'Facturado': 'Facturado',
    'Waiting': 'En Espera',
  }
  return map[stage] || stage
}

const getStageClass = (stage) => {
  if (stage === 'Closed Won' || stage === 'Facturado') return 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30'
  if (stage === 'Closed Lost') return 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/30'
  if (stage === 'Negotiation/Review' || stage === 'Proposal/Price Quote' || stage === 'Firmar_Contrato') 
    return 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/30'
  if (stage === 'Levantamiento') return 'bg-purple-50 text-purple-600 border-purple-100 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/30'
  if (stage === 'Proyecto') return 'bg-indigo-50 text-indigo-600 border-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/30'
  if (stage === 'Waiting') return 'bg-slate-50 text-slate-600 border-slate-100 dark:bg-slate-500/10 dark:text-slate-400 dark:border-slate-500/30'
  return 'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/30'
}

const taskStatusClass = (status) => {
  const map = {
    'pending': 'bg-slate-300',
    'in_progress': 'bg-blue-500',
    'completed': 'bg-emerald-500',
    'cancelled': 'bg-rose-500'
  }
  return map[status] || 'bg-slate-300'
}

const taskStatusTextClass = (status) => {
  const map = {
    'pending': 'text-slate-500',
    'in_progress': 'text-blue-500',
    'completed': 'text-emerald-500',
    'cancelled': 'text-rose-500'
  }
  return map[status] || 'text-slate-500'
}

const formatTaskStatus = (status) => {
  const map = {
    'pending': 'Pendiente',
    'in_progress': 'En proceso',
    'completed': 'Completada',
    'cancelled': 'Cancelada'
  }
  return map[status] || status
}

const taskPriorityBadge = (priority) => {
  const map = {
    'low': 'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/30',
    'medium': 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/30',
    'high': 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/30',
    'urgent': 'bg-red-50 text-red-600 border-red-100 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/30'
  }
  return map[priority] || 'bg-slate-50 text-slate-600 border-slate-100 dark:bg-slate-500/10 dark:text-slate-400 dark:border-slate-500/30'
}

onMounted(() => {
  fetchOpportunities()
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}
.modal-enter-from {
  opacity: 0;
  transform: scale(0.95);
}
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

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
