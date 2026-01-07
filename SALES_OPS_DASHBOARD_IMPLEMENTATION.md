# 📊 Implementación de Dashboard basado en Área - Ventas vs Operaciones

## ¿Qué se implementó?

Se ha implementado una nueva funcionalidad que permite que el dashboard de TaskFlow **se adapte automáticamente según el área/departamento del usuario**:

### Para Equipo de Ventas/Comercial
✅ **Contenido Principal**: Oportunidades + Tareas (en lugar de Casos)
✅ **Tareas Delegadas**: Oportunidades y Tareas que el usuario ha delegado a otros
✅ **Estadísticas**: Total de Oportunidades activas
✅ **Seguimiento**: Control de Oportunidades en diferentes etapas del pipeline

### Para Equipo de Operaciones (comportamiento actual)
✅ **Contenido Principal**: Casos + Tareas
✅ **Tareas Delegadas**: Casos y Tareas que el usuario ha delegado a otros
✅ **Estadísticas**: Total de Casos activos
✅ **Seguimiento**: Control de Casos en diferentes estados

## Implementación Técnica

### Backend (Laravel)

#### Nuevos Endpoints

**1. GET `/api/v1/dashboard/area-content`**
- Detecta automáticamente el área del usuario basándose en el campo `department`
- Para Ventas/Comercial: Retorna Oportunidades + Tareas
- Para otros: Retorna Casos + Tareas
- Respuesta incluye: `user_area` (sales | null) e `data` con el contenido

**Lógica de Detección de Área**:
```php
$department = strtolower($user->department ?? '');
$isSalesTeam = in_array($department, ['ventas', 'comercial', 'sales', 'commercial']);
```

**2. GET `/api/v1/dashboard/delegated-sales`**
- Para equipo de Ventas
- Obtiene Oportunidades y Tareas creadas por el usuario y asignadas a otros
- Filtra Oportunidades: `created_by = USER AND assigned_user_id != USER`
- Filtra Tareas: `created_by = USER AND assigned_user_id != USER` (estados activos)
- Retorna estructura: `{ opportunities: [], tasks: [], total: 0, pending: 0 }`

#### Archivos Modificados

**`taskflow-backend/app/Http/Controllers/Api/DashboardController.php`**
- Nuevo método `getAreaBasedContent()` (líneas 161-198)
  - Detecta área del usuario
  - Llama a `getSalesTeamContent()` o `getMyContent()` según corresponda

- Nuevo método `getSalesTeamContent()` (líneas 204-306)
  - Obtiene Oportunidades asignadas al usuario (100 máximo)
  - Obtiene Tareas en estados activos (Open, Reassigned, In Progress, Not Started)
  - Retorna estructura optimizada para Ventas

- Nuevo método `getDelegatedSales()` (líneas 313-427)
  - Filtra Oportunidades creadas por usuario y asignadas a otros
  - Filtra Tareas creadas por usuario y asignadas a otros
  - Solo estados activos: Open, Reassigned, In Progress, Not Started
  - Retorna cantidad total y pendiente

**`taskflow-backend/routes/api.php`**
```php
Route::get('/dashboard/area-content', [DashboardController::class, 'getAreaBasedContent']);
Route::get('/dashboard/delegated-sales', [DashboardController::class, 'getDelegatedSales']);
```

### Frontend (Vue.js + Pinia)

#### API Service

**`taskflow-frontend/src/services/api.js`**
```javascript
export const dashboardAPI = {
  getStats: () => api.get('dashboard/stats'),
  getMyContent: (params) => api.get('dashboard/my-content', { params }),
  getAreaBasedContent: (params) => api.get('dashboard/area-content', { params }),
  getDelegated: () => api.get('dashboard/delegated'),
  getDelegatedSales: () => api.get('dashboard/delegated-sales'),
}
```

#### Store Pinia

**`taskflow-frontend/src/stores/dashboard.js`**

