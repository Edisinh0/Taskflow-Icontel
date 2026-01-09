# ✅ TaskCreateModal.vue - Estado Actual

**Estado**: COMPLETAMENTE IMPLEMENTADO
**Ubicación**: `taskflow-frontend/src/components/TaskCreateModal.vue`
**Última Actualización**: 2026-01-09

---

## 📋 Especificaciones Cumplidas

### ✅ Props Configurables

El componente recibe automáticamente:

```javascript
defineProps({
  isOpen: Boolean,           // Control de apertura/cierre
  parentId: String,          // ID del Caso u Oportunidad
  parentType: String,        // 'Cases' o 'Opportunities'
})
```

**Validación**: El `parentType` solo acepta 'Cases' o 'Opportunities'

### ✅ Interfaz UI (Tailwind CSS)

Campos implementados:

1. **Nombre de la Tarea** (required)
   - Input de texto
   - Máximo 255 caracteres
   - Placeholder: "Ej: Contactar cliente"
   - Validación: No puede estar vacío

2. **Prioridad** (required)
   - Select dropdown
   - Opciones: Alta, Media, Baja (values: High, Medium, Low)
   - Default: Medium

3. **Fecha de Inicio** (required)
   - Input datetime-local
   - Formato: YYYY-MM-DDTHH:mm
   - Se establece por defecto a hoy

4. **Fecha de Término** (required)
   - Input datetime-local
   - Formato: YYYY-MM-DDTHH:mm
   - Se establece por defecto a mañana
   - Validación: Debe ser >= fecha de inicio

5. **Descripción** (opcional)
   - Textarea
   - Máximo 2000 caracteres
   - Muestra contador en tiempo real

6. **Porcentaje de Completitud** (opcional)
   - Range slider 0-100%
   - Default: 0%

### ✅ Lógica de Envío

Flujo completo implementado:

```javascript
// 1. Validar datos en cliente
if (!formData.title) errors.title = 'El nombre es requerido'
if (!formData.priority) errors.priority = 'La prioridad es requerida'
// ... etc

// 2. Formatear fechas de datetime-local a Y-m-d H:i:s
formatDateForBackend('2026-01-15T09:00') → '2026-01-15 09:00:00'

// 3. Construir payload con parent automático
const payload = {
  title: formData.title,
  description: formData.description,
  priority: formData.priority,
  date_start: formatDateForBackend(formData.dateStart),
  date_due: formatDateForBackend(formData.dateDue),
  parent_type: props.parentType,      // ← AUTOMÁTICO desde props
  parent_id: props.parentId,           // ← AUTOMÁTICO desde props
  completion_percentage: formData.completionPercentage,
}

// 4. Llamar acción del store
await tasksStore.createTask(payload)

// 5. Refrescar lista y cerrar modal
emit('task-created', response.data)
closeModal()
```

**Nota**: El usuario NO ve ni selecciona `parent_id` ni `parent_type` - se envían automáticamente desde las props.

### ✅ Feedback de Carga

```vue
<!-- Spinner durante carga -->
<span v-if="isLoading" class="inline-block h-4 w-4 animate-spin..."></span>

<!-- Botón deshabilitado durante carga -->
<button type="submit" :disabled="isLoading">
  {{ isLoading ? 'Guardando...' : 'Crear Tarea' }}
</button>
```

### ✅ Eventos Emitidos

El modal emite eventos para que el padre actualice:

```javascript
// Cuando se cierra (por botón cerrar o éxito)
emit('close')

// Cuando la tarea se crea exitosamente
emit('task-created', response.data)
```

---

## 🔌 Integración de Store

### Acción `createTask()` en tasks.js

**Ubicación**: `taskflow-frontend/src/stores/tasks.js` (línea 109)

**Qué hace**:
1. Envía POST a `/api/v1/tasks` con los datos
2. Maneja respuesta success/error
3. Agrega tarea a lista local
4. Retorna respuesta estructurada

