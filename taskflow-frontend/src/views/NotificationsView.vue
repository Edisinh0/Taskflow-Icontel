<template>
  <div class="min-h-screen bg-slate-900">
    <Navbar />
    
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-white tracking-tight">Notificaciones</h2>
        <p class="text-slate-400 mt-1 text-lg">Mantente al día con todas las actualizaciones de tus proyectos</p>
      </div>

      <!-- Acciones -->
      <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="bg-slate-800/50 p-1 rounded-xl border border-white/5 backdrop-blur-sm">
          <button
            @click="filterType = 'all'"
            :class="filterType === 'all' ? 'bg-slate-700 text-white shadow-md' : 'text-slate-400 hover:text-white'"
            class="px-6 py-2 rounded-lg font-bold text-sm transition-all"
          >
            Todas
          </button>
          <button
            @click="filterType = 'unread'"
            :class="filterType === 'unread' ? 'bg-slate-700 text-white shadow-md' : 'text-slate-400 hover:text-white'"
            class="px-6 py-2 rounded-lg font-bold text-sm transition-all"
          >
            No leídas <span v-if="unreadCount > 0" class="ml-1 px-1.5 py-0.5 bg-blue-500 text-white text-[10px] rounded-full">{{ unreadCount }}</span>
          </button>
        </div>
        
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="px-4 py-2 bg-blue-600/10 text-blue-400 border border-blue-600/20 rounded-xl hover:bg-blue-600 hover:text-white font-bold text-sm transition-all flex items-center"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          Marcar todas como leídas
        </button>
      </div>

      <!-- Lista de notificaciones -->
      <div class="space-y-4">
        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          @click="handleNotificationClick(notification)"
          class="rounded-2xl p-5 border transition-all cursor-pointer group relative overflow-hidden"
          :class="[
            !notification.is_read 
              ? 'bg-blue-900/10 border-blue-500/30 shadow-lg shadow-blue-900/10 hover:bg-blue-900/20' 
              : 'bg-slate-800/80 border-white/5 hover:border-slate-600 hover:bg-slate-800'
          ]"
        >
          <div v-if="!notification.is_read" class="absolute top-4 right-4 w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>

          <div class="flex items-start space-x-5">
            <!-- Icono -->
            <div
              class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center border border-white/5 shadow-inner"
              :class="getNotificationIconClass(notification.type)"
            >
              <span class="text-2xl filter drop-shadow no-emoji-font">{{ getNotificationIcon(notification.type) }}</span>
            </div>

            <!-- Contenido -->
            <div class="flex-1 min-w-0 pt-0.5">
              <div class="flex items-start justify-between">
                <div class="flex-1 pr-6">
                  <p class="text-lg font-bold text-white group-hover:text-blue-400 transition-colors">
                    {{ notification.title }}
                  </p>
                  <p class="text-slate-400 mt-1 leading-relaxed">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mt-3 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>

                <!-- Badge de prioridad -->
                <span
                  v-if="notification.priority === 'urgent' || notification.priority === 'high'"
                  class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border"
                  :class="notification.priority === 'urgent' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-orange-500/10 text-orange-400 border-orange-500/20'"
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
          class="text-center py-20 bg-slate-800/30 rounded-3xl border-2 border-dashed border-slate-700"
        >
          <div class="bg-slate-800 p-4 rounded-full inline-block mb-4">
            <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          <p class="text-white text-xl font-bold">No tienes notificaciones</p>
          <p class="text-slate-400 mt-2">Estás al día con todas tus tareas</p>
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
    sla_warning: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
    task_overdue: 'bg-red-500/10 text-rose-500 border-rose-500/20',
    task_completed: 'bg-green-500/10 text-green-500 border-green-500/20',
    task_assigned: 'bg-blue-500/10 text-blue-500 border-blue-500/20',
    task_blocked: 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    task_unblocked: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
    milestone_completed: 'bg-purple-500/10 text-purple-500 border-purple-500/20'
  }
  return classes[type] || 'bg-slate-700/50 text-slate-400'
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