**Nuevo Estado**:
```javascript
state: () => ({
    // ... estado existente
    opportunities: [], // Oportunidades para equipo de ventas
    delegatedSales: { // Oportunidades y tareas delegadas para ventas
        opportunities: [],
        tasks: [],
        total: 0,
        pending: 0
    },
    delegatedSalesLoading: false,
    userArea: null, // 'sales' | null
    // ...
})
```

**Nuevas Acciones**:
```javascript
async fetchAreaBasedContent() {
    // Obtiene contenido basado en área del usuario
    // Detecta userArea automáticamente
    // Llena opportunities o cases según corresponda
}

async fetchDelegatedSales() {
    // Obtiene oportunidades y tareas delegadas por el usuario de ventas
}
```

#### Vista

**`taskflow-frontend/src/views/DashboardView.vue`**

**Actualización de `loadData()`**:
```javascript
const loadData = async () => {
    // Llama a endpoint area-aware
    dashboardStore.fetchAreaBasedContent();

    // Obtiene delegadas específicas según área
    if (dashboardStore.userArea === 'sales') {
        dashboardStore.fetchDelegatedSales();
    } else {
        dashboardStore.fetchDelegated();
    }
    // ...
}
```

**Stats Dinámicas**:
```javascript
const stats = computed(() => {
    let activeItemsCount = 0;

    if (dashboardStore.userArea === 'sales') {
        activeItemsCount = dashboardStore.opportunities.length;
    } else {
        activeItemsCount = dashboardStore.cases.length;
    }

    // Delegar total y pending según área
    const delegatedTotal = dashboardStore.userArea === 'sales'
        ? dashboardStore.delegatedSales.total
        : dashboardStore.delegated.total;

    return {
        activeFlows: activeItemsCount, // Oportunidades para Ventas, Casos para Ops
        delegatedTasks: delegatedTotal,
        // ...
    }
})
```

**Delegadas Dinámicas**:
```javascript
const delegatedTasks = computed(() => {
    if (dashboardStore.userArea === 'sales') {
        // Combina oportunidades + tareas delegadas
        return [
            ...dashboardStore.delegatedSales.opportunities.map(...),
            ...dashboardStore.delegatedSales.tasks.map(...)
        ]
    } else {
        // Combina casos + tareas delegadas (comportamiento actual)
        return [
            ...dashboardStore.delegated.cases.map(...),
            ...dashboardStore.delegated.tasks.map(...)
        ]
    }
})
```

## Flujo de Datos

### Para Usuario de Ventas

```
Usuario (Comercial) inicia sesión
        ↓
DashboardView.vue carga
        ↓
onMounted → loadData()
        ↓
dashboardStore.fetchAreaBasedContent()
        ↓
dashboardAPI.getAreaBasedContent()
        ↓
Backend: DashboardController@getAreaBasedContent
        ↓
Detecta: user.department = 'Ventas'
        ↓
Llama: getSalesTeamContent()
        ↓
SweetCrmService → Queries Oportunidades
                → Queries Tareas
        ↓
Retorna: { user_area: 'sales', data: { opportunities: [...], tasks: [...], total: X, total_opportunities: Y, total_tasks: Z } }
        ↓
Frontend actualiza: store.userArea = 'sales'
                    store.opportunities = [...]
                    store.orphanTasks = [...]
        ↓
Vue reactivamente actualiza:
  - Tarjeta "Flujos Activos" ahora muestra Oportunidades
  - Stats muestran cantidad de Oportunidades
  - Tabla show Oportunidades + Tareas
        ↓
dashboardStore.fetchDelegatedSales()
        ↓
Trae Oportunidades y Tareas delegadas por el usuario de Ventas
        ↓
Actualiza store.delegatedSales y muestra en tabla de "Delegadas"
```

### Para Usuario de Operaciones/Standard