**Retorna**:
```javascript
{
  success: true,
  message: 'Tarea creada exitosamente',
  data: { id, title, priority, ... }
}
```

---

## 🎨 Estilos Tailwind Aplicados

| Elemento | Classes |
|----------|---------|
| Modal backdrop | `fixed inset-0 bg-black bg-opacity-50` |
| Modal card | `rounded-lg bg-white shadow-xl` |
| Header | `border-b border-gray-200 px-6 py-4` |
| Input/Select | `rounded-md border border-gray-300 focus:border-blue-500` |
| Textarea | `rounded-md border border-gray-300 w-full` |
| Botones | `inline-flex items-center px-4 py-2` |
| Errors | `text-sm text-red-600` |
| Spinner | `h-4 w-4 animate-spin rounded-full border-2` |

---

## 🚀 Cómo Usar en Componentes Padre

### 1. Importar el componente
```vue
<script setup>
import TaskCreateModal from '@/components/TaskCreateModal.vue'
import { ref } from 'vue'

const showModal = ref(false)
const selectedCaseId = ref(null)
</script>
```

### 2. Usar en template
```vue
<template>
  <!-- Botón para abrir modal -->
  <button @click="showModal = true" class="bg-blue-600 text-white px-4 py-2 rounded">
    Nueva Tarea
  </button>

  <!-- Modal -->
  <TaskCreateModal
    :isOpen="showModal"
    :parentId="selectedCaseId"
    parentType="Cases"
    @close="showModal = false"
    @task-created="handleTaskCreated"
  />
</template>

<script setup>
function handleTaskCreated(task) {
  // Refrescar lista de tareas del caso
  console.log('Tarea creada:', task)
  // Realizar lógica de refresh
}
</script>
```

### 3. Ejemplo con Caso específico
```vue
<!-- En CaseDetailView.vue -->
<TaskCreateModal
  :isOpen="modalOpen"
  :parentId="caseId"  <!-- ej: 'abc-123-xyz' -->
  parentType="Cases"
  @close="modalOpen = false"
  @task-created="reloadCaseTasks"
/>
```

### 4. Ejemplo con Oportunidad
```vue
<!-- En OpportunityDetailView.vue -->
<TaskCreateModal
  :isOpen="modalOpen"
  :parentId="opportunityId"  <!-- ej: 'opp-456-xyz' -->
  parentType="Opportunities"
  @close="modalOpen = false"
  @task-created="reloadOppTasks"
/>
```

---

## 🔍 Flujo de Datos (Diagrama)

```
Usuario abre modal
  ↓
Componente padre pasa props:
  - parentId: "abc-123-xyz"
  - parentType: "Cases"
  ↓
Modal inicializa fechas por defecto
  - date_start: hoy 12:00
  - date_due: mañana 12:00
  ↓
Usuario llena formulario:
  - title: "Seguimiento"
  - priority: "High"
  - description: "Contactar cliente"
  ↓
Usuario clickea "Crear Tarea"
  ↓
Validaciones cliente ✓
  ↓
Formatea fechas:
  - 2026-01-15T09:00 → 2026-01-15 09:00:00
  ↓
Construye payload con parent automático:
  - parent_id: "abc-123-xyz" ← de props
  - parent_type: "Cases" ← de props
  ↓
POST /api/v1/tasks con payload
  ↓
Backend crea tarea
  ↓
Response: { success: true, data: {...} }
  ↓
Modal emite 'task-created'
  ↓
Componente padre recibe evento
  ↓
Modal se cierra
  ↓
Componente padre refresca lista
```

---

## 🧪 Validaciones Implementadas

### Cliente (Frontend)
- ✅ Título requerido
- ✅ Prioridad requerida
- ✅ Fecha inicio requerida
- ✅ Fecha término requerida
- ✅ Fecha inicio ≤ Fecha término
- ✅ Máximo 255 caracteres en título
- ✅ Máximo 2000 caracteres en descripción

