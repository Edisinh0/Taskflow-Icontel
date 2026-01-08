# DOCUMENTACION TECNICA COMPLETA - TASKFLOW-ICONTEL

> Documento de referencia para entender la estructura, arquitectura y funcionamiento del proyecto.
> Usar este documento para formular prompts precisos y realizar ediciones efectivas.

---

## TABLA DE CONTENIDOS

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Arquitectura General](#2-arquitectura-general)
3. [Backend - Laravel](#3-backend---laravel)
4. [Frontend - Vue.js](#4-frontend---vuejs)
5. [Integracion SweetCRM](#5-integracion-sweetcrm)
6. [Base de Datos](#6-base-de-datos)
7. [Flujos de Datos](#7-flujos-de-datos)
8. [Guia de Prompts](#8-guia-de-prompts)
9. [Como Funcionan los Metodos (Detalle Interno)](#9-como-funcionan-los-metodos-detalle-interno)
10. [Mapeos de Datos Completos](#10-mapeos-de-datos-completos)
11. [Flujos de Negocio Completos](#11-flujos-de-negocio-completos)

---

## 1. RESUMEN EJECUTIVO

### Que es Taskflow-Icontel?

Sistema web full-stack que integra **SweetCRM** (basado en SugarCRM v4_1) con un sistema interno de gestion de:
- **Casos** (tickets de soporte/proyectos)
- **Tareas** (trabajo asignado a usuarios)
- **Oportunidades** (ventas potenciales)
- **Flujos de trabajo** (plantillas de procesos)
- **Clientes** (cuentas empresariales)

### Stack Tecnologico

| Componente | Tecnologia | Version |
|------------|------------|---------|
| Backend | Laravel (PHP) | 11.x |
| Frontend | Vue.js + Vite | 3.5.x |
| Estado Frontend | Pinia | 3.x |
| Estilos | Tailwind CSS | 3.4.x |
| Base de Datos | MariaDB/MySQL | 10.11 |
| Cache/Queue | Redis | 7.x |
| WebSockets | Laravel Echo + Soketi | - |
| Autenticacion | Laravel Sanctum | - |
| Contenedores | Docker Compose | - |

### Estructura de Directorios Principal

```
Taskflow-Icontel/
├── taskflow-backend/          # API Laravel
│   ├── app/
│   │   ├── Console/Commands/  # Comandos artisan (sincronizacion)
│   │   ├── Http/Controllers/Api/  # Controladores REST
│   │   ├── Models/            # Modelos Eloquent
│   │   └── Services/          # Logica de negocio
│   ├── database/migrations/   # Estructura de BD
│   ├── routes/api.php         # Definicion de rutas
│   └── config/                # Configuraciones
│
├── taskflow-frontend/         # SPA Vue.js
│   ├── src/
│   │   ├── views/             # Paginas principales
│   │   ├── components/        # Componentes reutilizables
│   │   ├── stores/            # Estado global (Pinia)
│   │   ├── services/api.js    # Cliente HTTP
│   │   └── router/            # Rutas frontend
│   └── .env.local             # Variables de entorno
│
└── docker-compose.yml         # Orquestacion de contenedores
```

---

## 2. ARQUITECTURA GENERAL

### Diagrama de Flujo de Datos

```
┌─────────────────────────────────────────────────────────────────┐
│                        SWEETCRM (Externo)                        │
│                   https://sweet.icontel.cl                       │
│         API REST v4_1 - Casos, Tareas, Oportunidades            │
└──────────────────────────┬──────────────────────────────────────┘
                           │ HTTP POST (JSON)
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    BACKEND (Laravel)                             │
│                   localhost:8080/api/v1                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │ Controllers │──│  Services   │──│  SweetCrmService.php    │  │
│  │   (API)     │  │  (Logica)   │  │  (Sincronizacion)       │  │
│  └──────┬──────┘  └──────┬──────┘  └─────────────────────────┘  │
│         │                │                                       │
│         ▼                ▼                                       │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │                    MODELOS ELOQUENT                          ││
│  │  User | Client | CrmCase | Task | CrmOpportunity | Flow     ││
│  └──────────────────────────┬──────────────────────────────────┘│
└─────────────────────────────┼───────────────────────────────────┘
                              │ SQL
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    BASE DE DATOS (MariaDB)                       │
│                      localhost:3306                              │
│           Database: taskflow | User: root | Pass: root          │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ JSON API
┌─────────────────────────────┼───────────────────────────────────┐
│                    FRONTEND (Vue.js)                             │
│                    localhost:5173                                │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   Views     │──│   Stores    │──│   services/api.js       │  │
│  │  (Paginas)  │  │   (Pinia)   │  │   (Axios Client)        │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Contenedores Docker

| Contenedor | Puerto | Proposito |
|------------|--------|-----------|
| taskflow_app | 9000 (interno) | PHP-FPM Backend |
| taskflow_nginx | 8080 | Servidor web (proxy a app) |
| taskflow_mariadb | 3306 | Base de datos |
| taskflow_redis | 6379 | Cache y colas |
| taskflow_queue | - | Worker de colas |
| taskflow_soketi | 6001 | WebSockets |

---

## 3. BACKEND - LARAVEL

### 3.1 Modelos Principales y Relaciones

#### USER (Usuario del sistema)
**Archivo**: `app/Models/User.php`

```
┌─────────────────────────────────────────────────────────────┐
│ USER                                                         │
├─────────────────────────────────────────────────────────────┤
│ id                    │ bigint (PK)                         │
│ name                  │ string - Nombre completo            │
│ email                 │ string - Email unico                │
│ password              │ string - Hash                       │
│ role                  │ enum: admin, project_manager, user  │
│ department            │ enum: Ventas, Operaciones, Soporte  │
│ sweetcrm_id           │ string - UUID en SweetCRM           │
│ sweetcrm_user_type    │ string - Tipo en SweetCRM           │
│ sweetcrm_synced_at    │ timestamp                           │
├─────────────────────────────────────────────────────────────┤
│ RELACIONES:                                                  │
│ - hasMany(Task, 'assignee_id') → Tareas asignadas           │
│ - hasMany(Task, 'created_by') → Tareas creadas              │
│ - hasMany(Flow, 'created_by') → Flujos creados              │
│ - hasMany(Template, 'created_by') → Plantillas              │
└─────────────────────────────────────────────────────────────┘
```

#### CRM_CASE (Caso de soporte/proyecto)
**Archivo**: `app/Models/CrmCase.php`

```
┌─────────────────────────────────────────────────────────────┐
│ CRM_CASE                                                     │
├─────────────────────────────────────────────────────────────┤
│ id                    │ bigint (PK)                         │
│ sweetcrm_id           │ string - UUID de SweetCRM           │
│ case_number           │ string - Numero legible (ej: 7447)  │
│ subject               │ string - Titulo del caso            │
│ description           │ text - Descripcion                  │
│ status                │ string - Abierto, Cerrado, etc.     │
│ priority              │ string - Alta, Media, Baja          │
│ type                  │ string - Tipo de caso               │
│ area                  │ string - Area responsable           │
│ client_id             │ FK → clients                        │
│ sweetcrm_assigned_user_id │ string - UUID usuario asignado  │
│ assigned_user_name    │ string - Nombre del asignado        │
│ original_creator_id   │ string - Quien creo en SweetCRM     │
│ closure_requested     │ boolean - Cierre solicitado?        │
│ closure_requested_by  │ FK → users                          │
│ sweetcrm_synced_at    │ timestamp                           │
├─────────────────────────────────────────────────────────────┤
│ RELACIONES:                                                  │
│ - belongsTo(Client) → Cliente                               │
│ - hasMany(Task) → Tareas del caso                           │
│ - hasMany(CaseUpdate) → Historial de avances                │
└─────────────────────────────────────────────────────────────┘
```

#### TASK (Tarea)
**Archivo**: `app/Models/Task.php`

```
┌─────────────────────────────────────────────────────────────┐
│ TASK                                                         │
├─────────────────────────────────────────────────────────────┤
│ id                    │ bigint (PK)                         │
│ title                 │ string - Titulo                     │
│ description           │ text - Descripcion                  │
│ status                │ enum: pending, in_progress,         │
│                       │       completed, cancelled,         │
│                       │       blocked, paused               │
│ priority              │ enum: High, Medium, Low             │
│ progress              │ integer 0-100                       │
│ case_id               │ FK → crm_cases (nullable)           │
│ flow_id               │ FK → flows (nullable)               │
│ assignee_id           │ FK → users                          │
│ created_by            │ FK → users                          │
│ parent_task_id        │ FK → tasks (subtareas)              │
│ estimated_start_at    │ timestamp                           │
│ estimated_end_at      │ timestamp                           │
│ actual_start_at       │ timestamp                           │
│ actual_end_at         │ timestamp                           │
│ sweetcrm_id           │ string - UUID si viene de CRM       │
│ sweetcrm_synced_at    │ timestamp                           │
├─────────────────────────────────────────────────────────────┤
│ IMPORTANTE:                                                  │
│ - Si case_id != null → Tarea de un Caso de CRM              │
│ - Si flow_id != null → Tarea de un Flujo interno            │
│ - Si ambos null → Tarea huerfana                            │
├─────────────────────────────────────────────────────────────┤
│ RELACIONES:                                                  │
│ - belongsTo(CrmCase) → Caso padre                           │
│ - belongsTo(Flow) → Flujo padre                             │
│ - belongsTo(User, 'assignee_id') → Asignado                 │
│ - belongsTo(User, 'created_by') → Creador                   │
│ - hasMany(Task, 'parent_task_id') → Subtareas               │
│ - hasMany(CaseUpdate) → Actualizaciones                     │
└─────────────────────────────────────────────────────────────┘
```

#### CRM_OPPORTUNITY (Oportunidad de venta)
**Archivo**: `app/Models/CrmOpportunity.php`

```
┌─────────────────────────────────────────────────────────────┐
│ CRM_OPPORTUNITY                                              │
├─────────────────────────────────────────────────────────────┤
│ id                    │ bigint (PK)                         │
│ sweetcrm_id           │ string - UUID de SweetCRM           │
│ name                  │ string - Nombre de oportunidad      │
│ sales_stage           │ string - Prospecting, Proposal,     │
│                       │          Negotiation, Closed Won... │
│ amount                │ decimal(15,2) - Monto               │
│ currency              │ string - CLP, USD                   │
│ expected_closed_date  │ date                                │
│ client_id             │ FK → clients                        │
│ sweetcrm_assigned_user_id │ string                          │
│ sweetcrm_synced_at    │ timestamp                           │
├─────────────────────────────────────────────────────────────┤
│ RELACIONES:                                                  │
│ - belongsTo(Client) → Cliente                               │
│ - hasMany(CrmQuote) → Cotizaciones                          │
│ - hasMany(Task) → Tareas asociadas                          │
└─────────────────────────────────────────────────────────────┘
```

#### CLIENT (Cliente/Cuenta)
**Archivo**: `app/Models/Client.php`

```
┌─────────────────────────────────────────────────────────────┐
│ CLIENT                                                       │
├─────────────────────────────────────────────────────────────┤
│ id                    │ bigint (PK)                         │
│ sweetcrm_id           │ string - UUID de SweetCRM           │
│ name                  │ string - Razon social               │
│ email                 │ string                              │
│ phone                 │ string                              │
│ address               │ text                                │
│ industry_id           │ FK → industries                     │
│ status                │ string                              │
│ sweetcrm_synced_at    │ timestamp                           │
├─────────────────────────────────────────────────────────────┤
│ RELACIONES:                                                  │
│ - hasMany(CrmCase) → Casos                                  │
│ - hasMany(CrmOpportunity) → Oportunidades                   │
│ - hasMany(Flow) → Flujos                                    │
│ - hasMany(ClientContact) → Contactos                        │
│ - hasMany(ClientAttachment) → Documentos                    │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Controladores API Principales

**Ubicacion**: `app/Http/Controllers/Api/`

| Controlador | Endpoints | Proposito |
|-------------|-----------|-----------|
| **AuthController** | `/auth/*` | Login, logout, usuario actual |
| **DashboardController** | `/dashboard/*` | Estadisticas, contenido por area |
| **CaseController** | `/cases/*` | CRUD de casos, cierre |
| **TaskController** | `/tasks/*` | CRUD de tareas, attachments |
| **OpportunityController** | `/opportunities/*` | CRUD de oportunidades |
| **ClientController** | `/clients/*` | CRUD de clientes |
| **FlowController** | `/flows/*` | CRUD de flujos |
| **UserController** | `/users` | Listar usuarios |
| **NotificationController** | `/notifications/*` | Notificaciones |

### 3.3 Endpoints API Detallados

```
AUTENTICACION
─────────────────────────────────────────────────────────────────
POST   /api/v1/auth/login          → Autenticar (contra SweetCRM)
POST   /api/v1/auth/logout         → Cerrar sesion
GET    /api/v1/auth/me             → Usuario actual

DASHBOARD
─────────────────────────────────────────────────────────────────
GET    /api/v1/dashboard/stats            → Estadisticas generales
GET    /api/v1/dashboard/area-content     → Contenido por departamento
       ?view=my|area                         (casos u oportunidades)
GET    /api/v1/dashboard/delegated        → Casos/tareas delegadas
GET    /api/v1/dashboard/delegated-sales  → Oportunidades delegadas

CASOS
─────────────────────────────────────────────────────────────────
GET    /api/v1/cases                → Listar casos (paginado)
       ?search=texto&status=Abierto&priority=Alta&assigned_to_me=true
GET    /api/v1/cases/stats          → Estadisticas de casos
GET    /api/v1/cases/{id}           → Detalle de un caso
GET    /api/v1/my-cases             → Mis casos asignados
POST   /api/v1/cases/{id}/updates   → Agregar avance/comentario
DELETE /api/v1/updates/{id}         → Eliminar actualizacion
POST   /api/v1/cases/{id}/request-closure  → Solicitar cierre
POST   /api/v1/cases/{id}/approve-closure  → Aprobar cierre
POST   /api/v1/cases/{id}/reject-closure   → Rechazar cierre

TAREAS
─────────────────────────────────────────────────────────────────
GET    /api/v1/tasks                → Listar tareas
       ?flow_id=1&assignee_id=5&status=pending
GET    /api/v1/tasks/{id}           → Detalle de tarea
POST   /api/v1/tasks                → Crear tarea
PUT    /api/v1/tasks/{id}           → Actualizar tarea
DELETE /api/v1/tasks/{id}           → Eliminar tarea
GET    /api/v1/my-tasks             → Mis tareas asignadas
POST   /api/v1/tasks/{id}/updates   → Agregar avance a tarea
POST   /api/v1/tasks/{id}/attachments  → Subir archivo
DELETE /api/v1/attachments/{id}     → Eliminar archivo

OPORTUNIDADES
─────────────────────────────────────────────────────────────────
GET    /api/v1/opportunities        → Listar oportunidades
       ?search=texto&sales_stage=Proposal
GET    /api/v1/opportunities/stats  → Estadisticas
GET    /api/v1/opportunities/{id}   → Detalle
POST   /api/v1/opportunities/{id}/send-to-operations  → Crear flujo

CLIENTES
─────────────────────────────────────────────────────────────────
GET    /api/v1/clients              → Listar clientes
GET    /api/v1/clients/{id}         → Detalle de cliente
POST   /api/v1/clients              → Crear cliente
PUT    /api/v1/clients/{id}         → Actualizar cliente

FLUJOS
─────────────────────────────────────────────────────────────────
GET    /api/v1/flows                → Listar flujos
GET    /api/v1/flows/{id}           → Detalle de flujo
POST   /api/v1/flows                → Crear flujo
PUT    /api/v1/flows/{id}           → Actualizar flujo
DELETE /api/v1/flows/{id}           → Eliminar flujo

USUARIOS
─────────────────────────────────────────────────────────────────
GET    /api/v1/users                → Listar usuarios (para asignacion)

NOTIFICACIONES
─────────────────────────────────────────────────────────────────
GET    /api/v1/notifications        → Listar notificaciones
PUT    /api/v1/notifications/{id}/read    → Marcar como leida
POST   /api/v1/notifications/read-all     → Marcar todas como leidas
```

### 3.4 Servicios Clave

#### SweetCrmService.php
**Ubicacion**: `app/Services/SweetCrmService.php`

Servicio central para integracion con SweetCRM:

```php
// Metodos principales:
getCachedSession($username, $password)  // Obtiene/cachea sesion (1 hora)
authenticate($username, $password)       // Autentica contra SweetCRM
getSessionId($username, $password)       // Obtiene ID de sesion
getCases($sessionId, $filters)           // Consulta casos
getTasks($sessionId, $filters)           // Consulta tareas
getOpportunities($sessionId, $filters)   // Consulta oportunidades
getUsers($sessionId)                     // Consulta usuarios
```

### 3.5 Comandos de Consola

**Ubicacion**: `app/Console/Commands/`

| Comando | Descripcion |
|---------|-------------|
| `php artisan sweetcrm:sync-cases` | Sincroniza casos y tareas desde SweetCRM |
| `php artisan sweetcrm:sync-users` | Sincroniza usuarios |
| `php artisan sweetcrm:sync-opportunities` | Sincroniza oportunidades |

---

## 4. FRONTEND - VUE.JS

### 4.1 Vistas Principales

**Ubicacion**: `src/views/`

| Vista | Archivo | Descripcion |
|-------|---------|-------------|
| **Dashboard** | `DashboardView.vue` | Pagina principal con casos/tareas/stats |
| **Casos** | `CasesView.vue` | Tabla de casos con filtros |
| **Oportunidades** | `OpportunitiesView.vue` | Grid de oportunidades |
| **Tareas** | `TasksView.vue` | Lista de tareas |
| **Flujos** | `FlowsView.vue` | Lista de flujos |
| **Detalle Flujo** | `FlowDetailView.vue` | Editor de flujo |
| **Clientes** | `ClientsView.vue` | Lista de clientes |
| **Detalle Cliente** | `ClientDetailView.vue` | Info completa del cliente |
| **Plantillas** | `TemplatesView.vue` | Gestion de plantillas |
| **Notificaciones** | `NotificationsView.vue` | Centro de notificaciones |
| **Reportes** | `ReportsView.vue` | Graficos y exportacion |
| **Login** | `LoginView.vue` | Pantalla de acceso |

### 4.2 Componentes Reutilizables

**Ubicacion**: `src/components/`

| Componente | Proposito |
|------------|-----------|
| `AppNavbar.vue` | Barra de navegacion principal |
| `TaskModal.vue` | Modal para crear/editar tareas |
| `FlowModal.vue` | Modal para flujos |
| `ClientModal.vue` | Modal para clientes |
| `TaskTreeItem.vue` | Arbol jerarquico de tareas |
| `DependencyManager.vue` | Gestion de dependencias |
| `NotificationBell.vue` | Icono de notificaciones |
| `TaskAttachments.vue` | Gestion de archivos |
| `FlowDiagram.vue` | Diagrama visual de flujo |
| `TaskGantt.vue` | Diagrama Gantt |

### 4.3 Stores (Pinia)

**Ubicacion**: `src/stores/`

#### auth.js - Autenticacion
```javascript
// Estado
user: null          // Usuario logueado
token: null         // Token Bearer

// Acciones
login(credentials)  // POST /auth/login
logout()            // POST /auth/logout
fetchCurrentUser()  // GET /auth/me
loadFromStorage()   // Carga de localStorage
```

#### dashboard.js - Dashboard
```javascript
// Estado
cases: []           // Casos del dashboard
orphanTasks: []     // Tareas sin caso
opportunities: []   // Oportunidades (ventas)
delegated: {}       // Casos/tareas delegadas
scope: 'my'         // 'my' o 'area'
userArea: null      // 'sales' o 'operations'

// Acciones
fetchAreaBasedContent()  // GET /dashboard/area-content
fetchDelegated()         // GET /dashboard/delegated
setScope(scope)          // Cambiar entre mis/area
toggleCase(caseId)       // Expandir/contraer caso
```

#### cases.js - Casos
```javascript
// Estado
cases: []           // Lista de casos
loading: false
pagination: {}      // Paginacion
filters: {          // Filtros activos
  search: '',
  status: 'all',
  priority: 'all',
  assigned_to_me: false
}
stats: {}           // Estadisticas

// Acciones
fetchCases()        // GET /cases con filtros
loadMore()          // Paginacion
setFilter(k, v)     // Actualizar filtro
```

#### tasks.js, flows.js, clients.js, notifications.js
Estructura similar a cases.js

### 4.4 Servicio API

**Archivo**: `src/services/api.js`

```javascript
// Configuracion base
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,  // http://localhost:8080/api/v1
  withCredentials: true
})

// Interceptor: Agrega token a cada request
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Exports por feature:
export const authAPI = { login, logout, me }
export const dashboardAPI = { getStats, getAreaBasedContent, getDelegated }
export const casesAPI = { getAll, getOne, getStats }
export const tasksAPI = { getAll, create, update, delete, uploadAttachment }
export const opportunitiesAPI = { getAll, sendToOperations }
export const flowsAPI = { getAll, create, update, delete }
export const clientsAPI = { getAll, getOne, create, update }
```

### 4.5 Router

**Archivo**: `src/router/index.js`

```javascript
// Rutas principales
/login              → LoginView (requiresGuest)
/dashboard          → DashboardView (requiresAuth)
/cases              → CasesView (requiresAuth)
/opportunities      → OpportunitiesView (requiresAuth)
/tasks              → TasksView (requiresAuth)
/flows              → FlowsView (requiresAuth)
/flows/:id          → FlowDetailView (requiresAuth)
/clients            → ClientsView (requiresAuth)
/clients/:id        → ClientDetailView (requiresAuth)
/templates          → TemplatesView (requiresAuth, roles: admin/pm)
/notifications      → NotificationsView (requiresAuth)
/reports            → ReportsView (requiresAuth)
```

---

## 5. INTEGRACION SWEETCRM

### 5.1 Arquitectura de Sincronizacion

```
SWEETCRM                    BACKEND                     BD LOCAL
────────                    ───────                     ────────
Cases      ──────────────►  SyncSugarCrmCases.php  ──►  crm_cases
Tasks      ──────────────►       (Command)         ──►  tasks
Users      ──────────────►  SweetCrmService.php    ──►  users
Opportunities ───────────►                         ──►  crm_opportunities
Accounts   ──────────────►                         ──►  clients
```

### 5.2 Campo 'state' vs 'status'

**IMPORTANTE**: En SweetCRM el campo de estado real es `state`, no `status`:

```
SweetCRM.state    →    BD Local.status
─────────────────────────────────────────
Open              →    Abierto
Closed            →    Cerrado
(otros legacy)    →    Mapeo segun statusMap
```

### 5.3 Mapeo de Campos

| SweetCRM | Campo Local | Notas |
|----------|-------------|-------|
| `id` (UUID) | `sweetcrm_id` | Identificador unico |
| `case_number` | `case_number` | Numero legible |
| `name` | `subject` | Titulo |
| `state` | `status` | **NO 'status'** |
| `assigned_user_id` | `sweetcrm_assigned_user_id` | UUID del asignado |
| `account_id` | `sweetcrm_account_id` | UUID del cliente |

### 5.4 Credenciales de Sincronizacion

```bash
# .env del backend
SWEETCRM_URL=https://sweet.icontel.cl/
SWEETCRM_USERNAME=ecerpa
SWEETCRM_PASSWORD=sweetPokemonito23.
SWEETCRM_API_TOKEN=737812fd-2290-03a4-3dde-694a972e8788
```

---

## 6. BASE DE DATOS

### 6.1 Conexion

```
Host: 127.0.0.1 (localhost)
Port: 3306
Database: taskflow
User: root
Password: root
```

### 6.2 Tablas Principales

| Tabla | Registros Aprox. | Descripcion |
|-------|------------------|-------------|
| users | ~26 | Usuarios del sistema |
| clients | ~1,500 | Clientes sincronizados |
| crm_cases | ~7,100 | Casos de SweetCRM |
| tasks | ~4,500 | Tareas (casos y flujos) |
| crm_opportunities | ~500 | Oportunidades de venta |
| flows | ~100 | Flujos internos |
| case_updates | ~2,000 | Historial de avances |
| notifications | ~1,000 | Notificaciones |

### 6.3 Consultas Utiles

```sql
-- Casos abiertos por usuario
SELECT u.name, COUNT(*) as casos
FROM crm_cases c
JOIN users u ON u.sweetcrm_id = c.sweetcrm_assigned_user_id
WHERE c.status = 'Abierto'
GROUP BY u.id;

-- Tareas pendientes de un caso
SELECT * FROM tasks
WHERE case_id = 123
AND status IN ('pending', 'in_progress');

-- Distribucion de estados de casos
SELECT status, COUNT(*) FROM crm_cases GROUP BY status;
```

---

## 7. FLUJOS DE DATOS

### 7.1 Login de Usuario

```
1. Usuario ingresa credenciales en LoginView.vue
2. Frontend: authStore.login({ username, password })
3. Frontend: POST /api/v1/auth/login
4. Backend: AuthController.login()
5. Backend: SweetCrmService.authenticate() → SweetCRM API
6. SweetCRM valida y retorna session_id + user data
7. Backend: Crea/actualiza User local
8. Backend: Genera token Sanctum
9. Backend: Retorna { user, token }
10. Frontend: Guarda en localStorage y store
11. Frontend: Redirect a /dashboard
```

### 7.2 Carga del Dashboard

```
1. DashboardView.vue monta
2. Computed: determina userArea (sales/operations)
3. Store: dashboardStore.fetchAreaBasedContent()
4. API: GET /dashboard/area-content?view=my
5. Backend: DashboardController.getAreaBasedContent()
6. Backend: Consulta BD local (NO SweetCRM)
   - Operaciones: crm_cases + tasks
   - Ventas: crm_opportunities + tasks
7. Backend: Retorna JSON con casos/tareas/stats
8. Frontend: Store actualiza estado
9. Vue: Reactividad renderiza UI
```

### 7.3 Sincronizacion desde SweetCRM

```
1. Ejecutar: php artisan sweetcrm:sync-cases
2. SyncSugarCrmCases.php:
   a. Autenticar con SweetCRM
   b. Obtener casos en chunks de 250
   c. Por cada caso:
      - Buscar/crear en crm_cases
      - Mapear campos (state → status)
      - Guardar avances (avances_1_c, etc.)
   d. Obtener tareas relacionadas
   e. Crear/actualizar en tasks
3. Resultado: BD local actualizada
```

### 7.4 Crear Avance en un Caso

```
1. Usuario en CasesView expande un caso
2. Click en "Agregar avance"
3. Frontend: POST /cases/{id}/updates { content: "..." }
4. Backend: CaseController.addUpdate()
5. Backend: Crea CaseUpdate con type='update'
6. Backend: Opcionalmente notifica al supervisor
7. Frontend: Recarga datos del caso
8. UI: Muestra nuevo avance en historial
```

---

## 8. GUIA DE PROMPTS

### 8.1 Como Pedir Cambios en el Backend

**Para modificar un controlador:**
```
"Modifica el metodo getAreaBasedContent() en
DashboardController.php para que incluya
el campo 'client_name' en cada caso."
```

**Para agregar un nuevo endpoint:**
```
"Agrega un endpoint GET /api/v1/cases/{id}/timeline
en CaseController que retorne todas las actualizaciones
ordenadas por fecha."
```

**Para modificar sincronizacion:**
```
"En SyncSugarCrmCases.php, agrega el campo 'fecha_resolucion_estimada_c'
de SweetCRM y guardalo en la columna 'estimated_resolution_at' de crm_cases."
```

### 8.2 Como Pedir Cambios en el Frontend

**Para modificar una vista:**
```
"En DashboardView.vue, agrega una nueva tarjeta de estadisticas
que muestre la cantidad de casos vencidos (SLA incumplido)."
```

**Para modificar un store:**
```
"En dashboard.js (Pinia), agrega un getter 'overdueTasksCount'
que cuente las tareas con estimated_end_at < hoy."
```

**Para modificar un componente:**
```
"En TaskModal.vue, agrega un campo de fecha 'SLA Due Date'
que se envie como sla_due_date al backend."
```

### 8.3 Como Pedir Cambios en la BD

**Para agregar una columna:**
```
"Crea una migracion que agregue la columna 'resolution_notes'
(text, nullable) a la tabla crm_cases."
```

**Para modificar relaciones:**
```
"Modifica el modelo Task para que tenga una relacion
'followers' (belongsToMany con users a traves de task_followers)."
```

### 8.4 Patrones de Prompts Efectivos

1. **Especifica el archivo exacto:**
   - "En `app/Http/Controllers/Api/CaseController.php`..."
   - "En `src/views/DashboardView.vue`..."

2. **Indica el metodo/funcion:**
   - "...modifica el metodo `getAreaBasedContent()`..."
   - "...en el computed `delegatedTasks`..."

3. **Describe el cambio esperado:**
   - "...para que retorne tambien el campo X..."
   - "...filtrando solo los registros donde Y..."

4. **Da contexto de negocio:**
   - "Esto es para que los usuarios de Operaciones vean..."
   - "El objetivo es sincronizar el campo de SweetCRM que..."

5. **Menciona dependencias:**
   - "Asegurate de actualizar tambien el store correspondiente"
   - "Incluye la migracion y el cambio en el modelo"

### 8.5 Ejemplos de Prompts Completos

**Ejemplo 1: Agregar campo a sincronizacion**
```
Necesito sincronizar el campo 'responsable_c' desde SweetCRM.

1. En SweetCrmService.php metodo getCases(), agrega 'responsable_c'
   al array select_fields.

2. En SyncSugarCrmCases.php, guarda ese valor en una nueva columna
   'responsible_party' de crm_cases.

3. Crea la migracion para agregar esa columna.

4. En CaseController, incluye ese campo en las respuestas.
```

**Ejemplo 2: Nueva funcionalidad en Dashboard**
```
Quiero mostrar en el Dashboard una seccion de "Tareas Vencidas".

1. En DashboardController.php metodo getAreaBasedContent(),
   agrega una consulta que traiga tareas donde estimated_end_at < now()
   y status no sea 'completed'.

2. Retorna esas tareas en un campo 'overdue_tasks' del JSON.

3. En DashboardView.vue, agrega una nueva seccion despues de
   las estadisticas que muestre esas tareas con estilo de alerta (rojo).

4. En dashboard.js store, agrega el estado 'overdueTasks' y
   un getter 'overdueTasksCount'.
```

**Ejemplo 3: Corregir bug**
```
Hay un bug: cuando hago click en un caso del dashboard,
me da error 404 "No query results for model CrmCase NaN".

El problema esta en DashboardView.vue en handleCaseClick().
Verifica que se este enviando el ID correcto (entero de BD local)
y no el sweetcrm_id (UUID).
```

---

## APENDICE A: Comandos Utiles

```bash
# Backend - Artisan
docker exec taskflow_app php artisan migrate           # Ejecutar migraciones
docker exec taskflow_app php artisan migrate:rollback  # Revertir ultima
docker exec taskflow_app php artisan tinker            # REPL de PHP
docker exec taskflow_app php artisan route:list        # Ver rutas
docker exec taskflow_app php artisan sweetcrm:sync-cases  # Sincronizar

# Base de Datos
docker exec taskflow_mariadb mysql -uroot -proot taskflow

# Frontend
cd taskflow-frontend && npm run dev   # Desarrollo
cd taskflow-frontend && npm run build # Produccion

# Docker
docker-compose up -d          # Iniciar contenedores
docker-compose down           # Detener
docker-compose logs -f app    # Ver logs del backend
docker ps                     # Ver contenedores activos
```

---

## APENDICE B: Variables de Entorno

**Backend (.env)**
```bash
APP_URL=http://localhost:8080
DB_CONNECTION=mysql
DB_HOST=taskflow_mariadb
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=root
SWEETCRM_URL=https://sweet.icontel.cl/
SWEETCRM_USERNAME=ecerpa
SWEETCRM_PASSWORD=sweetPokemonito23.
```

**Frontend (.env.local)**
```bash
VITE_API_BASE_URL=http://localhost:8080/api/v1
```

---

## 9. COMO FUNCIONAN LOS METODOS (DETALLE INTERNO)

Esta seccion explica paso a paso como funcionan los metodos principales,
que parametros aceptan, que retornan, y como modificarlos.

---

### 9.1 DashboardController - getAreaBasedContent()

**Archivo**: `app/Http/Controllers/Api/DashboardController.php`
**Linea aproximada**: 180-310

**Proposito**: Detectar el departamento del usuario y retornar contenido especifico (Ventas vs Operaciones)

**Flujo paso a paso**:

```php
public function getAreaBasedContent(Request $request) {
    // 1. Obtener usuario autenticado
    $user = $request->user();

    // 2. Validar que tenga ID de SweetCRM
    if (!$user->sweetcrm_id) {
        return error(401, 'Usuario sin ID de SweetCRM');
    }

    // 3. Detectar departamento
    $department = strtolower($user->department ?? '');
    $isSalesTeam = in_array($department, ['ventas', 'comercial', 'sales']);

    // 4. Rutear a subfuncion especifica
    if ($isSalesTeam) {
        return $this->getSalesTeamContent($request, $user);
    } else {
        return $this->getOperationsTeamContent($request, $user);
    }
}
```

**Parametros aceptados**:
- `?view=my` → Solo contenido asignado al usuario
- `?view=area` → Todo el contenido del area/departamento

**Respuesta JSON**:
```json
{
    "success": true,
    "user_area": "operations",
    "view_mode": "my",
    "data": {
        "cases": [...],
        "tasks": [...],
        "total": 25,
        "total_cases": 15,
        "total_tasks": 10
    }
}
```

**Para modificar**: Si quieres agregar un campo nuevo a los casos:
1. Busca el metodo `getOperationsTeamContent()`
2. Encuentra el `->map(function ($case) {`
3. Agrega el campo en el array de retorno

---

### 9.2 DashboardController - getOperationsTeamContent()

**Proposito**: Obtener Casos + Tareas desde BD LOCAL (no API de SweetCRM)

**Query de Casos**:
```php
// Consulta casos activos (no cerrados)
$casesQuery = \App\Models\CrmCase::whereNotIn('status',
    ['Closed', 'Rejected', 'Duplicate', 'Merged', 'Cerrado']
);

// Si view='my' filtra por usuario
if ($viewMode === 'my') {
    $casesQuery->where('sweetcrm_assigned_user_id', $userSweetCrmId);
}

$cases = $casesQuery->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();
```

**Query de Tareas**:
```php
// Solo tareas activas (pending, in_progress)
$activeTaskStatuses = ['pending', 'in_progress'];
$tasksQuery = \App\Models\Task::whereIn('status', $activeTaskStatuses)
    ->where('assignee_id', $user->id)  // SIEMPRE personales
    ->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();
```

**Transformacion de datos (lo que se envia al frontend)**:
```php
// Cada CASO se transforma asi:
[
    'id' => $case->id,                    // ID local (entero)
    'sweetcrm_id' => $case->sweetcrm_id,  // UUID de SweetCRM
    'type' => 'case',
    'title' => $case->subject,
    'case_number' => $case->case_number,
    'status' => $case->status,
    'priority' => $case->priority,
    'assigned_user_name' => $case->assigned_user_name,
    'date_entered' => $case->sweetcrm_created_at,
]

// Cada TAREA se transforma asi:
[
    'id' => $task->id,
    'sweetcrm_id' => $task->sweetcrm_id,
    'type' => 'task',
    'title' => $task->title,
    'status' => $task->status,
    'priority' => $task->priority,
    'date_due' => $task->estimated_end_at,
    'case_id' => $task->case_id,
    'crm_case' => [   // Si tiene caso vinculado
        'id' => $task->crmCase->id,
        'case_number' => $task->crmCase->case_number,
    ]
]
```

**Para agregar un campo nuevo**:
```php
// Ejemplo: agregar 'client_name' a cada caso
'client_name' => $case->client?->name ?? 'Sin cliente',
```

---

### 9.3 CaseController - index() (Listar casos)

**Archivo**: `app/Http/Controllers/Api/CaseController.php`

**Query con filtros**:
```php
public function index(Request $request) {
    // 1. Eager loading (evita N+1 queries)
    $query = CrmCase::with([
        'client:id,name',
        'assignedUser:id,name,department'
    ])->withCount('tasks');

    // 2. FILTRO: Busqueda
    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
              ->orWhere('case_number', 'like', "%{$search}%")
              ->orWhereHas('client', fn($cq) =>
                  $cq->where('name', 'like', "%{$search}%")
              );
        });
    }

    // 3. FILTRO: Estado
    if ($request->status && $request->status !== 'all') {
        $query->where('status', $request->status);
    }

    // 4. FILTRO: Prioridad
    if ($request->priority && $request->priority !== 'all') {
        $query->where('priority', $request->priority);
    }

    // 5. FILTRO: Solo mis casos
    if ($request->assigned_to_me) {
        $user = auth()->user();
        $query->where('sweetcrm_assigned_user_id', $user->sweetcrm_id);
    }

    // 6. Paginacion
    return $query->orderBy('created_at', 'desc')
                 ->paginate($request->per_page ?? 20);
}
```

**Parametros aceptados**:
```
GET /api/v1/cases?search=fibra&status=Abierto&priority=Alta&assigned_to_me=true&per_page=20
```

**Respuesta**:
```json
{
    "data": [
        {
            "id": 123,
            "case_number": "7447",
            "subject": "Instalacion fibra",
            "status": "Abierto",
            "client": { "id": 1, "name": "Empresa X" },
            "tasks_count": 5
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "total": 198
    }
}
```

---

### 9.4 CaseController - addUpdate() (Agregar avance)

**Proposito**: Crear un comentario/avance en un caso

```php
public function addUpdate(Request $request, $id) {
    // 1. Validacion
    $request->validate([
        'content' => 'required|string|min:3',
        'attachments.*' => 'nullable|file|max:10240',  // 10MB
    ]);

    // 2. Obtener caso
    $case = CrmCase::findOrFail($id);

    // 3. Crear avance
    $update = $case->updates()->create([
        'user_id' => auth()->user()->id,
        'content' => $request->content,
        'type' => 'update'  // Otros tipos: closure_request, closure_approved
    ]);

    // 4. Procesar archivos adjuntos
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $path = $file->store('update_attachments', 'public');
            $update->attachments()->create([
                'user_id' => auth()->user()->id,
                'name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
            ]);
        }
    }

    return response()->json([
        'message' => 'Avance registrado',
        'update' => $update->load('user', 'attachments')
    ]);
}
```

**Request desde frontend**:
```javascript
// En api.js
casesAPI.addUpdate(caseId, { content: 'Trabajo completado...' })

// Con archivo
const formData = new FormData()
formData.append('content', 'Avance con archivo')
formData.append('attachments[]', file)
api.post(`/cases/${id}/updates`, formData)
```

---

### 9.5 Flujo de Cierre de Caso

**Estados del flujo**:
```
CASO ABIERTO
    ↓
requestClosure() → closure_requested = true
    ↓
┌─────────────────────┐
│ El CREADOR decide:  │
├─────────────────────┤
│ approveClosure()    │ → status = 'Cerrado' ✓
│ rejectClosure()     │ → closure_requested = false, razon guardada
└─────────────────────┘
```

**Codigo de requestClosure()**:
```php
public function requestClosure(Request $request, $id) {
    $case = CrmCase::findOrFail($id);

    // Verificar no duplicado
    if ($case->closure_requested) {
        return response()->json(['message' => 'Ya solicitado'], 400);
    }

    // Actualizar caso
    $case->update([
        'closure_requested' => true,
        'closure_requested_at' => now(),
        'closure_requested_by' => auth()->user()->id
    ]);

    // Crear registro de avance
    $case->updates()->create([
        'user_id' => auth()->user()->id,
        'content' => 'Ha solicitado el cierre del caso.',
        'type' => 'closure_request'
    ]);

    return response()->json(['message' => 'Solicitud enviada']);
}
```

---

### 9.6 TaskController - update() (Actualizar tarea)

**Logica de cambios automaticos**:
```php
public function update(Request $request, $id) {
    $task = Task::findOrFail($id);
    $validated = $request->validate([...]);

    // AUTOMATICO: Si se completa, poner progress=100 y fecha
    if ($validated['status'] === 'completed') {
        $validated['progress'] = 100;
        $validated['actual_end_at'] = now();
    }

    // AUTOMATICO: Si se inicia, guardar fecha de inicio
    if ($validated['status'] === 'in_progress' && !$task->actual_start_at) {
        $validated['actual_start_at'] = now();
    }

    // VALIDACION: Si requiere adjuntos para completar
    if ($validated['status'] === 'completed' && $task->allow_attachments) {
        if ($task->attachments()->count() === 0) {
            return response()->json([
                'message' => 'Debes adjuntar al menos un documento'
            ], 422);
        }
    }

    $task->update($validated);

    return response()->json(['task' => $task]);
}
```

**Cambios automaticos**:
| Cambio | Efecto |
|--------|--------|
| `status: 'in_progress'` | `actual_start_at = NOW()` |
| `status: 'completed'` | `progress = 100`, `actual_end_at = NOW()` |
| `allow_attachments = true` + completar | Requiere al menos 1 adjunto |

---

### 9.7 SyncSugarCrmCases - Sincronizacion

**Archivo**: `app/Console/Commands/SyncSugarCrmCases.php`

**Ejecucion**:
```bash
docker exec taskflow_app php artisan sweetcrm:sync-cases
```

**Flujo completo**:
```
1. Autenticar con SweetCRM
   $sessionId = sweetCrmService->getSessionId(user, pass)

2. Obtener casos en chunks de 250
   loop (offset = 0, 250, 500...):
       $cases = sweetCrmService->getCases($sessionId, offset, 250)

3. Por cada caso:
   CrmCase::updateOrCreate(
       ['sweetcrm_id' => $caseId],
       [
           'case_number' => $nvl['case_number']['value'],
           'subject' => $nvl['name']['value'],
           'status' => $statusMap[$nvl['state']['value']],  // OJO: 'state' no 'status'
           ...
       ]
   )

4. Sincronizar tareas de esos casos
   $tasks = sweetCrmService->getTasks($sessionId, "parent_type='Cases'")

5. Por cada tarea:
   Task::updateOrCreate(
       ['sweetcrm_id' => $taskId],
       [
           'case_id' => $crmCase->id,  // Vincular al caso local
           'title' => $nvl['name']['value'],
           'status' => mapStatus($nvl['status']['value']),
           ...
       ]
   )
```

**Mapeo de estados (MUY IMPORTANTE)**:
```php
// Campo 'state' de SweetCRM → campo 'status' local
$statusMap = [
    'Open' => 'Abierto',      // Caso activo
    'Closed' => 'Cerrado',    // Caso finalizado
    'New' => 'Nuevo',
    'Assigned' => 'Asignado',
];

// Para tareas:
'In Progress' → 'in_progress'
'Completed' → 'completed'
'Not Started' → 'pending'
```

**Para agregar un campo nuevo a sincronizar**:

1. En `SweetCrmService.php` metodo `getCases()`, agrega al array `select_fields`:
```php
'select_fields' => [
    'id',
    'case_number',
    'name',
    'state',
    'mi_campo_nuevo_c',  // AGREGAR AQUI
    ...
]
```

2. En `SyncSugarCrmCases.php` metodo `syncCases()`, usa el valor:
```php
CrmCase::updateOrCreate(
    ['sweetcrm_id' => $sweetId],
    [
        ...
        'mi_campo_local' => $nvl['mi_campo_nuevo_c']['value'] ?? null,
    ]
);
```

3. Crea migracion si la columna no existe en BD.

---

### 9.8 Frontend - DashboardView.vue

**Archivo**: `src/views/DashboardView.vue`

**Ciclo de vida**:
```javascript
onMounted(async () => {
    await loadData()
})

const loadData = async () => {
    // 1. Cargar contenido segun area del usuario
    await dashboardStore.fetchAreaBasedContent()

    // 2. Cargar delegadas
    if (dashboardStore.userArea === 'sales') {
        await dashboardStore.fetchDelegatedSales()
    } else {
        await dashboardStore.fetchDelegated()
    }
}
```

**Computed properties clave**:
```javascript
// Estadisticas calculadas
const stats = computed(() => ({
    activeFlows: dashboardStore.cases.length,
    pendingTasks: dashboardStore.allTasksFlat.filter(t =>
        t.status !== 'Completed'
    ).length,
    overdueTasks: dashboardStore.allTasksFlat.filter(t => {
        if (!t.date_due) return false
        return new Date(t.date_due) < new Date() && t.status !== 'Completed'
    }).length,
}))

// Tareas delegadas combinadas
const delegatedTasks = computed(() => {
    if (dashboardStore.userArea === 'sales') {
        return [
            ...dashboardStore.delegatedSales.opportunities,
            ...dashboardStore.delegatedSales.tasks
        ]
    } else {
        return [
            ...dashboardStore.delegated.cases,
            ...dashboardStore.delegated.tasks
        ]
    }
})
```

**Metodos de navegacion**:
```javascript
// Click en un caso → ir a detalle
const handleCaseClick = (crmCase, event) => {
    if (event.target.closest('button')) return  // Ignorar si es boton expand
    router.push({ path: '/cases', query: { caseId: crmCase.id } })
}

// Click en una tarea → ir a su caso
const handleTaskClick = (task) => {
    const caseId = task.crm_case?.id || task.case_id
    if (caseId) {
        router.push({ path: '/cases', query: { caseId, taskId: task.id } })
    }
}
```

**Para agregar una nueva seccion**:
1. Agrega el HTML en el template
2. Crea un computed que filtre/procese los datos
3. Si necesitas datos nuevos, modificar el store y/o backend

---

### 9.9 Frontend - dashboard.js (Store Pinia)

**Archivo**: `src/stores/dashboard.js`

**Estado**:
```javascript
state: () => ({
    cases: [],              // Casos con tareas anidadas
    orphanTasks: [],        // Tareas sin caso
    opportunities: [],      // Para equipo ventas
    delegated: {
        cases: [],
        tasks: [],
        total: 0,
        pending: 0
    },
    scope: 'my',            // 'my' | 'area'
    userArea: null,         // 'sales' | 'operations'
    loading: false,
})
```

**Accion principal - fetchAreaBasedContent()**:
```javascript
async fetchAreaBasedContent() {
    this.loading = true
    try {
        // Llamar API con scope actual
        const response = await dashboardAPI.getContentByView(this.scope)

        if (response.data.success) {
            this.userArea = response.data.user_area
            const data = response.data.data

            if (this.userArea === 'sales') {
                this.opportunities = data.opportunities || []
                this.orphanTasks = data.tasks || []
                this.cases = []
            } else {
                // Agregar propiedad 'expanded' a cada caso
                this.cases = (data.cases || []).map(c => ({
                    ...c,
                    expanded: false,
                    tasks: c.tasks || []
                }))
                this.orphanTasks = data.tasks || []
            }
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        this.loading = false
    }
}
```

**Getter importante - allTasksFlat**:
```javascript
getters: {
    // Combina tareas de casos + huerfanas en lista plana
    allTasksFlat: (state) => {
        const tasksFromCases = state.cases.flatMap(c =>
            (c.tasks || []).map(t => ({
                ...t,
                crm_case: {
                    id: c.id,
                    case_number: c.case_number,
                    subject: c.title
                }
            }))
        )
        return [...tasksFromCases, ...state.orphanTasks]
    }
}
```

---

### 9.10 Frontend - api.js (Cliente HTTP)

**Archivo**: `src/services/api.js`

**Configuracion base**:
```javascript
import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL,  // http://localhost:8080/api/v1
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true,
})

// Agregar token automaticamente
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Manejar error 401 (no autenticado)
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)
```

**APIs exportadas**:
```javascript
export const authAPI = {
    login: (creds) => api.post('auth/login', creds),
    logout: () => api.post('auth/logout'),
    me: () => api.get('auth/me'),
}

export const dashboardAPI = {
    getContentByView: (view = 'my') =>
        api.get('dashboard/area-content', { params: { view } }),
    getDelegated: () => api.get('dashboard/delegated'),
}

export const casesAPI = {
    getAll: (params) => api.get('cases', { params }),
    getOne: (id) => api.get(`cases/${id}`),
    addUpdate: (id, data) => api.post(`cases/${id}/updates`, data),
    requestClosure: (id) => api.post(`cases/${id}/request-closure`),
}

export const tasksAPI = {
    getAll: (params) => api.get('tasks', { params }),
    create: (data) => api.post('tasks', data),
    update: (id, data) => api.put(`tasks/${id}`, data),
    uploadAttachment: (id, formData) =>
        api.post(`tasks/${id}/attachments`, formData),
}
```

**Para agregar un nuevo endpoint**:
```javascript
export const casesAPI = {
    // ... existentes ...

    // NUEVO: Obtener timeline del caso
    getTimeline: (id) => api.get(`cases/${id}/timeline`),
}
```

---

## 10. MAPEOS DE DATOS COMPLETOS

### Estados de Casos

| SweetCRM (state) | BD Local (status) | UI Mostrado |
|------------------|-------------------|-------------|
| `Open` | `Abierto` | Badge verde |
| `New` | `Nuevo` | Badge azul |
| `Assigned` | `Asignado` | Badge azul |
| `Closed` | `Cerrado` | Badge gris |
| `Pending Input` | `Pendiente Datos` | Badge amarillo |
| `Rejected` | `Rechazado` | Badge rojo |

### Estados de Tareas

| SweetCRM | BD Local | Significado |
|----------|----------|-------------|
| `Not Started` | `pending` | No iniciada |
| `In Progress` | `in_progress` | En ejecucion |
| `Completed` | `completed` | Finalizada |
| `Deferred` | `blocked` | Bloqueada |

### Prioridades

| SweetCRM | BD Local | Color |
|----------|----------|-------|
| `P1` / `High` | `high` | Rojo |
| `P2` / `Medium` | `medium` | Amarillo |
| `P3` / `Low` | `low` | Verde |

### Departamentos

| SweetCRM | Taskflow | Dashboard muestra |
|----------|----------|-------------------|
| `Ventas`, `Sales` | `Ventas` | Oportunidades + Tareas |
| `Operaciones`, `Ops` | `Operaciones` | Casos + Tareas |
| `Soporte` | `Soporte` | Casos + Tareas |
| (otro) | `General` | Casos + Tareas |

---

## 11. FLUJOS DE NEGOCIO COMPLETOS

### Flujo 1: Login hasta Dashboard

```
USUARIO ingresa credenciales
    ↓
LoginView.vue: authStore.login({ username, password })
    ↓
POST /api/v1/auth/login
    ↓
AuthController.login():
    → sweetCrmService.authenticate(user, pass)
    → SweetCRM valida credenciales
    → Retorna session_id + user data
    ↓
Backend:
    → User::firstOrCreate(['sweetcrm_id' => id])
    → user->createToken('api-token')
    ↓
Response: { user, token }
    ↓
Frontend:
    → localStorage.setItem('token', token)
    → router.push('/dashboard')
    ↓
DashboardView.vue monta:
    → dashboardStore.fetchAreaBasedContent()
    ↓
GET /api/v1/dashboard/area-content?view=my
    ↓
Backend consulta BD LOCAL (no SweetCRM):
    → SELECT * FROM crm_cases WHERE status != 'Cerrado' ...
    → SELECT * FROM tasks WHERE assignee_id = ? ...
    ↓
Response: { cases, tasks, total }
    ↓
Frontend renderiza dashboard
```

### Flujo 2: Sincronizacion SweetCRM

```
COMANDO: php artisan sweetcrm:sync-cases
    ↓
1. Autenticar
   $sessionId = sweetCrmService->getSessionId(user, pass)
    ↓
2. Loop de casos (chunks de 250)
   offset = 0
   while (hay mas casos):
       $cases = sweetCrmService->getCases($sessionId, offset, 250)
       foreach ($cases as $case):
           CrmCase::updateOrCreate(
               ['sweetcrm_id' => $case['id']],
               [
                   'case_number' => $case['case_number'],
                   'subject' => $case['name'],
                   'status' => $statusMap[$case['state']],  // 'state' no 'status'
               ]
           )
       offset += 250
    ↓
3. Sincronizar tareas
   $tasks = sweetCrmService->getTasks($sessionId)
   foreach ($tasks as $task):
       $crmCase = CrmCase::where('sweetcrm_id', $task['parent_id'])->first()
       Task::updateOrCreate(
           ['sweetcrm_id' => $task['id']],
           [
               'case_id' => $crmCase->id,
               'title' => $task['name'],
               'status' => mapTaskStatus($task['status']),
           ]
       )
    ↓
4. Marcar cerrados los que ya no existen en CRM
   CrmCase::whereNotIn('sweetcrm_id', $syncedIds)
          ->update(['status' => 'Cerrado'])
```

### Flujo 3: Ciclo de Cierre de Caso

```
CASO ABIERTO (status: 'Abierto')
    ↓
Usuario asignado: "Solicitar cierre"
    POST /cases/{id}/request-closure
    ↓
Backend:
    $case->update([
        'closure_requested' => true,
        'closure_requested_at' => now(),
        'closure_requested_by' => $user->id
    ])
    CaseUpdate::create(['type' => 'closure_request'])
    ↓
Estado: PENDIENTE DE APROBACION
    (Frontend muestra alerta amarilla)
    ↓
┌─────────────────────────────────────────┐
│ CREADOR del caso decide:                │
├─────────────────────────────────────────┤
│ ✓ Aprobar                               │
│   POST /cases/{id}/approve-closure      │
│   → status = 'Cerrado'                  │
│   → CASO CERRADO                        │
│                                         │
│ ✗ Rechazar                              │
│   POST /cases/{id}/reject-closure       │
│   → closure_requested = false           │
│   → closure_rejection_reason = "..."    │
│   → Vuelve a ABIERTO                    │
└─────────────────────────────────────────┘
```

### Flujo 4: Completar Tarea

```
TAREA en estado 'in_progress'
    ↓
Usuario: Cambiar a 'completed'
    PUT /tasks/{id} { status: 'completed' }
    ↓
Backend TaskController.update():
    ↓
    // 1. Validar autorizacion
    Gate::authorize('execute', $task)
    ↓
    // 2. Cambios automaticos
    if (status === 'completed') {
        $validated['progress'] = 100;
        $validated['actual_end_at'] = now();
    }
    ↓
    // 3. Validar adjuntos si requeridos
    if ($task->allow_attachments) {
        if ($task->attachments()->count() === 0) {
            return error(422, 'Adjunto requerido');
        }
    }
    ↓
    // 4. Guardar
    $task->update($validated);
    ↓
Response: { task }
    ↓
Frontend actualiza UI
```

---

*Documento generado el 2026-01-08*
*Usar como referencia para formular prompts precisos*
