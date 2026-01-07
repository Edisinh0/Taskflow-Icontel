import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useTasksStore = defineStore('tasks', () => {
  // State
  const tasks = ref([])
  const currentTask = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  // Getters
  const tasksList = computed(() => tasks.value)
  const tasksCount = computed(() => tasks.value.length)
  const isLoading = computed(() => loading.value)
  const hasError = computed(() => error.value !== null)

  const getTaskById = computed(() => {
    return (id) => tasks.value.find(task => task.id === id)
  })

  const pendingTasks = computed(() => {
    return tasks.value.filter(task => task.status === 'pending')
  })

  const inProgressTasks = computed(() => {
    return tasks.value.filter(task => task.status === 'in_progress')
  })

  const completedTasks = computed(() => {
    return tasks.value.filter(task => task.status === 'completed')
  })

  const tasksByFlow = computed(() => {
    return (flowId) => tasks.value.filter(task => task.flow_id === flowId)
  })

  const tasksByAssignee = computed(() => {
    return (userId) => tasks.value.filter(task => task.assigned_to === userId)
  })

  const overdueTasks = computed(() => {
    const now = new Date()
    return tasks.value.filter(task => {
      if (!task.due_date || task.status === 'completed') return false
      return new Date(task.due_date) < now
    })
  })

  // Actions
  async function fetchTasks(params = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('tasks', { params })

      if (response.data.data) {
        tasks.value = response.data.data
        pagination.value = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 15,
          total: response.data.total || response.data.data.length
        }
      } else {
        tasks.value = response.data
      }

      return tasks.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar tareas'
      console.error('Error fetching tasks:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchTask(id) {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/tasks/${id}`)
      currentTask.value = response.data

      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = response.data
      }

      return currentTask.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar tarea'
      console.error('Error fetching task:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createTask(taskData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('tasks', taskData)
      const newTask = response.data.task || response.data

      tasks.value.unshift(newTask)
      pagination.value.total++

      return newTask
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al crear tarea'
      console.error('Error creating task:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateTask(id, taskData) {
    loading.value = true
    error.value = null

    try {
      const response = await api.put(`/tasks/${id}`, taskData)
      const updatedTask = response.data.task || response.data

      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }

      if (currentTask.value?.id === id) {
        currentTask.value = updatedTask
      }

      return updatedTask
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al actualizar tarea'
      console.error('Error updating task:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteTask(id) {
    loading.value = true
    error.value = null

    try {
      await api.delete(`/tasks/${id}`)

      tasks.value = tasks.value.filter(t => t.id !== id)
      pagination.value.total--

      if (currentTask.value?.id === id) {
        currentTask.value = null
      }

      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al eliminar tarea'
      console.error('Error deleting task:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateTaskStatus(id, status) {
    return await updateTask(id, { status })
  }

  async function assignTask(id, userId) {
    return await updateTask(id, { assigned_to: userId })
  }

  async function completeTask(id, completionData = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/tasks/${id}/complete`, completionData)
      const completedTask = response.data.task || response.data

      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = completedTask
      }

      if (currentTask.value?.id === id) {
        currentTask.value = completedTask
      }

      return completedTask
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al completar tarea'
      console.error('Error completing task:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  function setCurrentTask(task) {
    currentTask.value = task
  }

  function clearCurrentTask() {
    currentTask.value = null
  }

  function clearError() {
    error.value = null
  }

  function resetState() {
    tasks.value = []
    currentTask.value = null
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
    tasks,
    currentTask,
    loading,
    error,
    pagination,

    // Getters
    tasksList,
    tasksCount,
    isLoading,
    hasError,
    getTaskById,
    pendingTasks,
    inProgressTasks,
    completedTasks,
    tasksByFlow,
    tasksByAssignee,
    overdueTasks,

    // Actions
    fetchTasks,
    fetchTask,
    createTask,
    updateTask,
    deleteTask,
    updateTaskStatus,
    assignTask,
    completeTask,
    setCurrentTask,
    clearCurrentTask,
    clearError,
    resetState
  }
})
