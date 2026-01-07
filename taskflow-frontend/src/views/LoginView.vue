<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-900 relative overflow-hidden px-4">
    <!-- Background Glows -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none -translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none translate-x-1/2 translate-y-1/2"></div>

    <div class="bg-slate-800/40 backdrop-blur-2xl p-10 rounded-[2.5rem] shadow-2xl w-full max-w-md border border-white/5 relative z-10 transition-all">
      <!-- Logo/Título -->
      <div class="text-center mb-10">
        <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-blue-500/20 rotate-3 hover:rotate-0 transition-transform duration-500">
           <LayoutGrid class="text-white w-10 h-10" />
        </div>
        <h1 class="text-3xl font-black text-white mb-2 tracking-tight">TaskFlow</h1>
        <p class="text-slate-400 font-medium">Acceso Corporativo SuiteCRM</p>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label for="identifier" class="block text-slate-400 font-bold mb-2.5 text-xs uppercase tracking-widest pl-1">
            Usuario o Correo
          </label>
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <User class="w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
            </div>
            <input
              id="identifier"
              v-model="credentials.identifier"
              type="text"
              required
              class="w-full pl-12 pr-4 py-4 bg-slate-900/60 border border-slate-700/50 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 text-white placeholder-slate-600 transition-all font-semibold"
              placeholder="tu.usuario"
            />
          </div>
        </div>

        <div>
          <label for="password" class="block text-slate-400 font-bold mb-2.5 text-xs uppercase tracking-widest pl-1">
            Contraseña
          </label>
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <Lock class="w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
            </div>
            <input
              id="password"
              v-model="credentials.password"
              type="password"
              required
              class="w-full pl-12 pr-4 py-4 bg-slate-900/60 border border-slate-700/50 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 text-white placeholder-slate-600 transition-all font-semibold"
              placeholder="••••••••"
            />
          </div>
        </div>

        <!-- Error message -->
        <Transition name="fade">
          <div v-if="authStore.error" class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-2xl flex items-center text-sm font-medium animate-shake">
            <AlertCircle class="w-5 h-5 mr-3 flex-shrink-0" />
            <span>{{ authStore.error }}</span>
          </div>
        </Transition>

        <!-- Botón Login -->
        <button
          type="submit"
          :disabled="authStore.isLoading"
          class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-xl shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-1 active:translate-y-0"
        >
          <span v-if="!authStore.isLoading" class="flex items-center justify-center gap-2">
            Entrar al Sistema
            <ArrowRight class="w-5 h-5" />
          </span>
          <span v-else class="flex items-center justify-center">
            <RefreshCw class="animate-spin h-5 w-5 mr-3" />
            Autenticando...
          </span>
        </button>
      </form>

      <!-- Footer -->
      <div class="mt-12 text-center">
        <div class="flex items-center justify-center gap-2 mb-4 opacity-30">
           <div class="h-[1px] w-8 bg-slate-500"></div>
           <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">TNA Group Technology</p>
           <div class="h-[1px] w-8 bg-slate-500"></div>
        </div>
        <p class="text-[10px] text-slate-500 font-bold tracking-tighter">TASKFLOW V2.0 • INTELIGENCIA OPERATIVA</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { User, Lock, LayoutGrid, ArrowRight, RefreshCw, AlertCircle } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const credentials = ref({
  identifier: '',
  password: '',
})

const handleLogin = async () => {
  try {
    const result = await authStore.login(credentials.value)

    if (result && result.success) {
      await router.push('/dashboard')
    }
  } catch (error) {
    console.error('Error en Login:', error)
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}
.animate-shake { animation: shake 0.4s ease-in-out; }
</style>