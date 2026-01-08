# Testing Case Closure Request API - Manual Guide

## 📋 Test Data Available

### Users Created
- **Regular User**: jramirez (ID: 2) - Department: General
- **SAC Jefe 1**: María José Araneda (ID: 3) - Department: SAC
- **SAC Jefe 2**: Daniela Araneda (ID: 4) - Department: SAC

### Cases Available (Open)
- Case 1: 7452 - Integramundo-Baja de servicio
- Case 2: 7451 - EPSA | PROVEEDOR RC | Corte de servicio por reparación
- Case 3: 7450 - CASA MUSA - Falla anexos
- Case 4: 7449 - Casa Musa | Migracion Cierre a Firewall Fortinet 100F
- Case 5: 7448 - ARTEL - Caida de enlace GTD

---

## 🔐 Getting Authentication Tokens

### Using Laravel Tinker (for quick testing)

```bash
cd /Users/eddiecerpa/Taskflow-Icontel/taskflow-backend

# Get token for regular user (jramirez)
docker exec taskflow_app php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$user = \Illuminate\Support\Facades\DB::table('users')->find(2);
\$token = app('Illuminate\Database\ConnectionResolver')->connection()->table('personal_access_tokens')->insertGetId([
    'tokenable_id' => 2,
    'tokenable_type' => 'App\\\Models\\\User',
    'name' => 'test-token',
    'token' => hash('sha256', \$plainToken = \Illuminate\Support\Str::random(40)),
    'abilities' => '[\"*\"]',
    'last_used_at' => null,
    'created_at' => now()
]);

echo 'Token: ' . \$plainToken;
"
```

---

## 🧪 Test Cases

### Test 1: Create Closure Request (as Regular User)

**Endpoint**: `POST /api/v1/cases/{caseId}/request-closure`

**Headers**:
```
Authorization: Bearer YOUR_USER_TOKEN
Content-Type: application/json
```

**Body**:
```json
{
  "reason": "Cliente confirmó que el problema fue resuelto satisfactoriamente",
  "completion_percentage": 100
}
```

**Expected Response (201)**:
```json
{
  "success": true,
  "message": "Solicitud de cierre enviada a SAC",
  "data": {
    "id": 1,
    "case_id": 1,
    "requested_by_user_id": 2,
    "assigned_to_user_id": 3,
    "status": "pending",
    "reason": "Cliente confirmó que el problema fue resuelto satisfactoriamente",
    "completion_percentage": 100,
    "rejection_reason": null,
    "reviewed_by_user_id": null,
    "reviewed_at": null,
    "created_at": "2026-01-08T16:30:00.000000Z",
    "updated_at": "2026-01-08T16:30:00.000000Z",
    "requestedBy": {
      "id": 2,
      "name": "jramirez"
    },
    "assignedTo": {
      "id": 3,
      "name": "María José Araneda"
    }
  }
}
```

**What happens**:
- ✅ Creates a new CaseClosureRequest with status='pending'
- ✅ Assigns to first SAC user (María José Araneda, ID: 3)
- ✅ Updates Case: closure_status='closure_requested', closure_requested_by_id=2, closure_requested_at=now

---

### Test 2: List Pending Requests (as SAC Jefe)

**Endpoint**: `GET /api/v1/closure-requests?status=pending`

**Headers**:
```
Authorization: Bearer JEFE_TOKEN
Content-Type: application/json
```

**Expected Response (200)**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "case_id": 1,
        "requested_by_user_id": 2,
        "assigned_to_user_id": 3,
        "status": "pending",
        "reason": "Cliente confirmó que el problema fue resuelto satisfactoriamente",
        "completion_percentage": 100,
        "rejection_reason": null,
        "reviewed_by_user_id": null,
        "reviewed_at": null,
        "created_at": "2026-01-08T16:30:00.000000Z",
        "updated_at": "2026-01-08T16:30:00.000000Z",
        "case": {
          "id": 1,
          "case_number": "7452",
          "subject": "Integramundo-Baja de servicio",
          "status": "Abierto",
          "closure_status": "closure_requested"
        },
        "requestedBy": {
          "id": 2,
          "name": "jramirez"
        },
        "assignedTo": {
          "id": 3,
          "name": "María José Araneda"
        }
      }
    ],
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