```
Usuario (Operaciones) inicia sesión
        ↓
DashboardView.vue carga
        ↓
onMounted → loadData()
        ↓
dashboardStore.fetchAreaBasedContent()
        ↓
dashboardAPI.getAreaBasedContent()
        ↓
Backend: DashboardController@getAreaBasedContent
        ↓
Detecta: user.department = 'Operaciones' (o null, o no coincide con Ventas)
        ↓
Llama: getMyContent() (comportamiento original)
        ↓
Retorna: Casos + Tareas
        ↓
Frontend actualiza: store.userArea = null (o 'operations')
                    store.cases = [...]
                    store.orphanTasks = [...]
        ↓
Vue reactivamente actualiza (mismo comportamiento de antes):
  - Tarjeta "Flujos Activos" muestra Casos
  - Stats muestran cantidad de Casos
  - Tabla muestra Casos + Tareas
        ↓
dashboardStore.fetchDelegated()
        ↓
Trae Casos y Tareas delegadas por el usuario de Operaciones
        ↓
Actualiza store.delegated y muestra en tabla de "Delegadas"
```

## Testing

### Para Equipo de Ventas

1. **Asegurarse que el usuario tenga `department = 'Ventas'` (o 'Comercial', 'Sales', 'Commercial')**
   - Verificar en la tabla `users` que el campo `department` sea correcto

2. **Login en el Dashboard**
   - Ir a `/dashboard`

3. **Verificar Contenido Principal**
   - Tarjeta "Flujos Activos" debe mostrar número de Oportunidades (no Casos)
   - En la sección principal debe aparecer tabla de Oportunidades + Tareas
   - Cada oportunidad debe mostrar: nombre, sales stage, amount, assigned_user

4. **Verificar Delegadas**
   - Tarjeta "Delegadas" debe mostrar oportunidades y tareas que el usuario ha delegado
   - Número total y pendiente deben ser correctos
   - Scroll a tabla de delegadas debe mostrar oportunidades (no casos)

5. **Verificar logs del backend** (si está en Docker):
   ```bash
   docker-compose logs -f app
   ```
   - Buscar: "Sales team content loaded" o similar

### Para Equipo de Operaciones

1. **Asegurarse que el usuario tenga `department` diferente** a los valores de Ventas
   - O que sea `null` o en blanco

2. **Login en el Dashboard**
   - Comportamiento debe ser idéntico al anterior

3. **Verificar Contenido Principal**
   - Tarjeta "Flujos Activos" debe mostrar número de Casos
   - Tabla debe mostrar Casos + Tareas

4. **Verificar Delegadas**
   - Tabla de delegadas debe mostrar Casos + Tareas delegadas (no Oportunidades)

## Estructura de Respuestas

### `GET /api/v1/dashboard/area-content` - Sales User

**Status 200**:
```json
{
    "success": true,
    "user_area": "sales",
    "data": {
        "opportunities": [
            {
                "id": "opp_123",
                "type": "opportunity",
                "title": "Proyecto XYZ",
                "sales_stage": "Needs Analysis",
                "amount": 50000,
                "currency": "CLP",
                "probability": 75,
                "date_closed": "2026-02-15",
                "assigned_user_id": "user_456",
                "assigned_user_name": "Carlos"
            }
        ],
        "tasks": [
            {
                "id": "task_789",
                "type": "task",
                "title": "Llamar cliente",
                "status": "In Progress",
                "priority": "High",
                "assigned_user_name": "Carlos",
                "date_due": "2026-01-10",
                "date_entered": "2026-01-01"
            }
        ],
        "total": 2,
        "total_opportunities": 1,
        "total_tasks": 1
    }
}
```

### `GET /api/v1/dashboard/area-content` - Operations User

**Status 200**:
```json
{
    "success": true,
    "user_area": null,
    "data": {
        "cases": [
            {
                "id": "case_123",
                "case_number": "C-00012",
                "subject": "Error en sistema",
                "status": "Open",
                // ...
            }
        ],
        "tasks": [...],
        "view_mode": "my"
    }
}
```

