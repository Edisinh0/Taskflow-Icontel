# Sistema de Solicitud de Cierre de Casos - Documentación de API

## 📋 Descripción General

Sistema MVP que permite:
- **Usuarios Regulares**: Solicitar cierre de casos a SAC
- **Jefes de Área**: Revisar, aprobar o rechazar solicitudes de cierre
- **Casos**: Transición automática a estado "closed" cuando se aprueba

---

## 🔗 Endpoints

### 1. Crear Solicitud de Cierre (Usuario Regular)

**POST** `/api/v1/cases/{caseId}/request-closure`

```json
Request Body:
{
  "reason": "Cliente confirmó que el problema fue resuelto",
  "completion_percentage": 100
}

Response (201):
{
  "success": true,
  "message": "Solicitud de cierre enviada a SAC",
  "data": {
    "id": 1,
    "case_id": 123,
    "requested_by_user_id": 26,
    "assigned_to_user_id": 5,
    "status": "pending",
    "reason": "Cliente confirmó que el problema fue resuelto",
    "completion_percentage": 100,
    "rejection_reason": null,
    "reviewed_by_user_id": null,
    "reviewed_at": null,
    "created_at": "2026-01-08 16:30:00",
    "updated_at": "2026-01-08 16:30:00",
    "requestedBy": {
      "id": 26,
      "name": "Juan Pérez"
    },
    "assignedTo": {
      "id": 5,
      "name": "María José Araneda"
    }
  }
}
```

**Validaciones**:
- `reason`: Requerido, máximo 500 caracteres
- `completion_percentage`: Opcional, 0-100
- El caso debe estar en estado `open`
- No puede haber otra solicitud pendiente para el caso
- Usuario debe ser creador o responsable del caso

---

### 2. Obtener Solicitudes Pendientes (Jefe de Área)

**GET** `/api/v1/closure-requests?status=pending`

```json
Response (200):
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "case_id": 123,
        "status": "pending",
        "reason": "Cliente confirmó que el problema fue resuelto",
        "completion_percentage": 100,
        "created_at": "2026-01-08 16:30:00",
        "case": {
          "id": 123,
          "subject": "Problema con servidor",
          "status": "En Progreso"
        },
        "requestedBy": {
          "id": 26,
          "name": "Juan Pérez"
        }
      }
    ],
    "per_page": 20,
    "total": 1
  }
}
```

**Parámetros**:
- `status`: `pending`, `approved`, `rejected`, o `all` (default: pending)
- Automáticamente filtra por usuario autenticado (a menos que sea admin)

---

### 3. Ver Detalle de Solicitud

**GET** `/api/v1/closure-requests/{id}`

```json
Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "case_id": 123,
    "requested_by_user_id": 26,
    "assigned_to_user_id": 5,
    "status": "pending",
    "reason": "Cliente confirmó que el problema fue resuelto",
    "completion_percentage": 100,
    "rejection_reason": null,
    "reviewed_by_user_id": null,
    "reviewed_at": null,
    "created_at": "2026-01-08 16:30:00",
    "updated_at": "2026-01-08 16:30:00",
    "case": {
      "id": 123,
      "case_number": "CASE-001",
      "subject": "Problema con servidor",
      "status": "En Progreso",
      "closure_status": "closure_requested"
    },
    "requestedBy": {
      "id": 26,
      "name": "Juan Pérez",
      "email": "juan@example.com"
    },
    "assignedTo": {
      "id": 5,
      "name": "María José Araneda",
      "email": "maria@example.com"
    },
    "reviewedBy": null
  }
}
```

---

### 4. Aprobar Solicitud (Jefe de Área)

**POST** `/api/v1/closure-requests/{id}/approve`

```json
Response (200):
{
  "success": true,
  "message": "Caso cerrado exitosamente",
  "data": {
    "id": 1,
    "case_id": 123,
    "status": "approved",
    "reviewed_by_user_id": 5,
    "reviewed_at": "2026-01-08 16:35:00",
    "case": {
      "id": 123,
      "subject": "Problema con servidor",
      "status": "Closed",
      "closure_status": "closed",
      "closure_approved_by_id": 5,
      "closure_approved_at": "2026-01-08 16:35:00"
    },
    "requestedBy": {
      "id": 26,
      "name": "Juan Pérez"
    },
    "reviewedBy": {
      "id": 5,
      "name": "María José Araneda"
    }
  }
}
```

**Cambios en el Caso**:
- `status` → `Closed`
- `closure_status` → `closed`
- `closure_approved_by_id` → ID del jefe
- `closure_approved_at` → Fecha/hora actual

---

### 5. Rechazar Solicitud (Jefe de Área)

**POST** `/api/v1/closure-requests/{id}/reject`

```json
Request Body:
{
  "rejection_reason": "Faltan pruebas de validación del cliente"
}

Response (200):
{
  "success": true,
  "message": "Solicitud de cierre rechazada",
  "data": {
    "id": 1,
    "case_id": 123,
    "status": "rejected",
    "rejection_reason": "Faltan pruebas de validación del cliente",
    "reviewed_by_user_id": 5,
    "reviewed_at": "2026-01-08 16:40:00",
    "case": {
      "id": 123,
      "subject": "Problema con servidor",
      "status": "En Progreso",
      "closure_status": "open",
      "closure_requested_by_id": null,
      "closure_requested_at": null
    }
  }
}
```

