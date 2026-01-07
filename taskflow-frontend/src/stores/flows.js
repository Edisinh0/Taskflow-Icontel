import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useFlowsStore = defineStore('flows', () => {
  // State
  const flows = ref([])
  const currentFlow = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  // Getters
  const flowsList = computed(() => flows.value)
  const flowsCount = computed(() => flows.value.length)
  const isLoading = computed(() => loading.value)
  const hasError = computed(() => error.value !== null)

  const getFlowById = computed(() => {
    return (id) => flows.value.find(flow => flow.id === id)
  })

  const activeFlows = computed(() => {
    return flows.value.filter(flow => flow.status === 'active')
  })

  const completedFlows = computed(() => {
    return flows.value.filter(flow => flow.status === 'completed')
  })

  const flowsByClient = computed(() => {
    return (clientId) => flows.value.filter(flow => flow.client_id === clientId)
  })

  // Actions
  async function fetchFlows(params = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('flows', { params })

      if (response.data.data) {
        flows.value = response.data.data
        pagination.value = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 15,
          total: response.data.total || response.data.data.length
        }
      } else {
        flows.value = response.data
      }

      return flows.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar flujos'
      console.error('Error fetching flows:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchFlow(id) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/flows/${id}`)
      currentFlow.value = response.data

      const index = flows.value.findIndex(f => f.id === id)
      if (index !== -1) {
        flows.value[index] = response.data
      }

      return currentFlow.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar flujo'
      console.error('Error fetching flow:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createFlow(flowData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('flows', flowData)
      const newFlow = response.data.flow || response.data

      flows.value.unshift(newFlow)
      pagination.value.total++

      return newFlow
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al crear flujo'
      console.error('Error creating flow:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateFlow(id, flowData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.put(`/flows/${id}`, flowData)
      const updatedFlow = response.data.flow || response.data

      const index = flows.value.findIndex(f => f.id === id)
      if (index !== -1) {
        flows.value[index] = updatedFlow
      }

      if (currentFlow.value?.id === id) {
        currentFlow.value = updatedFlow
      }

      return updatedFlow
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al actualizar flujo'
      console.error('Error updating flow:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteFlow(id) {
    loading.value = true
    error.value = null

    try {
      await api.delete(`/flows/${id}`)

      flows.value = flows.value.filter(f => f.id !== id)
      pagination.value.total--

      if (currentFlow.value?.id === id) {
        currentFlow.value = null
      }

      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al eliminar flujo'
      console.error('Error deleting flow:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function executeFlow(id, executionData = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/flows/${id}/execute`, executionData)

      // Actualizar el flujo en la lista
      await fetchFlow(id)

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al ejecutar flujo'
      console.error('Error executing flow:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  function setCurrentFlow(flow) {
    currentFlow.value = flow
  }

  function clearCurrentFlow() {
    currentFlow.value = null
  }

  function clearError() {
    error.value = null
  }

  function resetState() {
    flows.value = []
    currentFlow.value = null
    loading.value = false
    error.value = null
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    }
  }

  return {
    // State
    flows,
    currentFlow,
    loading,
    error,
    pagination,

    // Getters
    flowsList,
    flowsCount,
    isLoading,
    hasError,
    getFlowById,
    activeFlows,
    completedFlows,
    flowsByClient,

    // Actions
    fetchFlows,
    fetchFlow,
    createFlow,
    updateFlow,
    deleteFlow,
    executeFlow,
    setCurrentFlow,
    clearCurrentFlow,
    clearError,
    resetState
  }
})
