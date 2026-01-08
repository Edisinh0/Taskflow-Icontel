# Fix: Tareas no aparecen en Dashboard de JRamirez

**Fecha**: 8 de Enero de 2026  
**Usuario Afectado**: jramirez  
**Tareas Mencionadas**: 4421, 7447, 7412, 7443, 4503

---

## Problema Identificado

El usuario JRamirez tenía **5 tareas** asignadas que deberían mostrarse en el dashboard, pero no aparecían:

| Task ID | Tipo | Caso/Oportunidad | Status BD | Asignado a | Problema |
|---------|------|------------------|-----------|-----------|----------|
| 7447 | Tarea | Caso 7447 | pending | jramirez | ❌ Caso asignado a jramirez - FIJO |
| 7443 | Tarea | Caso 7443 | pending | jramirez | ❌ Caso asignado a jramirez - FIJO |
| 7412 | Tarea | Caso 7412 | pending | jramirez | ❌ **Caso asignado a otro usuario** - FIJO |
| 4421 | Tarea | Oportunidad | N/A | N/A | ❌ No sincronizada (jramirez no es de ventas) |
| 4503 | Tarea | Oportunidad | N/A | N/A | ❌ No sincronizada (jramirez no es de ventas) |

---

## Causas Raíz

### 1. **Filtro de estados incompleto**
**Archivo**: `app/Http/Controllers/Api/DashboardController.php:240`

El filtro original solo consideraba:
```php
$activeTaskStatuses = ['pending', 'in_progress'];
```

Pero debería incluir todos los estados activos:
```php
$activeTaskStatuses = ['pending', 'in_progress', 'blocked', 'paused'];
```

### 2. **No incluía tareas de casos asignados a otros usuarios**
**Archivo**: `app/Http/Controllers/Api/DashboardController.php:220-270`

La lógica original:
1. Traía casos asignados al usuario actual (línea 220)
2. Traía tareas del usuario (línea 243)
3. Agrupaba tareas en casos

**Problema**: Si una tarea del usuario estaba en un caso asignado a **otro usuario**, ese caso no se retornaba y la tarea se mostraba como "huérfana".

**Caso 7412 (c aseid 37)**: La tarea estaba asignada a jramirez, pero el caso estaba asignado a otro usuario (`b908b245-e2d8-2e7e-5e32-5f381ad5f540`), por lo que no aparecía en la consulta inicial de casos.

### 3. **Filtro de estado de casos excluía casos cerrados**
**Archivo**: `app/Http/Controllers/Api/DashboardController.php:220`

```php
$casesQuery = \App\Models\CrmCase::whereNotIn('status', ['Closed', 'Rejected', 'Duplicate', 'Merged']);
```

Esto excluía casos cerrados, lo que es correcto para casos sin tareas activas, pero tareas activas en casos cerrados deberían mostrarse igual.

---

## Soluciones Aplicadas

### 1. **Incluir todos los estados activos de tareas**

**Archivo**: `app/Http/Controllers/Api/DashboardController.php:240-245`

```php
// Antes:
$activeTaskStatuses = ['pending', 'in_progress'];

// Después:
$activeTaskStatuses = ['pending', 'in_progress', 'blocked', 'paused'];
```

### 2. **Agregar lógica para incluir casos con tareas del usuario**

**Archivo**: `app/Http/Controllers/Api/DashboardController.php:265-283`

Se agregó lógica para rastrear `$caseIdsFromTasks` y luego incluir esos casos si no estaban ya en la respuesta:

```php
// Si hay casos que contienen tareas del usuario pero no están en los casos obtenidos,
// agregarlos (Ej: tarea asignada a jramirez pero el caso está asignado a otro usuario)
// También incluir tareas de casos cerrados si la tarea está activa
if ($viewMode === 'my' && !empty($caseIdsFromTasks)) {
    $caseIdsFromTasks = array_unique($caseIdsFromTasks);
    $casesFromTasks = \App\Models\CrmCase::whereIn('id', $caseIdsFromTasks)
        ->whereNotIn('id', $cases->pluck('id')->toArray())
        ->get();
    
    $cases = $cases->merge($casesFromTasks);
}
```

**Cambio importante**: Se eliminó el filtro `whereNotIn('status', ['Closed', 'Rejected', 'Duplicate', 'Merged'])` de esta segunda consulta, permitiendo que casos cerrados se incluyan si tienen tareas activas del usuario.

### 3. **Actualización del usuario JRamirez**

Se actualizó el departamento de jramirez de `null` a `'Operaciones'` para asegurar que se clasifique como usuario de operaciones y no de ventas.

---

## Resultado Esperado

**Después del fix, jramirez debería ver en su dashboard**:
- ✅ **7447** (Tarea: "Seidor - Pruebas de conectividad") - Caso 7447
- ✅ **7443** (Tarea: "Naxi - Problema sitio web") - Caso 7443  
- ✅ **7412** (Tarea: "Anci-Conf.Intercomunicación") - Caso 7412 (aunque esté asignado a otro usuario)

**No aparecerán** (esperado):
- ❌ **4421, 4503** - No sincronizadas (requieren sincronización de oportunidades)

---

## Archivos Modificados

1. **taskflow-backend/app/Http/Controllers/Api/DashboardController.php**
   - Línea 240-245: Incluir estados de tareas adicionales
   - Línea 265-283: Agregar lógica para incluir casos con tareas del usuario

---

## Sincronización Realizada

```bash
docker exec taskflow_app php artisan sweetcrm:sync-cases
# Resultado: 4230 casos sincronizados, 25 tareas sincronizadas
```

---

## Notas Importantes

### Sobre las oportunidades 4421 y 4503
Estas NO aparecerán en el dashboard de jramirez porque:
1. No son tareas en el sistema local, sino oportunidades en SweetCRM
2. El usuario jramirez NO está en el equipo de ventas (department = 'Operaciones')
3. No existe sincronización de oportunidades para usuarios de operaciones

**Si se desea que aparezcan**, se debe:
- Crear un comando `sweetcrm:sync-opportunities` para sincronizar oportunidades
- O clasificar a jramirez como usuario de ventas (si aplica a su rol)
- O crear tareas asociadas a esas oportunidades para usuarios de operaciones

---

## Testing

**Comando para verificar tareas activas de jramirez**:
```sql
SELECT id, title, status, case_id, sweetcrm_id, assignee_id
FROM tasks
WHERE assignee_id = 26
AND status IN ('pending', 'in_progress', 'blocked', 'paused')
ORDER BY id;
```

**Resultado esperado**: 3 filas con las tareas 7447, 7443, 7412

---

## Cambios en el Comportamiento

### Antes
- Solo mostraba tareas de casos que estaban explícitamente asignados al usuario
- Excluía tareas de casos cerrados

### Después
- Muestra tareas de casos asignados al usuario
- Muestra tareas de casos asignados a otros usuarios SI la tarea está asignada al usuario actual
- Muestra tareas activas incluso si el caso está cerrado

Esta es la **solución correcta** porque respeta la responsabilidad del usuario sobre sus tareas, independientemente de quién gestione el caso.

