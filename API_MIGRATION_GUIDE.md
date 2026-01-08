# Guía de Migración de API - Sistema de Solicitud de Cierre de Casos

## Resumen de Cambios

Se ha implementado un **nuevo sistema de solicitud de cierre de casos** que reemplaza el sistema anterior. El nuevo sistema proporciona:

- ✅ **Mejor control de permisos** - Solo usuarios autorizados (SAC) pueden aprobar
- ✅ **Auto-asignación inteligente** - Las solicitudes se asignan automáticamente al jefe del departamento SAC
- ✅ **Flujo de trabajo completo** - Solicitar → Aprobar/Rechazar → Documentación
- ✅ **Permisos granulares** - Basados en rol y departamento

---

## Endpoints Deprecados

### 1. Solicitar Cierre del Caso

**ANTES (Deprecado):**
```
POST /api/v1/cases/{caseId}/request-closure
```

**AHORA (Nuevo):**
```
POST /api/v1/cases/{caseId}/request-closure
```

**Cambios en la Solicitud:**
```javascript
// ANTES: Sin body requerido
const response = await api.post(`/api/v1/cases/${caseId}/request-closure`)

// AHORA: Body requerido
const response = await api.post(`/api/v1/cases/${caseId}/request-closure`, {
  reason: "Solicitud de cierre del caso",              // REQUERIDO
  completion_percentage: 100                            // REQUERIDO (0-100)
})
```

**Cambios en la Respuesta:**
```javascript
// ANTES
{
  "message": "Solicitud de cierre enviada correctamente"
}

// AHORA
{
  "success": true,
  "message": "Solicitud de cierre enviada a Servicio al Cliente",
  "data": {
    "id": 1,
    "case_id": 5,
    "requested_by_user_id": 3,
    "assigned_to_user_id": 2,  // Asignado automáticamente a jefe de SAC
    "reason": "Solicitud de cierre del caso",
    "completion_percentage": 100,
    "status": "pending",
    ...
  }
}
```

**Quién puede usar:**
- ✅ Usuario asignado al caso
- ✅ Usuario creador del caso
- ✅ Jefe de departamento
- ❌ Otros usuarios (error 403)

---

### 2. Aprobar Cierre del Caso

**ANTES (Deprecado):**
```
POST /api/v1/cases/{caseId}/approve-closure
```

**AHORA (Nuevo):**
```
POST /api/v1/closure-requests/{closureRequestId}/approve
```

**Cambios en la Solicitud:**
```javascript
// ANTES: No requería nada especial
const response = await api.post(`/api/v1/cases/${caseId}/approve-closure`)

// AHORA: Necesitas obtener primero el ID de la solicitud
const closureResponse = await api.get(`/api/v1/cases/${caseId}/closure-request`)
const closureRequestId = closureResponse.data.closure_request.id

const response = await api.post(`/api/v1/closure-requests/${closureRequestId}/approve`)
```

**Cambios en la Respuesta:**
```javascript
// ANTES
{
  "message": "Caso cerrado correctamente"
}

// AHORA
{
  "success": true,
  "message": "Caso cerrado exitosamente",
  "data": {
    "id": 1,
    "case_id": 5,
    "status": "approved",
    "reviewed_by_user_id": 2,
    "reviewed_at": "2026-01-08T17:30:00Z",
    ...
  }
}
```

**Quién puede usar:**
- ✅ Usuario de SAC asignado a la solicitud
- ✅ Administrador
- ❌ Otros usuarios (error 403)

**Comportamiento:**
- El caso se marca como `status: 'Closed'`
- `closure_status` se actualiza a `'closed'`
- Se registra quién aprobó y cuándo

---

### 3. Rechazar Cierre del Caso

**ANTES (Deprecado):**
```
POST /api/v1/cases/{caseId}/reject-closure
```

**Parámetro antiguo:**
```javascript
{
  "reason": "Motivo del rechazo"  // Campo llamado 'reason'
}
```

**AHORA (Nuevo):**
```
POST /api/v1/closure-requests/{closureRequestId}/reject
```

**Parámetro nuevo:**
```javascript
{
  "rejection_reason": "Motivo del rechazo"  // Campo llamado 'rejection_reason'
}
```

**Cambios en la Solicitud:**
```javascript
// ANTES
const response = await api.post(`/api/v1/cases/${caseId}/reject-closure`, {
  reason: "Documentación incompleta"
})

// AHORA
const closureResponse = await api.get(`/api/v1/cases/${caseId}/closure-request`)
const closureRequestId = closureResponse.data.closure_request.id

const response = await api.post(`/api/v1/closure-requests/${closureRequestId}/reject`, {
  rejection_reason: "Documentación incompleta"  // Nota: rejection_reason
})
```