### `GET /api/v1/dashboard/delegated-sales`

**Status 200**:
```json
{
    "success": true,
    "data": {
        "opportunities": [
            {
                "id": "opp_999",
                "type": "opportunity",
                "title": "Cliente ABC",
                "sales_stage": "Prospecting",
                "amount": 30000,
                "currency": "CLP",
                "assigned_user_name": "Maria",
                "created_by_name": "Jorge",
                "date_closed": null
            }
        ],
        "tasks": [
            {
                "id": "task_555",
                "type": "task",
                "title": "Seguimiento cliente",
                "status": "Open",
                "priority": "Medium",
                "assigned_user_name": "Diego",
                "created_by_name": "Jorge",
                "date_due": "2026-01-15",
                "date_entered": "2026-01-01"
            }
        ],
        "total": 2,
        "pending": 2
    }
}
```

## Errores Comunes

### "Oportunidades no aparecen en dashboard de Ventas"
1. Verificar que `user.department = 'Ventas'` (exacto o case-insensitive)
2. Verificar que el usuario tenga `sweetcrm_id` poblado
3. Verificar que existan oportunidades asignadas al usuario en SweetCRM
4. Revisar logs: `docker-compose logs -f app` y buscar errores

### "Stats muestran 0 Oportunidades/Casos"
1. Verificar que `fetchAreaBasedContent()` se haya completado
2. Verificar que `dashboardStore.userArea` esté correctamente set
3. En console del navegador: `console.log(dashboardStore.opportunities)` o `dashboardStore.cases`

### "Delegadas muestra casos en usuario de Ventas"
1. Verificar que `fetchDelegatedSales()` se haya llamado (no `fetchDelegated()`)
2. Verificar en store que `delegatedSales` esté siendo usado (no `delegated`)
3. Revisar computed property `delegatedTasks` que debe chequear `dashboardStore.userArea`

## Consideraciones Técnicas

### Detección de Área
- **Campo usado**: `User.department` (case-insensitive)
- **Valores para Ventas**: 'ventas', 'comercial', 'sales', 'commercial'
- **Default**: Si no coincide con Ventas, se asume Operaciones/Standard

### Estados Filtrados

**Oportunidades**:
- No hay filtro de estado, se traen todas las asignadas al usuario
- Posibles valores de `sales_stage`: Prospecting, Qualification, Needs Analysis, Value Proposition, Id. Decision Makers, Perception Analysis, Proposal/Price Quote, Negotiation/Review, Verbal Agreement, Closed Won

**Tareas** (para Sales):
- Filtradas a: Open, Reassigned, In Progress, Not Started
- Excluye: Completed, Deferred, etc.

**Oportunidades Delegadas**:
- Sin filtro de estado (todas las delegadas)

**Tareas Delegadas**:
- Mismo filtro: Open, Reassigned, In Progress, Not Started

### Performance
- Máximo 100 oportunidades por request
- Máximo 100 tareas por request
- Datos se cachean en Pinia store mientras el usuario está en el dashboard
- No se refresca automáticamente (cargar manualmente con botón si es necesario)

## Próximos Pasos (Opcionales)

1. **Auto-refresh**: Implementar auto-refresh cada 5-10 minutos
2. **Botón de Refresh**: Agregar botón para refrescar datos manualmente
3. **Más departamentos**: Agregar lógica para otros departamentos además de Ventas
4. **Acciones desde Dashboard**: Poder crear/editar oportunidades directamente
5. **Filtros**: Filtrar oportunidades por sales stage, rango de amount, etc.
6. **Notificaciones**: Alertar cuando una delegada cambia de estado

## Commits Relacionados

- **e7496db** - Feat: Implementar dashboard basado en área para Ventas/Operaciones
  - Agregó endpoints area-aware
  - Actualizó store y vista

---

✅ **Implementación completada y funcional**

Última actualización: Enero 7, 2026
