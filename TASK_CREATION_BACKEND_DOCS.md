# Sistema de Creación de Tareas SuiteCRM v4.1 - Documentación Técnica Backend

## 📋 Resumen de Cambios

### 1. **TaskRequest.php** (NEW)
- Validaciones completas para creación de tareas
- Soporte para fechas en formatos: `Y-m-d H:i:s`, `datetime-local`, `ISO 8601`
- Validación de parent_type (Cases/Opportunities)
- Validación de date_start <= date_due

### 2. **TaskController.php** (UPDATED)
- Método `store()` completamente reescrito
- Integración con SuiteCRM para crear tareas
- Sincronización automática bidireccional
- Manejo de errores y logging

### 3. **SugarCRMApiAdapter.php** (NOT NEEDED)
- Ya existe el estructura necesaria
- Se utilizan requests HTTP directos en TaskController
- Compatible con SuiteCRM REST API v4.1

---

## 🔗 Rutas de API

La ruta ya existe en `routes/api.php`:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tasks', TaskController::class);
});
```

### Endpoints disponibles:

| Método | Ruta | Controlador | Descripción |
|--------|------|-------------|-------------|
| POST | `/api/v1/tasks` | `TaskController@store` | Crear nueva tarea |
| GET | `/api/v1/tasks` | `TaskController@index` | Listar tareas |
| GET | `/api/v1/tasks/{id}` | `TaskController@show` | Ver detalle de tarea |
| PUT | `/api/v1/tasks/{id}` | `TaskController@update` | Actualizar tarea |
| DELETE | `/api/v1/tasks/{id}` | `TaskController@destroy` | Eliminar tarea |

---

## 📤 Estructura del Request

### POST `/api/v1/tasks`

```json
{
  "title": "Contactar cliente",
  "description": "Llamar para confirmar detalles del proyecto",
  "priority": "High",
  "date_start": "2026-01-09 14:30:00",
  "date_due": "2026-01-10 17:00:00",
  "parent_type": "Cases",
  "parent_id": "123",
  "completion_percentage": 0,
  "assigned_user_id": 2,
  "sweetcrm_assigned_user_id": "user_id_from_suite",
  "flow_id": null,
  "parent_task_id": null
}
```

### Headers Requeridos

```
Authorization: Bearer {token_sanctum}
Content-Type: application/json
```

---

## 📥 Estructura de Respuesta

### 201 Created - Éxito

```json
{
  "success": true,
  "message": "Tarea creada exitosamente",
  "data": {
    "id": 456,
    "title": "Contactar cliente",
    "description": "Llamar para confirmar detalles del proyecto",
    "priority": "High",
    "status": "Not Started",
    "case_id": 123,
    "opportunity_id": null,
    "assigned_user_id": 2,
    "created_by": 2,
    "sweetcrm_id": "abc123xyz789",
    "sweetcrm_synced_at": "2026-01-09T14:30:00Z",
    "sweetcrm_parent_type": "Cases",
    "sweetcrm_parent_id": "123",
    "date_entered": "2026-01-09T14:30:00Z",
    "date_modified": "2026-01-09T14:30:00Z",
    "created_at": "2026-01-09T14:30:00Z",
    "updated_at": "2026-01-09T14:30:00Z",
    "assignee": {
      "id": 2,
      "name": "jramirez",
      "email": "jramirez@icontel.cl"
    },
    "crmCase": {
      "id": 123,
      "case_number": "7452",
      "subject": "Integramundo-Baja de servicio",
      "status": "Abierto"
    },
    "crmCase.client": {
      "id": 45,
      "name": "Integramundo",
      "sweetcrm_id": "account_123"
    }
  }
}
```

### 404 Not Found - Caso/Oportunidad no existe

```json
{
  "success": false,
  "message": "Caso no encontrado"
}
```

### 422 Unprocessable Entity - Validación fallida

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["El título de la tarea es requerido"],
    "priority": ["La prioridad debe ser High, Medium o Low"],
    "date_start": ["La fecha de inicio es requerida"],
    "date_due": ["La fecha de término debe ser posterior o igual a la fecha de inicio"],
    "parent_type": ["El tipo de padre debe ser Cases u Opportunities"]
  }
}
```

### 500 Server Error

```json
{
  "success": false,
  "message": "Error al crear la tarea: {error_message}"
}
```

---

## 🔄 Flujo de Creación

```
Frontend (TaskCreateModal)
    ↓
    POST /api/v1/tasks + datos
    ↓
Backend (TaskController@store)
    ↓
    1. Validar datos (TaskRequest)
    ↓
    2. Verificar que Case/Opportunity existe
    ↓
    3. Crear tarea en BD local (Task model)
    ↓
    4. Obtener sesión SuiteCRM
    ↓
    5. Llamar set_entry en SuiteCRM
    ↓
    6. Actualizar tarea local con sweetcrm_id
    ↓
    7. Retornar datos completos con relaciones
    ↓
Frontend (TaskCreateModal)
    ↓
    Emit event 'task-created'
    ↓
Vue Store (tasksStore)
    ↓
    Actualizar lista de tareas
    ↓
CasesView / OpportunitiesView
    ↓
    Refrescar UI (opcional)
```

