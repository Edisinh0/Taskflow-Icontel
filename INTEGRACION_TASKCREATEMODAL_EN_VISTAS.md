# 📋 Integración de TaskCreateModal en Vistas de Casos y Oportunidades

**Estado**: PLAN DE IMPLEMENTACIÓN
**Fecha**: 2026-01-09
**Objetivo**: Habilitar creación de tareas directamente desde vistas de detalles

---

## 🎯 Objetivo

Agregar botón "Nueva Tarea" en las secciones de tareas de:
- CaseValidationPanel.vue (tareas de casos en validación)
- Otras vistas de casos/oportunidades (si existen)

Con funcionalidad de:
- Abrir TaskCreateModal.vue
- Pasar `parentId` (ID del caso/oportunidad)
- Pasar `parentType` ('Cases' o 'Opportunities')
- Actualizar lista de tareas automáticamente sin recargar página

---

## 📍 Ubicaciones Identificadas

### 1. CaseValidationPanel.vue
**Ubicación**: `taskflow-frontend/src/components/CaseValidationPanel.vue`
**Sección**: Líneas 52-70 ("Tareas Asociadas")
**Props esperados**: `caseData` (objeto con id, case_number, tasks, etc.)

**Estructura actual**:
```vue
<!-- Tareas asociadas -->
<div v-if="caseData.tasks && caseData.tasks.length > 0" class="...">
  <h4 class="...">Tareas Asociadas ({{ caseData.tasks.length }})</h4>
  <div class="space-y-2">
    <!-- Lista de tareas -->
  </div>
</div>
```

**Dónde agregar botón**:
- Opción A: Junto al h4 (encabezado)
- Opción B: En un nuevo bloque "Sin tareas" cuando no hay tareas

---

## 🔧 Plan de Implementación

### Fase 1: Modificar CaseValidationPanel.vue

1. **Importar TaskCreateModal**
   ```javascript
   import TaskCreateModal from '@/components/TaskCreateModal.vue'
   ```

2. **Importar icono Plus**
   ```javascript
   import { Plus } from 'lucide-vue-next'
   ```

3. **Agregar estado de control del modal**
   ```javascript
   const showTaskModal = ref(false)
   ```

4. **Agregar método para manejar nueva tarea creada**
   ```javascript
   function handleTaskCreated(newTask) {
     // Agregar tarea a caseData.tasks
     if (caseData.value && caseData.value.tasks) {
       caseData.value.tasks.unshift(newTask)
     }
     showTaskModal.value = false
   }
   ```

5. **Agregar componente modal en template**
   ```vue
   <TaskCreateModal
     :isOpen="showTaskModal"
     :parentId="caseData.id"
     parentType="Cases"
     @close="showTaskModal = false"
     @task-created="handleTaskCreated"
   />
   ```

6. **Agregar botón en encabezado de tareas**
   ```vue
   <div class="flex items-center justify-between mb-4">
     <h4 class="text-sm font-bold ...">
       Tareas Asociadas ({{ caseData.tasks.length }})
     </h4>
     <button
       @click="showTaskModal = true"
       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all"
     >
       <Plus :size="18" />
       Nueva Tarea
     </button>
   </div>
   ```

### Fase 2: Agregar estado "Sin tareas"

Si no hay tareas, mostrar:
```vue
<div v-else class="text-center py-8">
  <p class="text-slate-500 dark:text-slate-400 mb-4">
    No hay tareas asociadas a este caso
  </p>
  <button
    @click="showTaskModal = true"
    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700"
  >
    <Plus :size="18" />
    Crear Primera Tarea
  </button>
</div>
```

### Fase 3: Repetir para Oportunidades

Buscar componentes similares para Oportunidades y aplicar mismo patrón:
- Cambiar `parentType` de 'Cases' a 'Opportunities'
- Usar ID de oportunidad en lugar de caso

---

## 📊 Detalles Técnicos

### Props de TaskCreateModal

