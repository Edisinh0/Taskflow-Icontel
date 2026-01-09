# TaskCreateModal - Ejemplos de Integración Específicos

## 🎯 Ejemplo 1: Integración Mínima en CasesView.vue

### Paso 1: Importar el componente

```javascript
import TaskCreateModal from '@/components/TaskCreateModal.vue'
```

### Paso 2: Agregar estado

```javascript
const isTaskModalOpen = ref(false)
const taskModalParentId = ref(null)
const taskModalParentType = ref('Cases')

// Para el caso seleccionado (suponiendo que ya existe)
// const selectedCase = ref(null)
```

### Paso 3: Crear funciones

```javascript
const openTaskModal = (caseId) => {
  taskModalParentId.value = caseId
  taskModalParentType.value = 'Cases'
  isTaskModalOpen.value = true
}

const closeTaskModal = () => {
  isTaskModalOpen.value = false
}

const onTaskCreated = (newTask) => {
  // Nueva tarea creada, actualizar si es necesario
  console.log('Tarea creada:', newTask)
  // Ejemplo: agregar a una lista local de tareas
  // if (caseDetail.value && caseDetail.value.tasks) {
  //   caseDetail.value.tasks.push(newTask)
  // }
}
```

### Paso 4: Agregar botón en template

Busca donde están las tareas del caso (alrededor de línea 600-700) y agrega:

```vue
<!-- Encabezado de Tareas -->
<div class="flex items-center justify-between mb-4">
  <h3 class="text-lg font-bold text-gray-900">Tareas del Caso</h3>
  <button
    @click="openTaskModal(selectedCase.id)"
    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
  >
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
    </svg>
    Nueva Tarea
  </button>
</div>
```

### Paso 5: Agregar componente modal al final del template

```vue
<!-- Modal de creación de tareas -->
<TaskCreateModal
  :is-open="isTaskModalOpen"
  :parent-id="taskModalParentId"
  :parent-type="taskModalParentType"
  @close="closeTaskModal"
  @task-created="onTaskCreated"
/>
```

---

## 🎯 Ejemplo 2: Integración Avanzada con Refrescar Lista

### Agregar método para refrescar tareas del caso

```javascript
const refreshCaseTasks = async () => {
  if (!selectedCase.value?.id) return
  
  try {
    const response = await api.get(`/cases/${selectedCase.value.id}`)
    const detail = response.data.data || response.data
    
    if (caseDetail.value && detail.tasks) {
      caseDetail.value.tasks = detail.tasks
    }
    
    console.log('Tareas refrescadas:', detail.tasks)
  } catch (error) {
    console.error('Error refrescando tareas:', error)
  }
}

// Actualizar onTaskCreated para refrescar
const onTaskCreated = async (newTask) => {
  console.log('Tarea creada:', newTask)
  await refreshCaseTasks()
}
```

---

## 🎯 Ejemplo 3: Con Notificaciones

### Usar una librería de toast (ejemplo con vue-toast-notification)

```javascript
import { useToast } from 'vue-toast-notification'

const $toast = useToast()

const onTaskCreated = async (newTask) => {
  $toast.success(`Tarea "${newTask.title}" creada exitosamente`)
  await refreshCaseTasks()
}

const closeTaskModal = () => {
  isTaskModalOpen.value = false
}

// También puedes mostrar error si algo falla en el modal
// (el modal ya maneja errores internamente)
```

---

## 🎯 Ejemplo 4: Con Validaciones Previas

```javascript
const openTaskModal = (caseId) => {
  // Validación: solo si el caso está abierto
  if (selectedCase.value?.status === 'Closed') {
    $toast.warning('No puedes crear tareas en un caso cerrado')
    return
  }
  
  // Validación: solo si el usuario es responsable del caso
  if (!canEditCase(selectedCase.value)) {
    $toast.error('No tienes permiso para crear tareas en este caso')
    return
  }
  
  taskModalParentId.value = caseId
  taskModalParentType.value = 'Cases'
  isTaskModalOpen.value = true
}

// Helper para verificar permisos
const canEditCase = (caseObj) => {
  const currentUser = useAuthStore().user
  return caseObj.created_by === currentUser.id || 
         caseObj.assigned_user?.id === currentUser.id
}
```

---

## 🎯 Ejemplo 5: OpportunitiesView.vue

Exactamente lo mismo, solo cambia:

```javascript
const taskModalParentType = ref('Opportunities')

const openTaskModal = (opportunityId) => {
  taskModalParentId.value = opportunityId
  taskModalParentType.value = 'Opportunities'  // ← CAMBIO CLAVE
  isTaskModalOpen.value = true
}
```

---