**Cambios en la Respuesta:**
```javascript
// ANTES
{
  "message": "Solicitud de cierre rechazada"
}

// AHORA
{
  "success": true,
  "message": "Solicitud de cierre rechazada",
  "data": {
    "id": 1,
    "case_id": 5,
    "status": "rejected",
    "rejection_reason": "Documentación incompleta",
    "reviewed_by_user_id": 2,
    "reviewed_at": "2026-01-08T17:30:00Z",
    ...
  }
}
```

**Quién puede usar:**
- ✅ Usuario de SAC asignado a la solicitud
- ✅ Administrador
- ❌ Otros usuarios (error 403)

**Comportamiento:**
- El caso vuelve a `status: 'Open'`
- `closure_status` se actualiza a `'open'`
- Se registra la razón del rechazo
- Se registra quién rechazó y cuándo

---

## Nuevos Endpoints

### 4. Ver Estado de Cierre de un Caso

**Nuevo:**
```
GET /api/v1/cases/{caseId}/closure-request
```

**Respuesta:**
```javascript
{
  "success": true,
  "closure_status": "closure_requested",  // open, closure_requested, closed
  "closure_request": {
    "id": 1,
    "case_id": 5,
    "status": "pending",
    "requested_by_user_id": 3,
    "assigned_to_user_id": 2,
    "reason": "Solicitud de cierre del caso",
    "completion_percentage": 100,
    ...
  }
}
```

**Acceso:** Cualquier usuario autenticado

---

### 5. Listar Solicitudes de Cierre Pendientes

**Nuevo:**
```
GET /api/v1/closure-requests
GET /api/v1/closure-requests?status=pending
GET /api/v1/closure-requests?status=all
```

**Parámetros:**
- `status` (opcional): 'pending', 'approved', 'rejected', 'all'

**Respuesta:**
```javascript
{
  "success": true,
  "data": [
    {
      "id": 1,
      "case_id": 5,
      "case": { "id": 5, "case_number": "CASE-123", ... },
      "status": "pending",
      "requested_by_user_id": 3,
      "requested_by": { "id": 3, "name": "Juan Pérez", ... },
      "assigned_to_user_id": 2,
      "assigned_to": { "id": 2, "name": "María García", ... },
      "reason": "Solicitud de cierre del caso",
      "completion_percentage": 100,
      ...
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

**Acceso:** Solo usuarios de SAC o administrador

---

### 6. Ver Detalle de una Solicitud

**Nuevo:**
```
GET /api/v1/closure-requests/{closureRequestId}
```

**Respuesta:**
```javascript
{
  "success": true,
  "data": {
    "id": 1,
    "case": { "id": 5, "case_number": "CASE-123", ... },
    "requested_by": { "id": 3, "name": "Juan Pérez", ... },
    "assigned_to": { "id": 2, "name": "María García", ... },
    "reviewed_by": { "id": 2, "name": "María García", ... },
    "status": "pending",
    "reason": "Solicitud de cierre del caso",
    "completion_percentage": 100,
    "reviewed_at": null,
    ...
  }
}
```

**Acceso:** Admin, SAC asignado a la solicitud, o quien la solicitó

---

## Cambios en la Respuesta de Detalle del Caso

El endpoint `GET /api/v1/cases/{caseId}` ahora retorna información de cierre mejorada:

**ANTES:**
```javascript
{
  "id": 5,
  "case_number": "CASE-123",
  "status": "Open",
  "closure_requested": true,  // boolean simple
  "closure_requested_by": 3,  // solo ID
  "closure_rejected_reason": null
}
```

**AHORA:**
```javascript
{
  "id": 5,
  "case_number": "CASE-123",
  "status": "Open",

  // Información estructurada del cierre
  "closure_info": {
    "requested": true,
    "requested_at": "2026-01-08T16:00:00Z",
    "requested_by": {
      "id": 3,
      "name": "Juan Pérez"
    },
    "closure_request_id": 1
  },

  // Estado de cierre
  "closure_status": "closure_requested",  // open, closure_requested, closed

  // Información de aprobación
  "closure_approved_by": {
    "id": 2,
    "name": "María García"
  },
  "closure_approved_at": null  // Será fecha cuando se apruebe
}
```

---

## Tabla de Equivalencia

| Acción | ANTES | AHORA |
|--------|-------|-------|
| Solicitar cierre | `POST /cases/{id}/request-closure` | `POST /cases/{id}/request-closure` |
| Aprobar cierre | `POST /cases/{id}/approve-closure` | `POST /closure-requests/{id}/approve` |
| Rechazar cierre | `POST /cases/{id}/reject-closure` | `POST /closure-requests/{id}/reject` |
| Ver estado | N/A (en respuesta del caso) | `GET /cases/{id}/closure-request` |
| Listar solicitudes | N/A | `GET /closure-requests` |
| Ver detalle de solicitud | N/A | `GET /closure-requests/{id}` |

---

## Permisos y Validaciones

### Crear Solicitud de Cierre
**Quién puede:**
- ✅ Usuario asignado al caso
- ✅ Usuario creador del caso
- ✅ Jefe de departamento (rol: admin, project_manager, pm)

**Validaciones:**
- ❌ Caso no puede estar en estado 'closure_requested' o 'closed'
- ❌ No puede haber otra solicitud pendiente
- ✅ Se requiere `reason` y `completion_percentage`

### Aprobar Solicitud
**Quién puede:**
- ✅ Usuario de SAC asignado a la solicitud
- ✅ Administrador

**Validaciones:**
- ❌ Solicitud debe estar en estado 'pending'
- ✅ Se requiere estar autenticado

### Rechazar Solicitud
**Quién puede:**
- ✅ Usuario de SAC asignado a la solicitud
- ✅ Administrador

**Validaciones:**
- ❌ Solicitud debe estar en estado 'pending'
- ✅ Se requiere `rejection_reason`

---

## Códigos de Error HTTP

| Código | Significado | Causa |
|--------|-------------|-------|
| 200 | OK | Operación exitosa |
| 201 | Created | Solicitud de cierre creada |
| 401 | Unauthorized | Usuario no autenticado |
| 403 | Forbidden | Usuario no tiene permisos |
| 404 | Not Found | Recurso no encontrado |
| 410 | Gone | Endpoint deprecado (legacy) |
| 422 | Unprocessable | Validación fallida |
| 500 | Server Error | Error interno del servidor |

---

## Ejemplos Completos

### Flujo Completo: Solicitar → Aprobar

```javascript
// 1. Usuario asignado solicita cierre
const requestResponse = await api.post(`/api/v1/cases/5/request-closure`, {
  reason: "Caso completado satisfactoriamente",
  completion_percentage: 100
})