**Cambios en el Caso**:
- `closure_status` → `open` (vuelve a estado anterior)
- `closure_requested_by_id` → null
- `closure_requested_at` → null

---

### 6. Ver Estado de Cierre de un Caso

**GET** `/api/v1/cases/{caseId}/closure-request`

```json
Response (200):
{
  "success": true,
  "closure_status": "closure_requested",
  "closure_request": {
    "id": 1,
    "case_id": 123,
    "status": "pending",
    "reason": "Cliente confirmó que el problema fue resuelto",
    "created_at": "2026-01-08 16:30:00",
    "requestedBy": {
      "id": 26,
      "name": "Juan Pérez"
    },
    "assignedTo": {
      "id": 5,
      "name": "María José Araneda"
    }
  }
}
```

---

## 🔐 Permisos

| Endpoint | Usuario Regular | Jefe de Área | Admin |
|----------|-----------------|--------------|-------|
| POST /cases/{id}/request-closure | ✅ | ❌ | ✅ |
| GET /closure-requests | ✅* | ✅ | ✅ |
| GET /closure-requests/{id} | ✅* | ✅ | ✅ |
| POST /closure-requests/{id}/approve | ❌ | ✅ | ✅ |
| POST /closure-requests/{id}/reject | ❌ | ✅ | ✅ |
| GET /cases/{id}/closure-request | ✅ | ✅ | ✅ |

*\*Ver solo sus propias solicitudes*

---

## 📊 Estados del Caso

```
Flujo de Cierre:

open
  └─ Usuario Regular solicita cierre
     └─ closure_status: "closure_requested"
        └─ Jefe de Área aprueba
           └─ closure_status: "closed"
              └─ status: "Closed"

open
  └─ Usuario Regular solicita cierre
     └─ closure_status: "closure_requested"
        └─ Jefe de Área rechaza
           └─ closure_status: "open" (vuelve)
              └─ Usuario puede solicitar nuevamente
```

---

## 🔍 Ejemplos de Uso en Frontend

### Solicitar Cierre (Vue.js)

```javascript
async function requestCaseClosureAsync(caseId, reason, completionPercentage = 100) {
  try {
    const response = await fetch(`/api/v1/cases/${caseId}/request-closure`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        reason,
        completion_percentage: completionPercentage
      })
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Solicitud enviada:', data.data);
      // Refrescar caso
      await fetchCaseDetails(caseId);
    } else {
      console.error(data.message);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### Obtener Solicitudes Pendientes (Jefe de Área)

```javascript
async function getPendingClosureRequests() {
  try {
    const response = await fetch('/api/v1/closure-requests?status=pending', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    const data = await response.json();
    
    if (data.success) {
      return data.data.data; // Array de solicitudes
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### Aprobar Cierre

```javascript
async function approveClosure(requestId) {
  try {
    const response = await fetch(`/api/v1/closure-requests/${requestId}/approve`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Caso cerrado:', data.data);
      // Refrescar lista de solicitudes
      await getPendingClosureRequests();
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### Rechazar Cierre

```javascript
async function rejectClosure(requestId, rejectionReason) {
  try {
    const response = await fetch(`/api/v1/closure-requests/${requestId}/reject`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        rejection_reason: rejectionReason
      })
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Solicitud rechazada:', data.data);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

---

## ⚠️ Códigos de Error

| Código | Mensaje | Causa |
|--------|---------|-------|
| 401 | No autenticado | Token inválido o expirado |
| 403 | No tienes permiso | Usuario no autorizado para la acción |
| 404 | Caso/Solicitud no encontrado | ID inválido |
| 422 | Validación fallida | Datos incompletos o inválidos |
| 422 | Ya existe una solicitud pendiente | Solo una solicitud activa por caso |
| 422 | Esta solicitud ya fue procesada | No se puede cambiar estado dos veces |
| 500 | Error del servidor | Error interno |

---

## 📝 Notas Importantes

1. **Asignación automática**: Las solicitudes se asignan automáticamente al primer usuario con departamento "SAC"
2. **Sin doble procesamiento**: Una solicitud no se puede aprobar/rechazar dos veces
3. **Reintento después de rechazo**: El usuario puede solicitar cierre nuevamente después de un rechazo
4. **Auditoría automática**: Se registran `reviewed_by_user_id` y `reviewed_at` en cada acción
5. **Campos del caso actualizados**: El caso se actualiza automáticamente con información de cierre

---

## 🚀 Próximos Pasos

1. **Notificaciones**: Implementar envío de notificaciones cuando se crea/aprueba/rechaza
2. **Sistema de Roles**: Reemplazar `department` con tabla de roles
3. **Dashboard Jefe**: Vista de solicitudes pendientes con estadísticas
4. **Historial**: Registro completo de todas las solicitudes (aprobadas y rechazadas)
5. **SweetCRM Sync**: Sincronizar estados cerrados de vuelta a SweetCRM