```javascript
defineProps({
  isOpen: {
    type: Boolean,
    required: true,         // De: showTaskModal ref
  },
  parentId: {
    type: String,
    required: true,         // De: caseData.id
  },
  parentType: {
    type: String,           // De: 'Cases' o 'Opportunities'
    required: true,
    validator: (value) => ['Cases', 'Opportunities'].includes(value),
  },
})
```

### Eventos Emitidos por TaskCreateModal

```javascript
emit('close')              // Cuando cierra el modal
emit('task-created', task) // Cuando tarea se crea exitosamente
```

### Datos de Respuesta

Cuando se emite 'task-created', recibimos:
```javascript
{
  id: 456,
  title: "Seguimiento con cliente",
  description: "Obtener feedback",
  priority: "High",
  status: "Not Started",
  date_start: "2026-01-15 09:00:00",
  date_due: "2026-01-20 17:00:00",
  sweetcrm_id: "task-456-xyz",
  case_id: 12,
  crmCase: { ... }
}
```

---

## ✅ Validaciones

### Frontend
- ✓ Título requerido
- ✓ Prioridad requerida
- ✓ Fechas requeridas y válidas
- ✓ Fecha inicio ≤ Fecha término

### Backend (TaskController)
- ✓ Parent (Case/Opportunity) existe
- ✓ Parent_type válido
- ✓ Fechas en formato Y-m-d H:i:s
- ✓ Sincronización automática con SuiteCRM

---

## 📝 Notas Importantes

1. **Formato de Fechas**:
   - El TaskCreateModal maneja conversión automática de datetime-local a Y-m-d H:i:s
   - Backend valida y formatea adicionalemente antes de enviar a SuiteCRM

2. **Parent ID**:
   - Se pasa automáticamente desde props (no requiere selección del usuario)
   - Garantiza vínculo correcto con Caso/Oportunidad

3. **Actualización de Lista**:
   - Se realiza en el handler `handleTaskCreated`
   - No requiere recargar página
   - Nueva tarea aparece inmediatamente al tope de la lista

4. **Sincronización SuiteCRM**:
   - Automática en el backend (TaskController)
   - TaskCreateModal no necesita conocer detalles de sincronización
   - Respuesta incluye sweetcrm_id si sincronización fue exitosa

---

## 🚀 Beneficios

✅ **UX Mejorada**: Usuario no salta a otra pantalla para crear tarea
✅ **Contexto Automático**: No hay riesgo de seleccionar parent incorrecto
✅ **Sin Recargas**: Actualización en tiempo real de lista
✅ **Consistencia**: Reutiliza mismo componente que en otras vistas

---

## 📋 Próximos Pasos

1. [ ] Modificar CaseValidationPanel.vue
2. [ ] Buscar/Identificar otras vistas de Casos/Oportunidades
3. [ ] Aplicar mismo patrón a todas las vistas
4. [ ] Testing manual:
   - [ ] Crear tarea desde Caso
   - [ ] Crear tarea desde Oportunidad
   - [ ] Verificar lista actualiza sin reload
   - [ ] Verificar sincronización con SuiteCRM
5. [ ] Testing de edge cases:
   - [ ] Crear tarea sin llenar campos (validación)
   - [ ] Crear tarea con fechas inválidas
   - [ ] Crear tarea con parent que no existe

---

## 📖 Documentación Relacionada

- [TASKCREATEMODALSTATUS.md](TASKCREATEMODALSTATUS.md) - Status del componente
- [RESUMEN_IMPLEMENTACION_TAREAS_SUITECRM.md](RESUMEN_IMPLEMENTACION_TAREAS_SUITECRM.md) - Backend details
- [INDICE_DOCUMENTACION_2026.md](INDICE_DOCUMENTACION_2026.md) - Centro de referencias

---

**Estado**: PLAN DEFINIDO - LISTO PARA IMPLEMENTACIÓN
**Prioridad**: ALTA (Mejora de UX)
**Complejidad**: BAJA (Reutiliza componente existente)

