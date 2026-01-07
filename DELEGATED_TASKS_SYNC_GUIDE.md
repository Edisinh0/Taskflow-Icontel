# Guía: Sincronización de Tareas Delegadas

## Resumen
Se ha implementado la funcionalidad para sincronizar y mostrar las **tareas delegadas** que el usuario conectado ha asignado a otros usuarios. Esto permite al delegante tener un seguimiento completo de sus tareas delegadas directamente en el dashboard.

## Cambios Realizados

### 1. Backend - SweetCrmService.php
**Archivo**: `taskflow-backend/app/Services/SweetCrmService.php` (líneas 743-746)

Se agregaron dos campos a la solicitud de tareas desde SweetCRM:
- `assigned_user_name`: Nombre del usuario asignado (para referencia)
- `created_by`: ID del usuario que creó la tarea
- `created_by_name`: Nombre del usuario creador

```php
'select_fields' => [
    // ... otros campos ...
    'assigned_user_id',
    'assigned_user_name',    // NUEVO
    'created_by',            // NUEVO
    'created_by_name',       // NUEVO
    // ... otros campos ...
],
```

### 2. Backend - SyncSugarCrmCases.php
**Archivo**: `taskflow-backend/app/Console/Commands/SyncSugarCrmCases.php` (líneas 254-256, 276)

Se modificó el método `syncTasks()` para:
1. Obtener el usuario creador desde SweetCRM
2. Guardar el campo `created_by` en la tabla `tasks`

```php
// Obtener el usuario creador
$creator = User::where('sweetcrm_id', $nvl['created_by']['value'] ?? '')->first();

// Guardar en la tabla
$task = Task::updateOrCreate(
    ['sweetcrm_id' => $sweetId],
    [
        // ... otros campos ...
        'created_by' => $creator?->id,  // NUEVO
        // ... otros campos ...
    ]
);
```

### 3. Base de Datos - Migración Pendiente
**Archivo**: `taskflow-backend/database/migrations/2026_01_07_add_created_by_to_tasks_table.php`

La migración agrega la columna `created_by` con una relación de llave foránea a la tabla `users`.

## Pasos para Completar la Implementación

### Paso 1: Ejecutar la Migración
En tu ambiente de producción/desarrollo, ejecuta:

```bash
cd taskflow-backend
php artisan migrate
```

Esto creará la columna `created_by` en la tabla `tasks`.

### Paso 2: Ejecutar la Sincronización
Ejecuta el comando de sincronización para poblar el campo `created_by` en todas las tareas existentes:

```bash
php artisan sweetcrm:sync-cases
```

Este comando:
- Obtendrá todas las tareas activas desde SweetCRM
- Buscará el usuario creador en la base de datos local
- Actualizará el campo `created_by` para cada tarea

### Paso 3: Verificar en el Dashboard
1. Inicia sesión en la aplicación
2. Ve al Dashboard
3. Busca la sección **"Tareas y Casos Delegados"** al final de la página
4. Deberías ver todas las tareas que has delegado a otros usuarios

## Cómo Funciona

### En el Frontend
El archivo `DashboardView.vue` ya tiene implementada la lógica para mostrar tareas delegadas:

**Computed Property `delegatedTasks` (líneas 742-750):**
```javascript
const delegatedTasks = computed(() => {
    return dashboardStore.allTasksFlat
        .filter(t =>
            t.created_by === authStore.currentUser?.id &&  // Creadas por el usuario actual
            t.assignee_id &&                               // Asignadas a alguien
            t.assignee_id !== authStore.currentUser?.id    // Pero no a él mismo
        )
        .sort((a, b) => {
            // Ordenar por estado: pendientes primero
            const statusOrder = { 'pending': 0, 'in_progress': 1, 'completed': 2, 'cancelled': 3 }
            return (statusOrder[a.status] || 99) - (statusOrder[b.status] || 99)
        })
})
```

### En la Base de Datos
La tabla `tasks` ahora tiene los campos:
- `created_by`: ID del usuario que creó la tarea
- `assignee_id`: ID del usuario al que se asignó la tarea

Una tarea es "delegada" cuando:
- `created_by` = Usuario actual
- `assignee_id` ≠ Usuario actual

