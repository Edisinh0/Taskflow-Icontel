<template>
  <div class="relative">
    <!-- Botón de campana -->
    <button
      @click="toggleDropdown"
      class="relative p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
      title="Notificaciones"
    >
      <!-- Icono de campana -->
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      
      <!-- Badge de cantidad -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center animate-pulse"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown de notificaciones -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50"
        @click.stop
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Notificaciones
          </h3>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
          >
            Marcar todas como leídas
          </button>
        </div>

        <!-- Lista de notificaciones -->
        <div class="max-h-96 overflow-y-auto">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
            :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.is_read }"
            @click="handleNotificationClick(notification)"
          >
            <div class="flex items-start space-x-3">
              <!-- Icono según tipo -->
              <div
                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                :class="getNotificationIconClass(notification.type)"
              >
                <span class="text-xl">{{ getNotificationIcon(notification.type) }}</span>
              </div>

              <!-- Contenido -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ notification.title }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ notification.message }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                  {{ formatDate(notification.created_at) }}
                </p>
              </div>

              <!-- Badge de prioridad -->
              <span
                v-if="notification.priority === 'urgent'"
                class="flex-shrink-0 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 text-xs font-semibold rounded-full"
              >
                Urgente
              </span>
            </div>
          </div>

          <!-- Vacío -->
          <div
            v-if="notifications.length === 0"
            class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
          >
            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <p>No tienes notificaciones</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
          <router-link
            to="/notifications"
            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
            @click="isOpen = false"
          >
            Ver todas las notificaciones →
          </router-link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const isOpen = ref(false)
const notifications = ref([])
const unreadCount = ref(0)

let pollInterval = null

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    loadNotifications()
  }
}

const loadNotifications = async () => {
  try {
    const token = localStorage.getItem('token')
    const response = await axios.get('http://localhost:8000/api/v1/notifications', {
      headers: { Authorization: `Bearer ${token}` },
      params: { unread: false }
    })
    
    notifications.value = response.data.data.slice(0, 10)
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

    isOpen.value = false
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
    task_blocked: '🔒'
  }
  return icons[type] || '📢'
}

const getNotificationIconClass = (type) => {
  const classes = {
    sla_warning: 'bg-yellow-100 dark:bg-yellow-900/30',
    task_overdue: 'bg-red-100 dark:bg-red-900/30',
    task_completed: 'bg-green-100 dark:bg-green-900/30',
    task_assigned: 'bg-blue-100 dark:bg-blue-900/30',
    task_blocked: 'bg-gray-100 dark:bg-gray-700'
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
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' })
}

// Polling cada 30 segundos
onMounted(() => {
  loadNotifications()
  pollInterval = setInterval(loadNotifications, 30000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

// Cerrar al hacer clic fuera
const handleClickOutside = (e) => {
  if (isOpen.value && !e.target.closest('.relative')) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>