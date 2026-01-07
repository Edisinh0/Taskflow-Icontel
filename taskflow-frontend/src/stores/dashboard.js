import { defineStore } from 'pinia';
import api from '@/services/api';
import { dashboardAPI } from '@/services/api';

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        cases: [], // Hierarchical cases (with tasks)
        orphanTasks: [], // Tasks without case
        opportunities: [], // Oportunidades para equipo de ventas
        delegated: { // Tareas y casos delegados
            cases: [],
            tasks: [],
            total: 0,
            pending: 0
        },
        delegatedSales: { // Oportunidades y tareas delegadas para ventas
            opportunities: [],
            tasks: [],
            total: 0,
            pending: 0
        },
        scope: 'my', // 'my' | 'area'
        viewMode: 'cases', // 'cases' (hierarchy) | 'tasks' (flat list)
        loading: false,
        delegatedLoading: false,
        delegatedSalesLoading: false,
        userArea: null, // 'sales' | 'operations' | null
        error: null,
    }),

    actions: {
        async fetchContent() {
            this.loading = true;
            this.error = null;
            try {
                // Usar endpoints locales (base de datos) en lugar de consultar SweetCRM API directamente
                // Esto mejora el rendimiento y la consistencia de los datos
                // El endpoint /my-cases ahora retorna datos paginados con estructura { data, meta }
                const [casesResponse, tasksResponse] = await Promise.all([
                    api.get('my-cases', { params: { per_page: 50 } }), // Casos desde la base de datos local
                    api.get('my-tasks')  // Tareas desde la base de datos local
                ]);

                // Manejar nueva estructura paginada del backend
                // El backend ahora retorna { data: [], meta: { pagination info } }
                const responseData = casesResponse.data;
                const rawCases = responseData.data || responseData.cases || [];

                console.log('📊 Dashboard API Response (Local DB):', {
                    cases_count: rawCases.length,
                    tasks_count: tasksResponse.data.tasks?.length || 0,
                    pagination: responseData.meta || null,
                });

                // Tareas que pertenecen a casos en memoria (para mantener consistencia)
                const tasksFromCases = rawCases.flatMap(c => c.tasks || []);
                const tasksFromCasesIds = new Set(tasksFromCases.map(t => t.id));

                // Tareas huérfanas (sin caso o cuyo caso no está en la respuesta)
                const allTasks = tasksResponse.data.tasks || [];
                const orphanTasks = allTasks.filter(t => !t.case_id || !tasksFromCasesIds.has(t.id));

                // Initialize expanded state for UI reactivity
                this.cases = rawCases.map(c => ({
                    ...c,
                    expanded: false,
                    // Normalizar estructura para compatibilidad con frontend
                    title: c.subject || c.title,
                    case_number: c.case_number,
                }));

                this.orphanTasks = orphanTasks;

                console.log('📊 After Processing:', {
                    cases: this.cases.length,
                    orphan_tasks: this.orphanTasks.length,
                    all_tasks_flat: this.allTasksFlat.length
                });

            } catch (error) {
                console.error('Error fetching dashboard content:', error);
                this.error = error.message || 'Error al cargar contenido';
            } finally {
                this.loading = false;
            }
        },

        calculateTotalTasks(cases, orphanTasks) {
            const tasksInCases = (cases || []).reduce((sum, c) => sum + (c.tasks?.length || 0), 0);
            return tasksInCases + (orphanTasks?.length || 0);
        },

        setScope(scope) {
            if (['my', 'area'].includes(scope)) {
                this.scope = scope;
                this.fetchContent(); // Refresh data on scope change
            }
        },

        toggleViewMode() {
            this.viewMode = this.viewMode === 'cases' ? 'tasks' : 'cases';
        },

        toggleCase(caseId) {
            const c = this.cases.find(c => c.id === caseId);
            if (c) {
                c.expanded = !c.expanded;
            }
        },

        async fetchDelegated() {
            this.delegatedLoading = true;
            try {
                const response = await dashboardAPI.getDelegated();
                if (response.data.success) {
                    this.delegated = response.data.data;
                    console.log('✅ Delegated tasks/cases loaded:', {
                        cases: this.delegated.cases.length,
                        tasks: this.delegated.tasks.length,
                        total: this.delegated.total,
                        pending: this.delegated.pending
                    });
                } else {
                    console.error('Error fetching delegated tasks:', response.data.message);
                }
            } catch (error) {
                console.error('Error en fetchDelegated:', error);
            } finally {
                this.delegatedLoading = false;
            }
        },

        async fetchAreaBasedContent() {
            this.loading = true;
            this.error = null;
            try {
                const response = await dashboardAPI.getAreaBasedContent();
                if (response.data.success) {
                    this.userArea = response.data.user_area;
                    const data = response.data.data;

                    if (this.userArea === 'sales') {
                        // Para equipo de ventas: Oportunidades + Tareas
                        this.opportunities = data.opportunities || [];
                        this.orphanTasks = data.tasks || [];
                        console.log('✅ Sales team content loaded:', {
                            opportunities: this.opportunities.length,
                            tasks: this.orphanTasks.length,
                            total: data.total
                        });
                    } else {
                        // Para otros: Casos + Tareas
                        this.cases = data.cases || [];
                        this.orphanTasks = data.tasks || [];
                        console.log('✅ Standard content loaded:', {
                            cases: this.cases.length,
                            tasks: this.orphanTasks.length
                        });
                    }
                } else {
                    console.error('Error fetching area-based content:', response.data.message);
                    this.error = response.data.message;
                }
            } catch (error) {
                console.error('Error en fetchAreaBasedContent:', error);
                this.error = error.message || 'Error al cargar contenido';
            } finally {
                this.loading = false;
            }
        },

        async fetchDelegatedSales() {
            this.delegatedSalesLoading = true;
            try {
                const response = await dashboardAPI.getDelegatedSales();
                if (response.data.success) {
                    this.delegatedSales = response.data.data;
                    console.log('✅ Delegated sales opportunities/tasks loaded:', {
                        opportunities: this.delegatedSales.opportunities.length,
                        tasks: this.delegatedSales.tasks.length,
                        total: this.delegatedSales.total,
                        pending: this.delegatedSales.pending
                    });
                } else {
                    console.error('Error fetching delegated sales:', response.data.message);
                }
            } catch (error) {
                console.error('Error en fetchDelegatedSales:', error);
            } finally {
                this.delegatedSalesLoading = false;
            }
        }
    },

    getters: {
        // Helper to get a flat list of ALL tasks if needed for 'tasks' view
        allTasksFlat: (state) => {
            const tasksFromCases = state.cases.flatMap(c => {
                if (!c.tasks) return [];
                // Inyectamos la información del caso padre en cada tarea
                return c.tasks.map(t => ({
                    ...t,
                    crm_case: {
                        id: c.id,
                        case_number: c.case_number,
                        subject: c.title, // Map title to subject for view compatibility
                        title: c.title,
                        client: c.client // Pass client info if available in case
                    }
                }));
            });
            return [...tasksFromCases, ...state.orphanTasks];
        },

        // Display string
        scopeLabel: (state) => state.scope === 'my' ? 'Mis Proyectos' : 'Proyectos del Área',

        totalActiveCases: (state) => state.cases.length,
    }
});
