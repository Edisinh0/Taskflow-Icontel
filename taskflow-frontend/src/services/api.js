import axios from 'axios'

// Configuración base de Axios
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Para cookies de sesión
})

// Interceptor para agregar el token automáticamente
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor para manejar errores de autenticación
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Token inválido o expirado
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// ========== AUTH ==========
export const authAPI = {
  login: (credentials) => api.post('auth/login', credentials),
  logout: () => api.post('auth/logout'),
  me: () => api.get('auth/me'),
  register: (userData) => api.post('auth/register', userData),
}

// ========== SWEETCRM ==========
export const sweetCrmAPI = {
  ping: () => api.get('sweetcrm/ping'),
  syncClients: (filters) => api.post('sweetcrm/sync-clients', { filters }),
  syncClient: (sweetcrmId) => api.post(`sweetcrm/sync-client/${sweetcrmId}`),
  getUser: (sweetcrmId) => api.get(`sweetcrm/user/${sweetcrmId}`),
  syncMe: () => api.post('sweetcrm/sync-me'),
}

// ========== INDUSTRIES ==========
export const industriesAPI = {
  getAll: () => api.get('industries'),
  getOne: (id) => api.get(`industries/${id}`),
  create: (data) => api.post('industries', data),
  update: (id, data) => api.put(`industries/${id}`, data),
  delete: (id) => api.delete(`industries/${id}`),
}

// ========== TEMPLATES ==========
export const templatesAPI = {
  getAll: (params) => api.get('templates', { params }),
  getOne: (id) => api.get(`templates/${id}`),
  create: (data) => api.post('templates', data),
  createFromFlow: (flowId, data) => api.post(`templates/from-flow/${flowId}`, data),
  update: (id, data) => api.put(`templates/${id}`, data),
  delete: (id) => api.delete(`templates/${id}`),
  getRecommendedForClient: (clientId) => api.get(`clients/${clientId}/recommended-templates`),
}

// ========== FLOWS ==========
export const flowsAPI = {
  getAll: (params) => api.get('flows', { params }),
  getOne: (id) => api.get(`flows/${id}`),
  create: (data) => api.post('flows', data),
  update: (id, data) => api.put(`flows/${id}`, data),
  delete: (id) => api.delete(`flows/${id}`),
}

// ========== TASKS ==========
export const tasksAPI = {
  getAll: (params) => api.get('tasks', { params }),
  getOne: (id) => api.get(`tasks/${id}`),
  create: (data) => api.post('tasks', data),
  update: (id, data) => api.put(`tasks/${id}`, data),
  delete: (id) => api.delete(`tasks/${id}`),
  uploadAttachment: (taskId, formData, onUploadProgress) => api.post(`tasks/${taskId}/attachments`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    onUploadProgress
  }),
  deleteAttachment: (fileId) => api.delete(`attachments/${fileId}`),
}

// ========== CASES ==========
export const casesAPI = {
  getAll: (params) => api.get('cases', { params }),
  getOne: (id) => api.get(`cases/${id}`),
  getStats: () => api.get('cases/stats'),
}

// ========== FLOW BUILDER MODULE (PM/Admin) ==========
export const flowBuilderAPI = {
  createFlow: (data) => api.post('flow-builder/flows', data),
  updateFlow: (id, data) => api.put(`flow-builder/flows/${id}`, data),
  deleteFlow: (id) => api.delete(`flow-builder/flows/${id}`),

  createTask: (data) => api.post('flow-builder/tasks', data),
  updateTaskStructure: (id, data) => api.put(`flow-builder/tasks/${id}`, data),
  deleteTask: (id) => api.delete(`flow-builder/tasks/${id}`),
  configureDependencies: (id, data) => api.put(`flow-builder/tasks/${id}/dependencies`, data),
}

// ========== DASHBOARD ==========
export const dashboardAPI = {
  getStats: () => api.get('dashboard/stats'),
  getMyContent: (params) => api.get('dashboard/my-content', { params }),
  getAreaBasedContent: (params) => api.get('dashboard/area-content', { params }),
  getDelegated: () => api.get('dashboard/delegated'),
  getDelegatedSales: () => api.get('dashboard/delegated-sales'),
  getContentByView: (view = 'my') => api.get('dashboard/area-content', { params: { view } }),
}

// ========== OPPORTUNITIES & SALES ==========
export const opportunitiesAPI = {
  getAll: (params) => api.get('opportunities', { params }),
  getOne: (id) => api.get(`opportunities/${id}`),
  sendToOperations: (id) => api.post(`opportunities/${id}/send-to-operations`),
}

export default api