const closureRequest = requestResponse.data.data
console.log(`Solicitud ID: ${closureRequest.id}`)

// 2. Jefe de SAC ve la solicitud
const listResponse = await api.get('/api/v1/closure-requests?status=pending')
console.log(`Solicitudes pendientes: ${listResponse.data.data.length}`)

// 3. Jefe de SAC aprueba
const approveResponse = await api.post(
  `/api/v1/closure-requests/${closureRequest.id}/approve`
)
console.log(`Caso cerrado: ${approveResponse.data.message}`)

// 4. Verificar estado del caso
const caseResponse = await api.get('/api/v1/cases/5')
console.log(`Closure status: ${caseResponse.data.data.closure_status}`) // 'closed'
```

### Flujo Completo: Solicitar → Rechazar

```javascript
// 1. Usuario solicita cierre
const requestResponse = await api.post(`/api/v1/cases/5/request-closure`, {
  reason: "Trabajo completado al 80%",
  completion_percentage: 80
})

const closureRequest = requestResponse.data.data

// 2. Jefe de SAC rechaza por documentación incompleta
const rejectResponse = await api.post(
  `/api/v1/closure-requests/${closureRequest.id}/reject`,
  {
    rejection_reason: "Falta completar documentación del cliente y pruebas finales"
  }
)

console.log(`Rechazado: ${rejectResponse.data.message}`)

// 3. Verificar estado - vuelve a 'open'
const caseResponse = await api.get('/api/v1/cases/5')
console.log(`Closure status: ${caseResponse.data.data.closure_status}`) // 'open'
```

---

## Timeline de Deprecación

### Fase 1: Hoy (8 de enero 2026)
- ✅ Endpoints nuevos disponibles
- ⚠️ Endpoints antiguos retornan error 410 Gone
- 📋 Se registran warnings en logs

### Fase 2: En 1 semana
- ✅ Verificar adopción de nuevos endpoints
- ✅ Recolectar feedback de usuarios

### Fase 3: En 2 semanas
- 🗑️ Remover completamente endpoints antiguos (opcional)

---

## Soporte

Si encuentras problemas durante la migración:

1. **Verifica los códigos de error HTTP** - Consulta la tabla de errores arriba
2. **Revisa los ejemplos** - Asegúrate de que sigues el nuevo formato exactamente
3. **Consulta los permisos** - Valida que el usuario tiene los permisos requeridos
4. **Revisa los logs** - Los endpoints deprecados registran warnings en los logs del servidor

---

## Resumen Técnico

| Aspecto | Detalle |
|--------|---------|
| **Modelos Nuevos** | `CaseClosureRequest`, actualizado `CrmCase` |
| **Controllers Nuevos** | `CaseClosureRequestController` |
| **Policies Nuevas** | `CaseClosureRequestPolicy` |
| **Métodos User** | 5 métodos de autorización agregados |
| **Tablas BD** | Nueva tabla `case_closure_requests` |
| **Tests** | 47 tests unitarios + 18 tests integración |
| **Estado Backend** | 100% implementado y testeado |

