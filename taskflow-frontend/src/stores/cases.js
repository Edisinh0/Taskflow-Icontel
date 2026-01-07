import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useCasesStore = defineStore('cases', () => {
    // Estado
    const cases = ref([])
    const loading = ref(false)
    const loadingMore = ref(false)
    const error = ref(null)

    // Paginación
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
        from: null,
        to: null
    })

    // Filtros
    const filters = ref({
        search: '',
        status: 'all',
        priority: 'all',
        area: 'all',
        assigned_to_me: false
    })

    // Estadísticas
    const stats = ref({
        total: 0,
        open: 0,
        totalTasks: 0,
        by_status: [],
        by_priority: [],
        by_area: []
    })

    // Computed
    const hasMore = computed(() => pagination.value.current_page < pagination.value.last_page)

    const isEmpty = computed(() => !loading.value && cases.value.length === 0)

    // Construir params para la API
    const buildParams = (page = 1) => {
        const params = {
            page,
            per_page: pagination.value.per_page
        }

        if (filters.value.search) {
            params.search = filters.value.search
        }
        if (filters.value.status !== 'all') {
            params.status = filters.value.status
        }
        if (filters.value.priority !== 'all') {
            params.priority = filters.value.priority
        }
        if (filters.value.area !== 'all') {
            params.area = filters.value.area
        }
        if (filters.value.assigned_to_me) {
            params.assigned_to_me = true
        }

        return params
    }

    // Acciones
    /**
     * Cargar casos (página inicial)
     */
    async function fetchCases(resetPage = true) {
        loading.value = true
        error.value = null

        if (resetPage) {
            pagination.value.current_page = 1
            cases.value = []
        }

        try {
            const params = buildParams(pagination.value.current_page)
            const response = await api.get('cases', { params })

            // La respuesta de Laravel Resource usa 'data' y 'meta'
            const responseData = response.data
            console.log('API Response data:', responseData)
            console.log('Total items in response:', responseData.data ? responseData.data.length : 0)

            cases.value = responseData.data || []

            // Actualizar paginación desde meta o directamente
            if (responseData.meta) {
                pagination.value = {
                    current_page: responseData.meta.current_page,
                    last_page: responseData.meta.last_page,
                    per_page: responseData.meta.per_page,
                    total: responseData.meta.total,
                    from: responseData.meta.from,
                    to: responseData.meta.to
                }
            } else {
                // Formato antiguo de paginate()
                pagination.value = {
                    current_page: responseData.current_page,
                    last_page: responseData.last_page,
                    per_page: responseData.per_page,
                    total: responseData.total,
                    from: responseData.from,
                    to: responseData.to
                }
            }

        } catch (err) {
            console.error('Error fetching cases:', err)
            error.value = err.message || 'Error al cargar casos'
        } finally {
            loading.value = false
        }
    }

    /**
     * Cargar más casos (Infinite Scroll)
     */
    async function loadMore() {
        if (loadingMore.value || !hasMore.value) return

        loadingMore.value = true

        try {
            const nextPage = pagination.value.current_page + 1
            const params = buildParams(nextPage)
            const response = await api.get('cases', { params })

            const responseData = response.data
            const newCases = responseData.data || []

            // Agregar nuevos casos sin duplicados
            const existingIds = new Set(cases.value.map(c => c.id))
            const uniqueNewCases = newCases.filter(c => !existingIds.has(c.id))

            cases.value = [...cases.value, ...uniqueNewCases]

            // Actualizar paginación
            if (responseData.meta) {
                pagination.value = {
                    ...pagination.value,
                    current_page: responseData.meta.current_page,
                    last_page: responseData.meta.last_page,
                    total: responseData.meta.total
                }
            } else {
                pagination.value.current_page = responseData.current_page
                pagination.value.last_page = responseData.last_page
                pagination.value.total = responseData.total
            }

        } catch (err) {
            console.error('Error loading more cases:', err)
        } finally {
            loadingMore.value = false
        }
    }

    /**
     * Ir a una página específica
     */
    async function goToPage(page) {
        if (page < 1 || page > pagination.value.last_page) return
        pagination.value.current_page = page
        await fetchCases(false)
    }

    /**
     * Cargar estadísticas
     */
    async function fetchStats() {
        try {
            const response = await api.get('cases/stats')
            const s = response.data

            stats.value = {
                total: s.total || 0,
                open: (s.by_status || [])
                    .filter(st => ['Nuevo', 'Asignado', ''].includes(st.status))
                    .reduce((acc, curr) => acc + curr.count, 0),
                totalTasks: s.tasks_total || 0,
                by_status: s.by_status || [],
                by_priority: s.by_priority || [],
                by_area: s.by_area || []
            }
        } catch (err) {
            console.error('Error fetching stats:', err)
        }
    }

    /**
     * Actualizar filtros y recargar
     */
    function setFilter(key, value) {
        filters.value[key] = value
        fetchCases(true) // Reset a página 1
    }

    /**
     * Limpiar todos los filtros
     */
    function clearFilters() {
        filters.value = {
            search: '',
            status: 'all',
            priority: 'all',
            area: 'all',
            assigned_to_me: false
        }
        fetchCases(true)
    }

    /**
     * Resetear el store
     */
    function reset() {
        cases.value = []
        pagination.value = {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: null,
            to: null
        }
        filters.value = {
            search: '',
            status: 'all',
            priority: 'all',
            area: 'all',
            assigned_to_me: false
        }
        error.value = null
    }

    return {
        // Estado
        cases,
        loading,
        loadingMore,
        error,
        pagination,
        filters,
        stats,

        // Computed
        hasMore,
        isEmpty,

        // Acciones
        fetchCases,
        loadMore,
        goToPage,
        fetchStats,
        setFilter,
        clearFilters,
        reset
    }
})
