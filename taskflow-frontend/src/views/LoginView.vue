<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
      <!-- Logo/Título -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">TaskFlow</h1>
        <p class="text-gray-600">Sistema de Gestión de Tareas</p>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="handleLogin">
        <!-- Email -->
        <div class="mb-4">
          <label for="email" class="block text-gray-700 font-semibold mb-2">
            Correo Electrónico
          </label>
          <input
            id="email"
            v-model="credentials.email"
            type="email"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="usuario@taskflow.com"
          />
        </div>

        <!-- Password -->
        <div class="mb-6">
          <label for="password" class="block text-gray-700 font-semibold mb-2">
            Contraseña
          </label>
          <input
            id="password"
            v-model="credentials.password"
            type="password"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="••••••••"
          />
        </div>

        <!-- Error message -->
        <div v-if="authStore.error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
          {{ authStore.error }}
        </div>

        <!-- Botón Login -->
        <button
          type="submit"
          :disabled="authStore.isLoading"
          class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="!authStore.isLoading">Iniciar Sesión</span>
          <span v-else>Cargando...</span>
        </button>
      </form>

      <!-- Credenciales de prueba -->
      <div class="mt-6 p-4 bg-gray-50 rounded-lg">
        <p class="text-sm text-gray-600 mb-2 font-semibold">Credenciales de prueba:</p>
        <p class="text-xs text-gray-500">Email: admin@taskflow.com</p>
        <p class="text-xs text-gray-500">Password: password123</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const credentials = ref({
  email: '',
  password: '',
})

const handleLogin = async () => {
  const result = await authStore.login(credentials.value)
  
  if (result.success) {
    router.push('/dashboard')
  }
}
</script>