### Backend (API)
- ✅ Formato Y-m-d H:i:s para fechas
- ✅ parent_id existe en BD
- ✅ parent_type válido (Cases/Opportunities)
- ✅ Prioridad válida (High/Medium/Low)
- ✅ Campos requeridos presentes

---

## 📊 Datos de Respuesta

Cuando la tarea se crea exitosamente, el modal recibe:

```javascript
{
  "success": true,
  "message": "Tarea creada exitosamente",
  "data": {
    "id": 456,                              // ID local
    "title": "Seguimiento con cliente",
    "description": "Contactar para feedback",
    "priority": "High",
    "status": "Not Started",
    "date_start": "2026-01-15 09:00:00",
    "date_due": "2026-01-20 17:00:00",
    "sweetcrm_id": "task-456-xyz",          // ID en SuiteCRM
    "sweetcrm_synced_at": "2026-01-09...",
    "case_id": 12,                          // Relación local
    "crmCase": {                            // Datos del caso
      "id": 12,
      "case_number": "2026-001",
      "subject": "Proyecto ABC"
    }
  }
}
```

---

## 🔄 Ejemplo de Integración Completa

### Archivo: `CaseDetailView.vue`

```vue
<template>
  <div>
    <h1>Caso: {{ case.subject }}</h1>

    <!-- Botón para abrir modal -->
    <button
      @click="showTaskModal = true"
      class="bg-blue-600 text-white px-4 py-2 rounded"
    >
      + Nueva Tarea
    </button>

    <!-- Modal -->
    <TaskCreateModal
      :isOpen="showTaskModal"
      :parentId="case.id"
      parentType="Cases"
      @close="showTaskModal = false"
      @task-created="handleTaskCreated"
    />

    <!-- Lista de tareas del caso -->
    <div>
      <h3>Tareas</h3>
      <ul>
        <li v-for="task in caseTasks" :key="task.id">
          {{ task.title }} - {{ task.priority }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import TaskCreateModal from '@/components/TaskCreateModal.vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const showTaskModal = ref(false)
const case_ = ref({})
const caseTasks = ref([])

// Cargar caso
onMounted(async () => {
  const response = await api.get(`/cases/${route.params.id}`)
  case_.value = response.data
  loadCaseTasks()
})

// Cargar tareas del caso
async function loadCaseTasks() {
  const response = await api.get(`/tasks?case_id=${case_.value.id}`)
  caseTasks.value = response.data.data
}

// Cuando se crea tarea
function handleTaskCreated(newTask) {
  // Agregar a lista o recargar
  caseTasks.value.unshift(newTask)
  showTaskModal.value = false
}
</script>
```

---

## 🎯 Resumen

| Aspecto | Estado |
|--------|--------|
| Props (parentId, parentType) | ✅ Implementado |
| UI (campos, fechas, descripción) | ✅ Implementado |
| Validación cliente | ✅ Implementado |
| Formateo de fechas | ✅ Implementado |
| Envío automático de parent | ✅ Implementado |
| Spinner durante carga | ✅ Implementado |
| Cierre de modal | ✅ Implementado |
| Evento de refresco | ✅ Implementado |
| Integración con store | ✅ Implementado |
| Estilos Tailwind | ✅ Implementado |
| **ESTADO GENERAL** | **✅ 100% COMPLETO** |

---

## 📝 Notas Importantes

1. **Parent automático**: El usuario NO selecciona el parent, se envía automáticamente desde props
2. **Fechas por defecto**: Se cargan automáticamente (hoy y mañana)
3. **Validación doble**: Cliente + backend para máxima seguridad
4. **Sincronización**: Automática con SuiteCRM (el backend se encarga)
5. **Error handling**: Completo con mensajes descriptivos

---

**Componente**: TaskCreateModal.vue
**Estado**: ✅ LISTO PARA PRODUCCIÓN
**Última verificación**: 2026-01-09

