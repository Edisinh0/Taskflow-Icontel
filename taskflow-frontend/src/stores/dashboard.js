import { defineStore } from 'pinia';
import api from '@/services/api';

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        cases: [], // Hierarchical cases (with tasks)
        orphanTasks: [], // Tasks without case
        scope: 'my', // 'my' | 'area'
        viewMode: 'cases', // 'cases' (hierarchy) | 'tasks' (flat list)
        loading: false,
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