## 🎯 Ejemplo 6: Modal en Sidebar de Detalle

Si tienes un sidebar con detalles del caso, puedes agregar el botón allí:

```vue
<!-- En el sidebar del caso -->
<div class="sticky top-0 bg-white dark:bg-slate-800 p-4 border-b">
  <h2 class="text-xl font-bold mb-4">{{ selectedCase.subject }}</h2>
  
  <div class="space-y-2 mb-4">
    <button
      @click="openTaskModal(selectedCase.id)"
      class="w-full px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center justify-center gap-2"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Crear Tarea
    </button>
  </div>
</div>

<!-- Modal -->
<TaskCreateModal
  :is-open="isTaskModalOpen"
  :parent-id="selectedCase.id"
  :parent-type="'Cases'"
  @close="closeTaskModal"
  @task-created="onTaskCreated"
/>
```

---

## 🎯 Ejemplo 7: Con Contador de Tareas

```vue
<template>
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-bold">
      Tareas del Caso
      <span class="text-sm text-gray-500 ml-2">
        ({{ caseDetail?.tasks?.length || 0 }})
      </span>
    </h3>
    <button
      @click="openTaskModal(selectedCase.id)"
      :disabled="selectedCase?.status === 'Closed'"
      class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Nueva Tarea
    </button>
  </div>
</template>
```

---

## 🎯 Ejemplo 8: Handleando Errores en el Modal

El modal maneja errores internamente, pero puedes escuchar cambios:

```javascript
// Opción 1: El modal muestra errores automáticamente en rojo
// Opción 2: Escuchar cambios del store
import { useTasksStore } from '@/stores/tasks'

const tasksStore = useTasksStore()

watch(() => tasksStore.error, (newError) => {
  if (newError && isTaskModalOpen.value) {
    console.error('Error en creación de tarea:', newError)
    // El modal ya muestra el error, pero puedes hacer más cosas aquí
  }
})
```

---

## 🎯 Ejemplo 9: Deshabilitar Modal en Ciertos Casos

```javascript
const canCreateTask = computed(() => {
  if (!selectedCase.value) return false
  if (selectedCase.value.status === 'Closed') return false
  if (selectedCase.value.status === 'On Hold') return false
  return true
})

// En el template:
<button
  @click="openTaskModal(selectedCase.id)"
  :disabled="!canCreateTask"
  :title="!canCreateTask ? 'No puedes crear tareas en este caso' : ''"
  class="... disabled:opacity-50 disabled:cursor-not-allowed"
>
  Nueva Tarea
</button>
```

---

## 🎯 Ejemplo 10: Analytics/Logging

```javascript
const onTaskCreated = async (newTask) => {
  // Log para analytics
  console.log('Task created event:', {
    taskId: newTask.id,
    caseId: selectedCase.value.id,
    priority: newTask.priority,
    timestamp: new Date().toISOString(),
  })
  
  // Enviar a servicio de analytics (opcional)
  if (window.gtag) {
    window.gtag('event', 'task_created', {
      case_id: selectedCase.value.id,
      priority: newTask.priority,
    })
  }
  
  $toast.success(`Tarea "${newTask.title}" creada`)
  await refreshCaseTasks()
}
```

---

## ✅ Checklist de Integración

- [ ] Importar `TaskCreateModal`
- [ ] Crear refs: `isTaskModalOpen`, `taskModalParentId`, `taskModalParentType`
- [ ] Crear funciones: `openTaskModal()`, `closeTaskModal()`, `onTaskCreated()`
- [ ] Agregar botón "Nueva Tarea" en template
- [ ] Agregar componente modal al final del template
- [ ] Importar iconos si es necesario
- [ ] Probar crear una tarea
- [ ] Verificar que aparece en la lista
- [ ] Verificar que se sincroniza con SuiteCRM
- [ ] (Opcional) Agregar notificaciones
- [ ] (Opcional) Agregar validaciones previas
- [ ] (Opcional) Agregar refresh de lista

---

## 🧪 Casos de Prueba

### Test 1: Crear tarea básica
1. Abrir caso
2. Clic en "Nueva Tarea"
3. Llenar form
4. Guardar
5. ✅ Tarea aparece en lista

### Test 2: Cancelar
1. Abrir modal
2. Clic en "Cancelar"
3. ✅ Modal cierra sin crear tarea

### Test 3: Validación de fechas
1. Abrir modal
2. Poner fecha_inicio > fecha_fin
3. Intentar guardar
4. ✅ Error mostrado

### Test 4: SuiteCRM sync
1. Crear tarea desde modal
2. En BD: `SELECT * FROM tasks WHERE ... LIMIT 1`
3. ✅ `sweetcrm_id` debe tener valor

