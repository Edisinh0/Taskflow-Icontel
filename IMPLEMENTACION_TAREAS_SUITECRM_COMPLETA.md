# Implementación Completa: Creación de Tareas con SuiteCRM Legacy v4.1

**Estado**: EN PROGRESO
**Fecha**: 2026-01-09
**Versión**: 1.0

---

## 📋 Resumen Ejecutivo

Actualización integral del sistema de creación de tareas para lograr **100% de compatibilidad con SuiteCRM Legacy v4.1**, incluyendo:

✅ Soporte completo de fechas (`date_start`, `date_due`)
✅ Mapeo explícito de campos en `name_value_list`
✅ Validación strict de parent_type y parent_id
✅ Formato de datetime correcto (Y-m-d H:i:s)
✅ Sincronización bidireccional con SuiteCRM

---

## 📊 Estado Actual del Código

### ✅ Ya Implementado

#### 1. TaskRequest.php
- Validación de fechas con formato Y-m-d H:i:s ✅
- Transformación automática de formatos ISO a SuiteCRM ✅
- Validación de parent_type (Cases/Opportunities) ✅
- Validación de parent_id como string ✅
- Mensajes de error personalizados en español ✅
- Default status = 'Not Started' ✅

**Ubicación**: `taskflow-backend/app/Http/Requests/TaskRequest.php` (114 líneas)

#### 2. Task Model
- Campos fillable para SuiteCRM incluidos ✅
- `sweetcrm_parent_id`, `sweetcrm_parent_type` ✅
- `date_entered`, `date_modified` ✅
- Casts para datetime ✅

**Ubicación**: `taskflow-backend/app/Models/Task.php` (líneas 17-71)

#### 3. TaskController.store()
- Validación del parent (Case/Opportunity) ✅
- Creación de tarea local ✅
- Mapeo de datos a name_value_list ✅
- Llamada a createTaskInSuiteCRM() ✅
- Sincronización de sweetcrm_id ✅

**Ubicación**: `taskflow-backend/app/Http/Controllers/Api/TaskController.php` (líneas 241-367)

---

## 🔧 Mejoras Requeridas

### 1. Mejorar Validación de Parent en TaskController

**Problema**: El controller valida por ID local pero necesita también validar por sweetcrm_id

**Solución**:
```php
// Buscar por ID local O por sweetcrm_id
if ($validated['parent_type'] === 'Cases') {
    $parentRecord = CrmCase::where('id', $validated['parent_id'])
        ->orWhere('sweetcrm_id', $validated['parent_id'])
        ->first();
} else {
    $parentRecord = Opportunity::where('id', $validated['parent_id'])
        ->orWhere('sweetcrm_id', $validated['parent_id'])
        ->first();
}
```

### 2. Completar name_value_list Mapping

**Problema**: El mapeo actual es correcto pero falta documentación de campos opcionales

**Campos Mapeados Actualmente**:
- ✅ name (del título)
- ✅ description
- ✅ priority
- ✅ status
- ✅ date_start (en formato Y-m-d H:i:s)
- ✅ date_due (en formato Y-m-d H:i:s)
- ✅ parent_type
- ✅ parent_id
- ✅ parent_name
- ✅ assigned_user_id (si aplica)
- ✅ assigned_user_name (si aplica)

**Campos Opcionales a Agregar**:
- completion_percentage (si se proporciona)
- contact_id (si se proporciona)

### 3. Validar Formato DateTime en createTaskInSuiteCRM()

**Problema**: No hay validación que asegure el formato Y-m-d H:i:s en la sincronización

**Solución**:
```php
// Convertir fechas a formato SuiteCRM antes de enviar
$nameValueList['date_start']['value'] =
    $this->formatDateForSugarCRM($nameValueList['date_start']['value']);
$nameValueList['date_due']['value'] =
    $this->formatDateForSugarCRM($nameValueList['date_due']['value']);
```

---

## 🎯 Plan de Implementación

### Fase 1: Mejorar Validación en TaskController (30 min)

**Archivo**: `taskflow-backend/app/Http/Controllers/Api/TaskController.php`

**Cambios**:
1. Actualizar lógica de búsqueda de parent para soportar tanto ID local como sweetcrm_id
2. Agregar logging detallado del mapeo de campos
3. Completar name_value_list con campos opcionales

### Fase 2: Agregar Método Helper para Validación (20 min)

**Archivo**: `taskflow-backend/app/Http/Controllers/Api/TaskController.php`

**Nuevo método**: `validateAndFindParentRecord()`
- Unificar la lógica de búsqueda
- Reutilizable en múltiples métodos
- Mejor mantenibilidad

### Fase 3: Crear Servicio de Validación de Tareas (40 min)

