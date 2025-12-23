<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="close" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-3xl bg-white dark:bg-slate-800 p-8 text-left align-middle shadow-2xl transition-all border border-slate-200 dark:border-white/5">
              <DialogTitle as="h3" class="text-2xl font-black text-slate-900 dark:text-white mb-6">
                {{ contact ? 'Editar Contacto' : 'Nuevo Contacto' }}
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Nombre Completo</label>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all dark:text-white"
                    placeholder="Ej: Juan Pérez"
                  />
                </div>

                <div>
                  <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Cargo / Rol</label>
                  <input
                    v-model="form.role"
                    type="text"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all dark:text-white"
                    placeholder="Ej: Gerente IT"
                  />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Email</label>
                    <input
                      v-model="form.email"
                      type="email"
                      class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all dark:text-white"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Teléfono</label>
                    <input
                      v-model="form.phone"
                      type="text"
                      class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all dark:text-white"
                    />
                  </div>
                </div>

                <div class="flex items-center gap-3 py-2">
                  <input
                    v-model="form.is_primary"
                    type="checkbox"
                    id="is_primary"
                    class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                  />
                  <label for="is_primary" class="text-sm font-bold text-slate-700 dark:text-slate-300">Marcar como contacto principal</label>
                </div>

                <div class="mt-8 flex gap-3">
                  <button
                    type="button"
                    @click="close"
                    class="flex-1 px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    :disabled="loading"
                    class="flex-1 px-6 py-3 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all disabled:opacity-50"
                  >
                    {{ loading ? 'Guardando...' : 'Guardar' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const props = defineProps({
  isOpen: Boolean,
  contact: Object,
  loading: Boolean
})

const emit = defineEmits(['close', 'save'])

const form = ref({
  name: '',
  role: '',
  email: '',
  phone: '',
  is_primary: false
})

watch(() => props.contact, (newVal) => {
  if (newVal) {
    form.value = { ...newVal }
  } else {
    form.value = {
      name: '',
      role: '',
      email: '',
      phone: '',
      is_primary: false
    }
  }
}, { immediate: true })

const close = () => emit('close')
const handleSubmit = () => emit('save', { ...form.value })
</script>