**What happens**:
- ✅ Returns paginated list of requests assigned to logged-in jefe
- ✅ Only shows status='pending' (can filter with ?status=approved, ?status=rejected, or ?status=all)
- ✅ Non-admins only see their own assigned requests

---

### Test 3: View Closure Request Detail

**Endpoint**: `GET /api/v1/closure-requests/{requestId}`

**Headers**:
```
Authorization: Bearer JEFE_TOKEN
Content-Type: application/json
```

**Replace `{requestId}` with ID from Test 1 (e.g., 1)**

**Expected Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "case_id": 1,
    "requested_by_user_id": 2,
    "assigned_to_user_id": 3,
    "status": "pending",
    "reason": "Cliente confirmó que el problema fue resuelto satisfactoriamente",
    "completion_percentage": 100,
    "rejection_reason": null,
    "reviewed_by_user_id": null,
    "reviewed_at": null,
    "created_at": "2026-01-08T16:30:00.000000Z",
    "updated_at": "2026-01-08T16:30:00.000000Z",
    "case": {
      "id": 1,
      "case_number": "7452",
      "subject": "Integramundo-Baja de servicio",
      "status": "Abierto",
      "closure_status": "closure_requested",
      "closure_requested_by_id": 2,
      "closure_requested_at": "2026-01-08T16:30:00.000000Z",
      "closure_approved_by_id": null,
      "closure_approved_at": null
    },
    "requestedBy": {
      "id": 2,
      "name": "jramirez",
      "email": "jramirez@icontel.cl"
    },
    "assignedTo": {
      "id": 3,
      "name": "María José Araneda",
      "email": "maria.araneda@icontel.cl"
    },
    "reviewedBy": null
  }
}
```

---

### Test 4: Approve Closure Request (as SAC Jefe)

**Endpoint**: `POST /api/v1/closure-requests/{requestId}/approve`

**Headers**:
```
Authorization: Bearer JEFE_TOKEN
Content-Type: application/json
```

**Body**: (empty or {})

**Expected Response (200)**:
```json
{
  "success": true,
  "message": "Caso cerrado exitosamente",
  "data": {
    "id": 1,
    "case_id": 1,
    "requested_by_user_id": 2,
    "assigned_to_user_id": 3,
    "status": "approved",
    "reason": "Cliente confirmó que el problema fue resuelto satisfactoriamente",
    "completion_percentage": 100,
    "rejection_reason": null,
    "reviewed_by_user_id": 3,
    "reviewed_at": "2026-01-08T16:35:00.000000Z",
    "created_at": "2026-01-08T16:30:00.000000Z",
    "updated_at": "2026-01-08T16:35:00.000000Z",
    "case": {
      "id": 1,
      "case_number": "7452",
      "subject": "Integramundo-Baja de servicio",
      "status": "Closed",
      "closure_status": "closed",
      "closure_requested_by_id": 2,
      "closure_requested_at": "2026-01-08T16:30:00.000000Z",
      "closure_approved_by_id": 3,
      "closure_approved_at": "2026-01-08T16:35:00.000000Z"
    },
    "requestedBy": {
      "id": 2,
      "name": "jramirez"
    },
    "assignedTo": {
      "id": 3,
      "name": "María José Araneda"
    },
    "reviewedBy": {
      "id": 3,
      "name": "María José Araneda"
    }
  }
}
```

**What happens**:
- ✅ Request status changes from 'pending' to 'approved'
- ✅ Case status changes to 'Closed'
- ✅ Case closure_status changes to 'closed'
- ✅ Case closure_approved_by_id set to jefe ID (3)
- ✅ Case closure_approved_at set to current timestamp
- ✅ reviewed_by_user_id and reviewed_at recorded in request

---

### Test 5: Check Case Closure Status (as Regular User)

**Endpoint**: `GET /api/v1/cases/{caseId}/closure-request`

**Headers**:
```
Authorization: Bearer USER_TOKEN
Content-Type: application/json
```

**Replace `{caseId}` with 1**

**Expected Response (200)**:
```json
{
  "success": true,
  "closure_status": "closed",
  "closure_request": {
    "id": 1,
    "case_id": 1,
    "requested_by_user_id": 2,
    "assigned_to_user_id": 3,
    "status": "approved",
    "reason": "Cliente confirmó que el problema fue resuelto satisfactoriamente",
    "completion_percentage": 100,
    "created_at": "2026-01-08T16:30:00.000000Z",
    "requestedBy": {
      "id": 2,
      "name": "jramirez"
    },
    "assignedTo": {
      "id": 3,
      "name": "María José Araneda"
    }
  }
}
```

---

## ⚠️ Error Test Cases

### Test 6: Duplicate Request (should fail)

**What happens**:
1. Create first request for Case 1 ✅
2. Try to create another request for Case 1 while first is still pending ❌

**Expected Response (422)**:
```json
{
  "success": false,
  "message": "Ya existe una solicitud de cierre pendiente para este caso"
}
```

---

### Test 7: Unauthorized Approval (should fail)

**Scenario**: Regular user tries to approve request

**Endpoint**: `POST /api/v1/closure-requests/{requestId}/approve`

**Headers**:
```
Authorization: Bearer USER_TOKEN (not jefe token)
```

**Expected Response (403)**:
```json
{
  "success": false,
  "message": "No tienes permiso para aprobar esta solicitud"
}
```

---

### Test 8: Reject Request (as SAC Jefe)

**Endpoint**: `POST /api/v1/closure-requests/{requestId}/reject`

**Headers**:
```
Authorization: Bearer JEFE_TOKEN
Content-Type: application/json
```

**Body**:
```json
{
  "rejection_reason": "Se requiere más validación del cliente antes de cerrar"
}
```

**Expected Response (200)**:
```json
{
  "success": true,
  "message": "Solicitud de cierre rechazada",
  "data": {
    "id": 1,
    "case_id": 1,
    "status": "rejected",
    "rejection_reason": "Se requiere más validación del cliente antes de cerrar",
    "reviewed_by_user_id": 3,
    "reviewed_at": "2026-01-08T16:40:00.000000Z",
    "case": {
      "id": 1,
      "status": "Abierto",
      "closure_status": "open",
      "closure_requested_by_id": null,
      "closure_requested_at": null
    }
  }
}
```

**What happens**:
- ✅ Request status changes to 'rejected'
- ✅ Case closure_status reverts to 'open'
- ✅ Case closure_requested_by_id set to null
- ✅ Case closure_requested_at set to null
- ✅ User can create new request after rejection

---

## 🔄 Complete Test Flow

1. **Create Request** (as jramirez) → Request ID: 1, Status: pending
2. **List Pending** (as María José) → Shows 1 pending request
3. **View Detail** → Shows request with case info
4. **Reject** (as María José) → Status: rejected, case reverts to open
5. **Create Request Again** (as jramirez) → Request ID: 2, Status: pending
6. **Approve** (as María José) → Status: approved, case closed
7. **Check Status** (as jramirez) → Shows case is closed

---

## 📝 cURL Examples

### Get Token (if your API has /login endpoint)
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jramirez@icontel.cl",
    "password": "password"
  }'
```

### Create Closure Request
```bash
curl -X POST http://localhost:8000/api/v1/cases/1/request-closure \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Cliente confirmó solución",
    "completion_percentage": 100
  }'
```

### List Pending Requests
```bash
curl -X GET "http://localhost:8000/api/v1/closure-requests?status=pending" \
  -H "Authorization: Bearer JEFE_TOKEN"
```

### Approve Request
```bash
curl -X POST http://localhost:8000/api/v1/closure-requests/1/approve \
  -H "Authorization: Bearer JEFE_TOKEN" \
  -H "Content-Type: application/json"
```

---

## ✅ Success Criteria

All tests pass when:
- ✅ Regular users can create closure requests
- ✅ Jefes de área see only their assigned requests
- ✅ Jefes can approve requests (case closes)
- ✅ Jefes can reject requests (case reopens)
- ✅ Users can retry after rejection
- ✅ Proper authorization (non-jefes can't approve)
- ✅ Proper validation (no duplicate requests)

