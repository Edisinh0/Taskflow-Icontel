# Integración de TaskCreateModal - Guía de Implementación

## 📋 Descripción

El componente **TaskCreateModal.vue** ha sido creado para permitir la creación de tareas directamente desde las vistas de Casos y Oportunidades. Es totalmente compatible con SuiteCRM v4.1 y maneja fechas automáticamente.

---

## 🔧 Instalación en CasesView.vue

### 1. Importar el componente

En la sección `<script setup>`:

```javascript
import TaskCreateModal from '@/components/TaskCreateModal.vue'
```

### 2. Agregar el estado para el modal

```javascript
import { ref } from 'vue'

const isTaskModalOpen = ref(false)
const taskModalParentId = ref(null)
const taskModalParentType = ref('Cases')

// Función para abrir modal
const openTaskModal = (caseId) => {
  taskModalParentId.value = caseId
  taskModalParentType.value = 'Cases'
  isTaskModalOpen.value = true
}

// Función para cerrar modal
const closeTaskModal = () => {
  isTaskModalOpen.value = false
}

// Función para manejar nuevo evento de tarea creada
const onTaskCreated = async (newTask) => {
  console.log('Nueva tarea creada:', newTask)
  // Aquí refrescar la lista de tareas del caso si es necesario
  // Por ejemplo, llamar a fetchCaseTasks(taskModalParentId.value)
}
```

### 3. Agregar el botón "Nueva Tarea" en el header de tareas

En el template, dentro de la sección de tareas del detalle del caso (alrededor de línea 600-700):

```vue
<!-- Encabezado de Tareas -->
<div class="flex items-center justify-between mb-4">
  <h3 class="text-lg font-bold text-gray-900 dark:text-white">
    Tareas del Caso
  </h3>
  <button
    @click="openTaskModal(selectedCase.id)"
    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
  >
    <Plus :size="18" />
    Nueva Tarea
  </button>
</div>

<!-- Lista de tareas del caso -->
<div v-if="caseDetail?.tasks" class="space-y-2">
  <div
    v-for="task in caseDetail.tasks"
    :key="task.id"
    class="p-3 bg-white dark:bg-slate-700 rounded-lg border border-gray-200 dark:border-gray-600"
  >
    <div class="flex items-start justify-between">
      <div>
        <p class="font-medium text-gray-900 dark:text-white">{{ task.title }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ task.description }}</p>
      </div>
      <span class="px-2 py-1 text-xs font-bold rounded" :class="getTaskStatusClass(task.status)">
        {{ task.status }}
      </span>
    </div>
  </div>
</div>
```

### 4. Agregar el componente al final del template

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

### 5. Importar iconos necesarios (si no los tienes)

```javascript
import { Plus } from 'lucide-vue-next'
```

---

## 🔧 Instalación en OpportunitiesView.vue

Repetir el mismo proceso, pero cambiar:

```javascript
const openTaskModal = (opportunityId) => {
  taskModalParentId.value = opportunityId
  taskModalParentType.value = 'Opportunities'  // ← Cambiar esto
  isTaskModalOpen.value = true
}
```

---

## 📝 Estructura del Payload Enviado

Cuando el usuario crea una tarea, el componente envía esto al backend:

```json
{
  "title": "Contactar cliente",
  "description": "Llamar para confirmar los detalles del proyecto",
  "priority": "High",
  "date_start": "2026-01-09 14:30:00",
  "date_due": "2026-01-10 17:00:00",
  "parent_type": "Cases",
  "parent_id": "123",
  "completion_percentage": 0
}
```

---

## ✅ Respuesta Esperada del Backend

```json
{
  "success": true,
  "message": "Tarea creada exitosamente",
  "data": {
    "id": 456,
    "title": "Contactar cliente",
    "description": "Llamar para confirmar los detalles del proyecto",
    "priority": "High",
    "status": "Not Started",
    "case_id": 123,
    "sweetcrm_id": "abc123xyz789",
    "sweetcrm_synced_at": "2026-01-09T14:30:00Z",
    "created_at": "2026-01-09T14:30:00Z",
    "assignee": {
      "id": 2,
      "name": "jramirez"
    },
    "crmCase": {
      "id": 123,
      "case_number": "7452",
      "subject": "Integramundo-Baja de servicio"
    }
  }
}
```

---

## 🎨 Personalizaciones Opcionales

### Cambiar colores del modal

En `TaskCreateModal.vue`, edita las clases Tailwind:

```vue
<!-- Botón de guardar -->
<button
  class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
>
```

### Agregar más campos

Si necesitas agregar campos adicionales (ej: assigned_user_id, flow_id), edita en:

1. **TaskRequest.php**: Agregar validaciones
2. **TaskCreateModal.vue**: Agregar input field
3. **TaskController.php**: Mapear al payload

### Desactivar automáticamente el modal tras crear tarea

El componente ya lo hace por defecto, pero puedes modificar el behavior en `submitForm()`:

```javascript
if (response.success) {
  // El modal se cierra automáticamente
  closeModal()
}
```

---

## 🔍 Debugging

### Ver en consola qué datos se envían

Edita el método `submitForm()` en TaskCreateModal.vue:

```javascript
console.log('Payload being sent:', payload)
const response = await tasksStore.createTask(payload)
```

### Verificar que el token esté siendo enviado

En `src/services/api.js`, asegúrate que el header de Authorization está presente:

```javascript
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('token')}`
  }
})
```

---

## 🐛 Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `Cannot read property 'createTask'` | Store no importado | Verificar `import { useTasksStore } from '@/stores/tasks.js'` |
| `422 Validación fallida` | Fechas en formato incorrecto | Component ya convierte `datetime-local` a `Y-m-d H:i:s` |
| `404 Caso no encontrado` | parent_id inválido | Verificar que selectedCase.id sea correcto |
| `Modal no se abre` | isOpen prop no actualizado | Verificar v-if binding |
| `Task no aparece en lista` | No refrescar datos | Agregar lógica de refresh en `onTaskCreated` |

---

## 📊 Testing

### Test 1: Crear tarea básica

1. Abrir un caso en CasesView
2. Hacer clic en "Nueva Tarea"
3. Llenar: Nombre="Test", Prioridad="High", Fechas (auto-rellenadas)
4. Guardar
5. ✅ Tarea debe aparecer en lista

### Test 2: Validar fechas

1. Abrir modal
2. Cambiar "Fecha de Término" a antes de "Fecha de Inicio"
3. Intentar guardar
4. ✅ Error: "La fecha de inicio debe ser anterior..."

### Test 3: Sincronizar con SuiteCRM

1. Crear tarea desde modal
2. Backend debe crear en BD local + SuiteCRM
3. Verificar en base de datos: `SELECT * FROM tasks WHERE title='Test'`
4. ✅ Campo `sweetcrm_id` debe estar poblado

---

## 🚀 Próximos Pasos Opcionales

1. **Notificaciones**: Mostrar toast al crear tarea
2. **Refrescar automático**: Agregar polling de tareas del caso
3. **Asignación**: Agregar dropdown para asignar a usuario
4. **Validación de tareas**: Evitar crear si hay tareas pendientes críticas
5. **Eventos**: Emitir evento global cuando se crea tarea