## Información Mostrada en la Tabla

La sección de "Tareas y Casos Delegados" muestra:

| Columna | Descripción |
|---------|------------|
| Asunto / Nombre | Título de la tarea |
| Tipo | Caso, Oportunidad o Tarea |
| Asignado a | Nombre del usuario que ejecutará la tarea |
| Prioridad | Baja, Media, Alta, Urgente |
| Estado | Pendiente, En Progreso, Completada, Cancelada |
| Creado | Fecha de creación |
| Finaliza | Fecha estimada de finalización |

## Estadísticas

En el cuadro de estadísticas superior se muestran:
- **Delegadas**: Total de tareas creadas por el usuario y asignadas a otros
- **Pendientes**: Cantidad de tareas delegadas que aún no están completadas

## Notas Importantes

1. **El campo `created_by` está en el array `$fillable` del modelo Task**, por lo que se guarda correctamente.

2. **La relación `creator()` ya existe en el modelo Task**, permitiendo acceder fácilmente al usuario creador.

3. **El frontend está completamente preparado** - solo necesitaba que el backend sincronizara el campo `created_by`.

4. **La sincronización es automática** - cada vez que ejecutes `php artisan sweetcrm:sync-cases`, los datos se actualizarán.

5. **Las tareas creadas manualmente en TaskFlow** (no desde SweetCRM) también mostrarán `created_by` si los usuarios las crean a través de la interfaz (esto depende de que el controlador de tareas establezca `created_by = auth()->id()`).

## Troubleshooting

### Las tareas delegadas no aparecen en el dashboard

**Posible causa 1**: La migración no se ejecutó
- Verifica: `php artisan migrate:status`
- Si no aparece aplicada, ejecuta: `php artisan migrate`

**Posible causa 2**: El campo `created_by` está vacío
- Verifica que el comando de sincronización se ejecutó correctamente:
  ```bash
  php artisan sweetcrm:sync-cases
  ```
- Revisa los logs: `tail -f storage/logs/laravel.log`

**Posible causa 3**: Los usuarios de SweetCRM no están sincronizados
- Asegúrate que los usuarios en SweetCRM tengan un `sweetcrm_id` correspondiente en la tabla `users` de TaskFlow
- Verifica en la BD: `SELECT sweetcrm_id, name FROM users;`

### Las tareas muestran "Sin asignar"

Esto ocurre si el usuario creador no existe en la tabla `users`. Asegúrate de que:
1. Los usuarios de SweetCRM estén sincronizados en TaskFlow
2. El campo `sweetcrm_id` esté poblado correctamente en ambos sistemas

## Commits Relacionados

- **90b7d2f**: "Feat: Sincronizar tareas delegadas al usuario conectado"
  - Agregados campos `created_by` y `created_by_name` a SweetCrmService
  - Actualizado SyncSugarCrmCases para guardar `created_by`
  - Migración pendiente aplicada al repo

## Archivos Modificados

```
taskflow-backend/
├── app/
│   ├── Console/Commands/SyncSugarCrmCases.php (modificado)
│   └── Services/SweetCrmService.php (modificado)
└── database/
    └── migrations/2026_01_07_add_created_by_to_tasks_table.php (nueva)

taskflow-frontend/
├── src/
│   ├── views/DashboardView.vue (ya estaba implementado)
│   └── stores/dashboard.js (ya estaba implementado)
```

## Próximos Pasos (Opcional)

Si deseas mejorar aún más la funcionalidad:

1. **Añadir filtros**: Permitir que los usuarios filtren tareas delegadas por estado, prioridad, asignado, etc.

2. **Notificaciones**: Crear notificaciones cuando una tarea delegada cambia de estado.

3. **Reportes**: Agregar un reporte de productividad basado en tareas delegadas.

4. **SLA para tareas delegadas**: Rastrear SLA solo para tareas delegadas pendientes.

## Soporte

Si tienes problemas, revisa:
1. Los logs en `storage/logs/laravel.log`
2. Que la base de datos esté conectada correctamente
3. Que los usuarios existan en ambos sistemas (SweetCRM y TaskFlow)