**Archivo**: `taskflow-backend/app/Services/TaskValidationService.php` (NUEVA)

**Responsabilidades**:
- Validar parent existe
- Validar fechas en formato correcto
- Validar campos requeridos
- Transformar datos para SuiteCRM
- Generar nombre_value_list completo

### Fase 4: Optimizar createTaskInSuiteCRM() (20 min)

**Archivo**: `taskflow-backend/app/Http/Controllers/Api/TaskController.php`

**Cambios**:
- Validar formato de fechas
- Agregar reintentos automáticos en caso de error
- Logging mejorado
- Manejo de errores específicos de SuiteCRM

### Fase 5: Agregar Testabilidad (30 min)

**Archivos nuevos**:
- `tests/Feature/TaskCreationTest.php`
- `tests/Unit/TaskValidationServiceTest.php`

**Coverage**:
- Creación con fechas válidas
- Rechazo de fechas inválidas
- Validación de parent
- Sincronización con SuiteCRM

---

## 📝 Cambios Detallados por Archivo

### 1. TaskController.php - Método store()

**Línea 256-274**: Actualizar lógica de validación de parent

**Antes**:
```php
if ($validated['parent_type'] === 'Cases') {
    $parentRecord = CrmCase::find($validated['parent_id']);
    if (!$parentRecord) {
        return response()->json([...], 404);
    }
    $validated['case_id'] = $parentRecord->id;
}
```

**Después**:
```php
$parentRecord = $this->validateAndFindParentRecord(
    $validated['parent_type'],
    $validated['parent_id']
);

if (!$parentRecord) {
    return response()->json([
        'success' => false,
        'message' => 'Caso/Oportunidad no encontrado: ' . $validated['parent_id']
    ], 404);
}

// Asignar según tipo
if ($validated['parent_type'] === 'Cases') {
    $validated['case_id'] = $parentRecord->id;
} else {
    $validated['opportunity_id'] = $parentRecord->id;
}
```

**Línea 304-314**: Mejorar name_value_list con validación de fechas

**Agregación**:
```php
// Campos opcionales
if (isset($validated['completion_percentage'])) {
    $nameValueList['completion_percentage'] = [
        'name' => 'completion_percentage',
        'value' => $validated['completion_percentage']
    ];
}

if (isset($validated['contact_id'])) {
    $nameValueList['contact_id'] = [
        'name' => 'contact_id',
        'value' => $validated['contact_id']
    ];
}
```

### 2. TaskController.php - Nuevo Método Helper

**Ubicación**: Después del método store()

**Código**:
```php
/**
 * Validar y encontrar registro parent (Case u Opportunity)
 * Soporta búsqueda por ID local o sweetcrm_id
 */
private function validateAndFindParentRecord(string $parentType, string $parentId)
{
    try {
        if ($parentType === 'Cases') {
            return CrmCase::where('id', $parentId)
                ->orWhere('sweetcrm_id', $parentId)
                ->firstOrFail();
        } else {
            return Opportunity::where('id', $parentId)
                ->orWhere('sweetcrm_id', $parentId)
                ->firstOrFail();
        }
    } catch (\Exception $e) {
        Log::warning("Parent record not found", [
            'parent_type' => $parentType,
            'parent_id' => $parentId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
```

### 3. Crear TaskValidationService.php

**Ubicación**: `taskflow-backend/app/Services/TaskValidationService.php` (NUEVO)

**Responsabilidades**:
- Validar parent existe
- Validar formato de fechas
- Generar name_value_list completo y validado
- Mapear datos locales a SuiteCRM

**Métodos principales**:
```php
class TaskValidationService
{
    public function validateTaskData(array $validated): array
    // Retorna error si hay problema

    public function buildNameValueList(array $validated, array $parentRecord): array
    // Construye name_value_list completo para SuiteCRM

    private function formatDateForSuiteCRM(string $date): string
    // Asegura formato Y-m-d H:i:s

    private function validateParent(string $type, string $id): ?Model
    // Busca parent por ID local o sweetcrm_id
}
```

### 4. createTaskInSuiteCRM() - Mejorado

**Cambios**:
1. Validar formato de fechas antes de enviar
2. Agregar reintentos automáticos
3. Logging más detallado
4. Manejo específico de errores SuiteCRM

