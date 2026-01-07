import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authAPI } from '@/services/api'
import { initializeEcho, disconnectEcho } from '@/services/echo'

export const useAuthStore = defineStore('auth', () => {
  // Estado
  const user = ref(null)
  const token = ref(null)
  const isLoading = ref(false)
  const error = ref(null)

  // Getters (computed)
  const isAuthenticated = computed(() => !!token.value)
  const currentUser = computed(() => user.value)

  // Cargar datos del localStorage al iniciar
  const loadFromStorage = () => {
    const storedToken = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')

    if (storedToken && storedUser && storedUser !== 'undefined' && storedUser !== 'null') {
      try {
        token.value = storedToken
        user.value = JSON.parse(storedUser)
        // Inicializar Echo si hay token
        initializeEcho(storedToken)
      } catch (err) {
        console.error('Error al cargar usuario del localStorage:', err)
        // Limpiar localStorage corrupto
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        localStorage.removeItem('auth_source')
      }
    }
  }

  // Login Unificado (Solo SuiteCRM)
  const login = async (credentials) => {
    try {
      console.log('Final credentials being sent to API:', {
        username: credentials.email || credentials.username || credentials.identifier,
        password: '***'
      })
      isLoading.value = true
      error.value = null

      // Enviar 'username' y 'password' tal cual espera el backend
      const response = await authAPI.login({
        username: credentials.email || credentials.username || credentials.identifier,
        password: credentials.password
      })

      const responseData = response.data

      const { user: userData, token: authToken, auth_source } = responseData

      // Guardar en el estado
      user.value = userData
      token.value = authToken

      // Guardar en localStorage
      localStorage.setItem('token', authToken)
      localStorage.setItem('user', JSON.stringify(userData))
      localStorage.setItem('auth_source', auth_source || 'sweetcrm')

      // Inicializar Echo
      initializeEcho(authToken)

      return { success: true, auth_source, user: userData }
    } catch (err) {
      console.error('Login error detail:', err.response?.data)
      error.value = err.response?.data?.message || 'Error al iniciar sesión'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  // Logout
  const logout = async () => {
    try {
      await authAPI.logout()
    } catch (err) {
      console.error('Error al hacer logout:', err)
    } finally {
      // Desconectar Echo
      disconnectEcho()

      // Limpiar todo
      user.value = null
      token.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  // Obtener datos del usuario actual
  const fetchCurrentUser = async () => {
    try {
      const response = await authAPI.me()
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(response.data.user))
    } catch (err) {
      console.error('Error al obtener usuario:', err)
      logout()
    }
  }

  return {
    // Estado
    user,
    token,
    isLoading,
    error,
    // Getters
    isAuthenticated,
    currentUser,
    // Acciones
    login,
    logout,
    fetchCurrentUser,
    loadFromStorage,
  }
})