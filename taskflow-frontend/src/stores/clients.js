import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useClientsStore = defineStore('clients', () => {
  // State
  const clients = ref([])
  const currentClient = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  // Getters
  const clientsList = computed(() => clients.value)
  const clientsCount = computed(() => clients.value.length)
  const isLoading = computed(() => loading.value)
  const hasError = computed(() => error.value !== null)

  const getClientById = computed(() => {
    return (id) => clients.value.find(client => client.id === id)
  })

  const activeClients = computed(() => {
    return clients.value.filter(client => client.status === 'active')
  })

  const clientsByIndustry = computed(() => {
    return (industryId) => clients.value.filter(
      client => client.industry_id === industryId
    )
  })

  // Actions
  async function fetchClients(params = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/clients', { params })

      if (response.data.data) {
        // Si la respuesta viene paginada
        clients.value = response.data.data
        pagination.value = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 15,
          total: response.data.total || response.data.data.length
        }
      } else {
        // Si la respuesta es un array simple
        clients.value = response.data
      }

      return clients.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar clientes'
      console.error('Error fetching clients:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchClient(id) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/clients/${id}`)
      currentClient.value = response.data

      // También actualizar en la lista si existe
      const index = clients.value.findIndex(c => c.id === id)
      if (index !== -1) {
        clients.value[index] = response.data
      }

      return currentClient.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar cliente'
      console.error('Error fetching client:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createClient(clientData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/clients', clientData)
      const newClient = response.data.client || response.data

      clients.value.unshift(newClient)
      pagination.value.total++

      return newClient
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al crear cliente'
      console.error('Error creating client:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateClient(id, clientData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.put(`/clients/${id}`, clientData)
      const updatedClient = response.data.client || response.data

      // Actualizar en la lista
      const index = clients.value.findIndex(c => c.id === id)
      if (index !== -1) {
        clients.value[index] = updatedClient
      }

      // Actualizar current si es el mismo
      if (currentClient.value?.id === id) {
        currentClient.value = updatedClient
      }

      return updatedClient
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al actualizar cliente'
      console.error('Error updating client:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteClient(id) {
    loading.value = true
    error.value = null

    try {
      await api.delete(`/clients/${id}`)

      // Remover de la lista
      clients.value = clients.value.filter(c => c.id !== id)
      pagination.value.total--

      // Limpiar current si es el mismo
      if (currentClient.value?.id === id) {
        currentClient.value = null
      }

      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al eliminar cliente'
      console.error('Error deleting client:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function syncFromSugarCRM(credentials) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/sweetcrm/sync-clients', credentials)

      // Recargar la lista de clientes después de sincronizar
      await fetchClients()

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al sincronizar con SugarCRM'
      console.error('Error syncing from SugarCRM:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  function setCurrentClient(client) {
    currentClient.value = client
  }

  function clearCurrentClient() {
    currentClient.value = null
  }

  function clearError() {
    error.value = null
  }

  function resetState() {
    clients.value = []
    currentClient.value = null
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
    clients,
    currentClient,
    loading,
    error,
    pagination,

    // Getters
    clientsList,
    clientsCount,
    isLoading,
    hasError,
    getClientById,
    activeClients,
    clientsByIndustry,

    // Actions
    fetchClients,
    fetchClient,
    createClient,
    updateClient,
    deleteClient,
    syncFromSugarCRM,
    setCurrentClient,
    clearCurrentClient,
    clearError,
    resetState
  }
})
