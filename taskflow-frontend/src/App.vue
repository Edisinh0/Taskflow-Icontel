<template>
  <RouterView />
</template>

<script setup>
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { onMounted } from 'vue'

const authStore = useAuthStore()

// Cargar el usuario del localStorage al iniciar la app
onMounted(() => {
  authStore.loadFromStorage()
})
</script>

<style>
/* Asegurar que no haya estilos por defecto */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}
</style>