---

## 🔐 Validaciones Implementadas

### En TaskRequest.php

1. **title**: Required, string, max 255 caracteres
2. **description**: Optional, string, max 2000 caracteres
3. **priority**: Required, IN {High, Medium, Low}
4. **status**: Optional, IN {Not Started, In Progress, Completed, Pending Input, Deferred}
5. **date_start**: Required, formato Y-m-d H:i:s, antes o igual a date_due
6. **date_due**: Required, formato Y-m-d H:i:s, después o igual a date_start
7. **parent_type**: Required, IN {Cases, Opportunities}
8. **parent_id**: Required, string max 36 caracteres
9. **assigned_user_id**: Optional, exists in users table
10. **sweetcrm_assigned_user_id**: Optional, string max 36
11. **completion_percentage**: Optional, integer 0-100
12. **flow_id**: Optional, exists in flows table
13. **parent_task_id**: Optional, exists in tasks table

### En TaskController@store

1. Usuario autenticado ✅
2. Case/Opportunity existe en BD ✅
3. Case/Opportunity está en SuiteCRM (via sweetcrm_id) ✅

---

## 📊 Mapeo de Campos SuiteCRM

Las tareas se crean en SuiteCRM con los siguientes campos:

```php
$nameValueList = [
    'name' => 'Título de la tarea',
    'description' => 'Descripción',
    'priority' => 'High|Medium|Low',
    'status' => 'Not Started|In Progress|Completed|Pending Input|Deferred',
    'date_start' => '2026-01-09 14:30:00',  // Y-m-d H:i:s
    'date_due' => '2026-01-10 17:00:00',    // Y-m-d H:i:s
    'parent_type' => 'Cases|Opportunities',
    'parent_id' => 'ID del caso/oportunidad',
    'parent_name' => 'Nombre del caso/oportunidad',
    'assigned_user_id' => 'sweetcrm_id del usuario',
    'assigned_user_name' => 'Nombre del usuario'
]
```

---

## 🔧 Configuración Necesaria

### En `.env`

```env
SWEETCRM_URL=http://sweetcrm.local
SWEETCRM_USERNAME=admin
SWEETCRM_PASSWORD=password
SWEETCRM_TIMEOUT=30
```

### En `config/services.php`

```php
'sweetcrm' => [
    'url' => env('SWEETCRM_URL'),
    'username' => env('SWEETCRM_USERNAME'),
    'password' => env('SWEETCRM_PASSWORD'),
    'timeout' => env('SWEETCRM_TIMEOUT', 30),
],
```

---

## 🧪 Testing

### Test 1: Crear tarea básica

```bash
curl -X POST http://localhost:8000/api/v1/tasks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Task",
    "priority": "High",
    "date_start": "2026-01-09 14:00:00",
    "date_due": "2026-01-10 17:00:00",
    "parent_type": "Cases",
    "parent_id": "123"
  }'
```

### Test 2: Validación de fechas

```bash
curl -X POST http://localhost:8000/api/v1/tasks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test",
    "priority": "High",
    "date_start": "2026-01-10 17:00:00",
    "date_due": "2026-01-09 14:00:00",  # Error: date_due antes de date_start
    "parent_type": "Cases",
    "parent_id": "123"
  }'
```

### Test 3: Caso no existe

```bash
curl -X POST http://localhost:8000/api/v1/tasks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test",
    "priority": "High",
    "date_start": "2026-01-09 14:00:00",
    "date_due": "2026-01-10 17:00:00",
    "parent_type": "Cases",
    "parent_id": "999"  # No existe
  }'
```

---

## 📝 Archivos Modificados

| Archivo | Estado | Cambios |
|---------|--------|---------|
| `app/Http/Requests/TaskRequest.php` | ✅ NUEVO | Validaciones completas |
| `app/Http/Controllers/Api/TaskController.php` | ✅ ACTUALIZADO | store() mejorado |
| `app/Models/Task.php` | ✅ COMPATIBLE | Sin cambios necesarios |
| `app/Models/CrmCase.php` | ✅ COMPATIBLE | Sin cambios necesarios |
| `routes/api.php` | ✅ COMPATIBLE | Sin cambios necesarios |

---

## 🚀 Próximas Mejoras

1. **Event Broadcasting**: Notificar en tiempo real cuando se crea una tarea
2. **Webhooks SuiteCRM**: Sincronizar cambios desde SuiteCRM al backend
3. **Task Templates**: Permitir crear tareas desde plantillas predefinidas
4. **Bulk Creation**: Crear múltiples tareas a la vez
5. **Task Dependencies**: Permitir dependencias entre tareas al crear
6. **SLA Integration**: Calcular SLA automáticamente basado en fechas

---

## 📚 Referencias

- [SuiteCRM REST API v4.1](https://docs.suitecrm.com/developer/api/methods/set_entry/)
- [Laravel Form Requests](https://laravel.com/docs/11.x/validation#form-request-validation)
- [Eloquent ORM](https://laravel.com/docs/11.x/eloquent)

