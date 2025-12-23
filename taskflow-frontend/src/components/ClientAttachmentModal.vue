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
                Subir Documento Base
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="space-y-6">
                <div>
                  <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Nombre del Documento</label>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all dark:text-white"
                    placeholder="Ej: Contrato de Servicio 2024"
                  />
                </div>

                <div>
                  <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Categoría</label>
                  <select
                    v-model="form.category"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all dark:text-white"
                  >
                    <option value="contract">Contrato</option>
                    <option value="base_plan">Plano Base / Mapa</option>
                    <option value="invoice">Facturación</option>
                    <option value="general">General / Otro</option>
                  </select>
                </div>

                <div class="relative">
                  <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Archivo</label>
                  <div 
                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 dark:border-slate-700 border-dashed rounded-2xl hover:border-blue-500 transition-colors cursor-pointer"
                    @click="$refs.fileInput.click()"
                  >
                    <div class="space-y-1 text-center">
                      <Upload class="mx-auto h-12 w-12 text-slate-400" />
                      <div class="flex text-sm text-slate-600 dark:text-slate-400">
                        <span class="relative cursor-pointer rounded-md font-bold text-blue-600 hover:text-blue-500">
                          {{ selectedFile ? selectedFile.name : 'Haz clic para subir un archivo' }}
                        </span>
                      </div>
                      <p class="text-xs text-slate-500">PDF, PNG, JPG, DOCX hasta 10MB</p>
                    </div>
                    <input ref="fileInput" type="file" class="hidden" @change="onFileSelected" />
                  </div>
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
                    :disabled="loading || !selectedFile"
                    class="flex-1 px-6 py-3 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition-all disabled:opacity-50"
                  >
                    {{ loading ? 'Subiendo...' : 'Subir Documento' }}
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
import { ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Upload } from 'lucide-vue-next'

const props = defineProps({
  isOpen: Boolean,
  loading: Boolean
})

const emit = defineEmits(['close', 'save'])

const form = ref({
  name: '',
  category: 'general'
})

const selectedFile = ref(null)

const onFileSelected = (event) => {
  selectedFile.value = event.target.files[0]
  if (selectedFile.value && !form.value.name) {
    form.value.name = selectedFile.value.name.split('.').slice(0, -1).join('.')
  }
}

const close = () => {
  selectedFile.value = null
  form.value = { name: '', category: 'general' }
  emit('close')
}

const handleSubmit = () => {
  emit('save', { 
    ...form.value,
    file: selectedFile.value 
  })
}
</script>
