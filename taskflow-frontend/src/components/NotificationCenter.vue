<template>
  <div class="notification-center">
    <!-- Notification Bell Button -->
    <button
      @click.stop="togglePanel"
      type="button"
      class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors"
      :class="{ 'animate-pulse': unreadCount > 0 }"
    >
      <Bell :size="24" />
      <span
        v-if="unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Notification Panel -->
    <transition name="slide-fade">
      <div
        v-if="showPanel"
        v-click-outside="closePanel"
        class="absolute right-0 top-12 w-96 max-h-[600px] bg-white rounded-lg shadow-xl border border-gray-200 z-50 overflow-hidden"
      >
        <!-- Panel Header -->
        <div
          class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50"
        >
          <h3 class="text-lg font-semibold text-gray-900">Notificaciones</h3>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
          >
            Marcar todas como leídas
          </button>
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto max-h-[500px]">
          <div v-if="isLoading" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>

          <div v-else-if="notifications.length === 0" class="text-center py-8 text-gray-500">
            <Bell :size="48" class="mx-auto mb-2 opacity-50" />
            <p>No tienes notificaciones</p>
          </div>

          <div v-else>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              @click="handleNotificationClick(notification)"
              :class="[
                'px-4 py-3 border-b border-gray-100 cursor-pointer transition-colors hover:bg-gray-50',
                {
                  'bg-blue-50': !notification.is_read,
                  'border-l-4 border-red-500': notification.priority === 'urgent'
                }
              ]"
            >
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0 mt-1">
                  <component :is="getNotificationIcon(notification.type)" :size="20" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-semibold text-gray-900 mb-1">
                    {{ notification.title }}
                  </h4>
                  <p class="text-sm text-gray-600 line-clamp-2">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs text-gray-400 mt-1">
                    {{ formatDate(notification.created_at) }}
                  </p>
                </div>
                <div v-if="!notification.is_read" class="flex-shrink-0">
                  <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Toast Notifications -->
    <transition-group name="toast" tag="div" class="fixed top-4 right-4 z-[9999] space-y-3">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'flex items-start gap-3 min-w-[320px] max-w-md p-4 bg-white rounded-lg shadow-lg border-l-4',
          {
            'border-red-500': toast.priority === 'urgent',
            'border-yellow-500': toast.priority === 'high',
            'border-blue-500': toast.priority === 'medium',
            'border-green-500': toast.priority === 'low'
          }
        ]"
      >
        <component :is="getNotificationIcon(toast.type)" :size="24" class="flex-shrink-0 mt-0.5" />
        <div class="flex-1 min-w-0">
          <h4 class="text-sm font-semibold text-gray-900 mb-1">{{ toast.title }}</h4>
          <p class="text-sm text-gray-600">{{ toast.message }}</p>
        </div>
        <button @click="removeToast(toast.id)" class="flex-shrink-0 text-gray-400 hover:text-gray-600">
          <X :size="20" />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useUserNotifications } from '@/composables/useRealtime'
import { useAuthStore } from '@/stores/auth'
import {
  Bell,
  AlertTriangle,
  ArrowUp,
  Info,
  CheckCircle,
  X
} from 'lucide-vue-next'

const notificationsStore = useNotificationsStore()
const authStore = useAuthStore()

const showPanel = ref(false)

// Computed
const notifications = computed(() => notificationsStore.notifications)
const toasts = computed(() => notificationsStore.toasts)
const unreadCount = computed(() => notificationsStore.unreadCount)
const isLoading = computed(() => notificationsStore.isLoading)

// Setup realtime notifications (must be called during setup, not in onMounted)
const userId = authStore.user?.id
if (userId) {
  useUserNotifications(userId, (event) => {
    console.log('📬 Nueva notificación:', event.notification)
    notificationsStore.addNotification(event.notification)
    notificationsStore.showToast(event.notification)
  })
}

// Methods
function togglePanel() {
  console.log('🔔 Toggle panel clicked, current state:', showPanel.value)
  showPanel.value = !showPanel.value
  console.log('🔔 New state:', showPanel.value)
}

function closePanel() {
  showPanel.value = false
}

function markAllAsRead() {
  notificationsStore.markAllAsRead()
}

async function handleNotificationClick(notification) {
  if (!notification.is_read) {
    await notificationsStore.markAsRead(notification.id)
  }

  // Navigate to task if exists
  if (notification.task_id) {
    // router.push({ name: 'task', params: { id: notification.task_id } })
    closePanel()
  }
}

function removeToast(toastId) {
  notificationsStore.removeToast(toastId)
}

function getNotificationIcon(type) {
  const icons = {
    sla_warning: AlertTriangle,
    sla_escalation: ArrowUp,
    sla_escalation_notice: Info,
    task_assigned: Bell,
    task_completed: CheckCircle
  }
  return icons[type] || Bell
}

function formatDate(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Ahora'
  if (minutes < 60) return `Hace ${minutes}m`
  if (hours < 24) return `Hace ${hours}h`
  if (days < 7) return `Hace ${days}d`
  return date.toLocaleDateString('es-ES')
}

// Click outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}

// Initialize - Load notifications on mount
onMounted(async () => {
  try {
    await notificationsStore.fetchNotifications()
  } catch (error) {
    console.error('Error loading notifications:', error)
  }
})
</script>

<style scoped>
.notification-center {
  position: relative;
}

/* Slide fade transition */
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}

/* Toast transitions */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s;
}

/* Line clamp */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