```php
private function createTaskInSuiteCRM(
    string $sessionId,
    array $nameValueList,
    int $attempts = 0
): ?string {
    try {
        // Validar fechas antes de enviar
        if (isset($nameValueList['date_start']['value'])) {
            $nameValueList['date_start']['value'] =
                $this->validateDateFormat(
                    $nameValueList['date_start']['value']
                );
        }

        if (isset($nameValueList['date_due']['value'])) {
            $nameValueList['date_due']['value'] =
                $this->validateDateFormat(
                    $nameValueList['date_due']['value']
                );
        }

        $response = Http::timeout(30)
            ->asForm()
            ->post(rtrim(config('services.sweetcrm.url'), '/') . '/service/v4_1/rest.php', [
                'method' => 'set_entry',
                'input_type' => 'JSON',
                'response_type' => 'JSON',
                'rest_data' => json_encode([
                    'session' => $sessionId,
                    'module' => 'Tasks',
                    'name_value_list' => $nameValueList,
                ]),
            ]);

        if (!$response->successful()) {
            // Reintentos automáticos
            if ($attempts < 2) {
                Log::warning('SuiteCRM set_entry failed, retrying', [
                    'status' => $response->status(),
                    'attempt' => $attempts + 1
                ]);
                sleep(2); // Esperar 2 segundos antes de reintentar
                return $this->createTaskInSuiteCRM(
                    $sessionId,
                    $nameValueList,
                    $attempts + 1
                );
            }

            Log::error('SuiteCRM set_entry failed after retries', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['id'])) {
            Log::info('Task created in SuiteCRM successfully', [
                'sweetcrm_id' => $data['id']
            ]);
            return $data['id'];
        }

        return null;

    } catch (\Exception $e) {
        Log::error('Error creating task in SuiteCRM', [
            'error' => $e->getMessage(),
            'attempt' => $attempts
        ]);
        return null;
    }
}

private function validateDateFormat(string $date): string
{
    try {
        $dateObj = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
        if (!$dateObj) {
            $dateObj = new \DateTime($date);
        }
        return $dateObj->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
        Log::warning('Invalid date format', ['date' => $date]);
        return $date; // Devolver tal cual
    }
}
```

---

## 🧪 Testing Checklist

### Unit Tests
- [ ] TaskRequest valida fechas correctamente
- [ ] TaskRequest rechaza fechas inválidas
- [ ] TaskRequest soporta múltiples formatos de fecha
- [ ] TaskRequest valida parent_type
- [ ] TaskRequest valida parent_id

### Integration Tests
- [ ] Crear tarea con Case válido
- [ ] Crear tarea con Opportunity válido
- [ ] Rechaza Case/Opportunity inválido
- [ ] Sincroniza correctamente con SuiteCRM
- [ ] name_value_list tiene formato correcto
- [ ] Fechas se envían en formato Y-m-d H:i:s

### API Tests
- [ ] POST /api/v1/tasks con datos completos
- [ ] Respuesta incluye sweetcrm_id si se sincronizó
- [ ] Respuesta incluye información de parent
- [ ] Errores tienen mensajes descriptivos

---

## 📊 Mapeo de Campos

### Local → SuiteCRM

| Campo Local | Campo SuiteCRM | Formato | Requerido |
|-------------|----------------|---------|-----------|
| title | name | string | ✅ |
| description | description | string | ❌ |
| priority | priority | string | ✅ |
| status | status | string | ✅ |
| estimated_start_at | date_start | Y-m-d H:i:s | ✅ |
| estimated_end_at | date_due | Y-m-d H:i:s | ✅ |
| sweetcrm_parent_type | parent_type | Cases/Opportunities | ✅ |
| sweetcrm_parent_id | parent_id | string (UUID) | ✅ |
| assigned_user_id | assigned_user_id | string (SuiteCRM ID) | ❌ |

---

## 🔐 Validaciones de Seguridad

✅ SQL Injection: Usar Eloquent, no raw queries
✅ XSS: Laravel automático en responses JSON
✅ CSRF: Middleware de Laravel
✅ Auth: Middleware auth:sanctum en rutas
✅ Validación: Laravel FormRequest validation

---

## 📈 Performance

**Optimizaciones**:
- Búsqueda de parent usa índices (id, sweetcrm_id)
- Caché de sesiones SuiteCRM (TTL 1 hora)
- Sincronización asíncrona (fire-and-forget)
- Logging selectivo (no log de datos sensibles)

---

## 🚀 Plan de Despliegue

1. **Backup**: Crear backup de BD antes de cambios
2. **Staging**: Probar cambios en ambiente staging
3. **Testing**: Ejecutar suite completa de tests
4. **Deploy**: Subir cambios a producción
5. **Monitoring**: Observar logs de errores
6. **Rollback**: Estar listo para revertir si hay problemas

---

## 📞 Soporte

Para problemas durante la implementación:

1. **Revisar logs**: `storage/logs/laravel.log`
2. **Verificar config**: `config/services.php` (SuiteCRM credentials)
3. **Testing manual**: Usar curl o Postman
4. **Debugging**: Usar Laravel Telescope (si está disponible)

---

**Próximo paso**: Comenzar Fase 1 de implementación

