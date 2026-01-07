# Investigación: Tareas Delegadas de Jorge en TaskFlow

## Problema Reportado
Jorge Ramírez reportó que tiene 5 tareas delegadas en SweetCRM (7351, 7301, 7447, 7433, "IContel - Computador Vicente") que **no aparecen en su dashboard de TaskFlow**.

## Investigación Realizada

### 1. Estado de Jorge en la Base de Datos

```
Usuario: Jorge Celis (ID: 24)
SweetCRM ID: a3f17344-837b-7520-abf6-5e5e6f094c48
Tareas creadas por Jorge: 66
Tareas delegadas (creadas por él pero asignadas a otros): 0
```

✅ Jorge está en la base de datos
✅ Sus tareas han sido sincronizadas

### 2. Definición de "Tarea Delegada" Implementada

Una tarea es considerada **"delegada"** cuando:
- ✅ `created_by` = Usuario actual (Jorge)
- ✅ `assignee_id` ≠ Usuario actual
- ✅ `assignee_id` ≠ NULL

### 3. Análisis de Tareas de Jorge

**Resultado**: Todas las 66 tareas creadas por Jorge en SweetCRM **están asignadas a Jorge mismo**, no a otros usuarios.

Ejemplos de las tareas que creo:
- "Accuratek - Configuracion de Correos" → Creado por: jcelis, Asignado a: jcelis
- "Retiro Equipamiento GSM" → Creado por: jcelis, Asignado a: jcelis
- "Compra de Disco DVR" → Creado por: jcelis, Asignado a: jcelis
- etc.

**Conclusión**: Según la definición actual, **Jorge no tiene tareas delegadas**.

### 4. Búsqueda de las Tareas Específicas Mencionadas

Se buscaron las 5 tareas por los ID numéricos proporcionados (7351, 7301, 7447, 7433):
- 122 tareas coinciden parcialmente con esos números en SweetCRM
- **Ninguna de ellas está delegada por Jorge** (todas están auto-asignadas)

## Posibles Explicaciones

### Opción 1: Definición Diferente de "Delegadas"
Quizá Jorge espera ver tareas que:
- Él **no creó**, pero que fueron **reasignadas de él a otros**
- O tareas que están en su **cola de seguimiento** pero asignadas a otros

### Opción 2: Las Tareas Fueron Delegadas en SweetCRM Después de la Sincronización
Si Jorge cambió el `assigned_to` en SweetCRM después de que sincronizamos, TaskFlow aún tendría los datos antiguos.

### Opción 3: Las Tareas Están en Oportunidades, No en Casos
El comando de sincronización actualmente solo sincroniza tareas que pertenecen a `Cases`:
```php
'query' => "tasks.parent_type = 'Cases' AND ..."
```

Si las 5 tareas están vinculadas a `Opportunities` en lugar de `Cases`, no serían sincronizadas.

## Recomendaciones

### Acción 1: Aclaración con Jorge
Preguntarle:
1. ¿En SweetCRM, esas 5 tareas están asignadas a él o a otros usuarios actualmente?
2. ¿Él las delegó de sí mismo a otros (cambio de asignado)?
3. ¿O espera ver tareas que le delegaron otros usuarios?
4. ¿Las tareas están vinculadas a Casos o a Oportunidades?

### Acción 2: Verificar en SweetCRM Directamente
```
Login en SweetCRM como Admin
Tasks module → Buscar "7351", "7301", "7447", "7433"
Ver quién está como "Assigned to" en cada una
Ver si están vinculadas a Cases o Opportunities
```

### Acción 3: Redefinir "Delegadas"
Dependiendo de la respuesta de Jorge, podríamos necesitar:

**Opción A**: Cambiar la lógica para mostrar tareas que:
- Fueron asignadas **a otros** pero están en el historial de Jorge
- Requeriría rastrear cambios de asignación

**Opción B**: Incluir tareas de Oportunidades
- Actualizar `SyncSugarCrmCases.php` para sincronizar tareas también de Opportunities

**Opción C**: Interpretación diferente
- Mostrar tareas que están **bajo supervisión** de Jorge (criterio diferente)

## Código Actual (Por Referencia)

### Filtro en DashboardView.vue
```javascript
const delegatedTasks = computed(() => {
    return dashboardStore.allTasksFlat
        .filter(t =>
            t.created_by === authStore.currentUser?.id &&      // Creadas por el usuario
            t.assignee_id &&                                    // Tiene asignado
            t.assignee_id !== authStore.currentUser?.id         // Pero no a él mismo
        )
})
```

### Sincronización en SyncSugarCrmCases.php
```php
$filters = [
    'query' => "tasks.parent_type = 'Cases' AND (tasks.status IS NULL OR tasks.status NOT IN ('Completed', 'Deferred'))",
    ...
];
```

## Próximos Pasos

1. **Confirmar** con Jorge cuáles son exactamente las tareas y a quién están asignadas
2. **Verificar** en SweetCRM directamente si esas tareas existen y quién es el asignado actual
3. **Ajustar** la lógica de sincronización/filtrado según la definición correcta de "delegadas"
4. **Re-sincronizar** si es necesario

## Resumen Técnico

| Métrica | Valor |
|---------|-------|
| Tareas creadas por Jorge | 66 |
| Tareas delegadas (asignadas a otros) | 0 |
| Tareas específicas encontradas (7351, etc.) | 0 coincidencias exactas |
| Sincronización | ✅ Funcionando |
| Campo `created_by` | ✅ Poblado correctamente |

