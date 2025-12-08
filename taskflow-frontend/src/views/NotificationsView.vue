<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <Navbar />
    
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Notificaciones</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Todas tus notificaciones en un solo lugar</p>
      </div>

      <!-- Acciones -->
      <div class="mb-6 flex justify-between items-center">
        <div class="flex space-x-2">
          <button
            @click="filterType = 'all'"
            :class="filterType === 'all' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
            class="px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300 dark:border-gray-600"
          >
            Todas
          </button>
          <button
            @click="filterType = 'unread'"
            :class="filterType === 'unread' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
            class="px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300 dark:border-gray-600"
          >
            No leídas ({{ unreadCount }})
          </button>
        </div>
        
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors"
        >
          Marcar todas como leídas
        </button>
      </div>

      <!-- Lista de notificaciones -->
      <div class="space-y-3">
        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          @click="handleNotificationClick(notification)"
          class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all cursor-pointer"
          :class="{ 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800': !notification.is_read }"
        >
          <div class="flex items-start space-x-4">
            <!-- Icono -->
            <div
              class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
              :class="getNotificationIconClass(notification.type)"
            >
              <span class="text-2xl">{{ getNotificationIcon(notification.type) }}</span>
            </div>

            <!-- Contenido -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ notification.title }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>

                <!-- Badge de prioridad -->
                <span
                  v-if="notification.priority === 'urgent' || notification.priority === 'high'"
                  class="ml-4 px-2 py-1 text-xs font-semibold rounded-full"
                  :class="notification.priority === 'urgent' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' : 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400'"
                >
                  {{ notification.priority === 'urgent' ? 'Urgente' : 'Alta' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Vacío -->
        <div
          v-if="filteredNotifications.length === 0"
          class="text-center py-16"
        >
          <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400">No tienes notificaciones</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Navbar from '@/components/Navbar.vue'

const router = useRouter()
const notifications = ref([])
const unreadCount = ref(0)
const filterType = ref('all')

const filteredNotifications = computed(() => {
  if (filterType.value === 'unread') {
    return notifications.value.filter(n => !n.is_read)
  }
  return notifications.value
})

const loadNotifications = async () => {
  try {
    const token = localStorage.getItem('token')
    const response = await axios.get('http://localhost:8000/api/v1/notifications', {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    notifications.value = response.data.data
    unreadCount.value = response.data.meta.unread_count
  } catch (error) {
    console.error('Error cargando notificaciones:', error)
  }
}

const markAllAsRead = async () => {
  try {
    const token = localStorage.getItem('token')
    await axios.post('http://localhost:8000/api/v1/notifications/read-all', {}, {
      headers: { Authorization: `Bearer ${token}` }
    })
    await loadNotifications()
  } catch (error) {
    console.error('Error marcando notificaciones:', error)
  }
}

const handleNotificationClick = async (notification) => {
  try {
    // Marcar como leída
    const token = localStorage.getItem('token')
    await axios.put(`http://localhost:8000/api/v1/notifications/${notification.id}/read`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    })

    // Navegar según el tipo
    if (notification.task_id) {
      router.push(`/flows/${notification.flow_id}`)
    } else if (notification.flow_id) {
      router.push(`/flows/${notification.flow_id}`)
    }

    await loadNotifications()
  } catch (error) {
    console.error('Error manejando notificación:', error)
  }
}

const getNotificationIcon = (type) => {
  const icons = {
    sla_warning: '⚠️',
    task_overdue: '🚨',
    task_completed: '✅',
    task_assigned: '📋',
    task_blocked: '🔒',
    task_unblocked: '🔓',
    milestone_completed: '🎯'
  }
  return icons[type] || '📢'
}

const getNotificationIconClass = (type) => {
  const classes = {
    sla_warning: 'bg-yellow-100 dark:bg-yellow-900/30',
    task_overdue: 'bg-red-100 dark:bg-red-900/30',
    task_completed: 'bg-green-100 dark:bg-green-900/30',
    task_assigned: 'bg-blue-100 dark:bg-blue-900/30',
    task_blocked: 'bg-gray-100 dark:bg-gray-700',
    task_unblocked: 'bg-green-100 dark:bg-green-900/30',
    milestone_completed: 'bg-purple-100 dark:bg-purple-900/30'
  }
  return classes[type] || 'bg-gray-100 dark:bg-gray-700'
}

const formatDate = (date) => {
  const d = new Date(date)
  const now = new Date()
  const diff = Math.floor((now - d) / 1000)

  if (diff < 60) return 'Ahora'
  if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`
  if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} h`
  if (diff < 604800) return `Hace ${Math.floor(diff / 86400)} días`
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(() => {
  loadNotifications()
})
</script>
