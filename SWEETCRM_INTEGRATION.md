# 🔄 Integración SugarCRM con Taskflow

## ✅ ESTADO ACTUAL: INTEGRACIÓN COMPLETA Y FUNCIONAL

**Última actualización:** 2025-12-23

La integración con **SugarCRM** está **completamente implementada y funcional**. Se descubrió que "SweetCRM" es una instalación de **SugarCRM** que utiliza la API REST v4_1 (versión legacy).

### ✅ Completado:
- ✅ Migración de base de datos
- ✅ Servicio de integración (SweetCrmService) con API v4_1
- ✅ Controladores de API (AuthController, SweetCrmController)
- ✅ Frontend con toggle SugarCRM
- ✅ Documentación completa
- ✅ Comando de diagnóstico funcional
- ✅ Autenticación completamente operativa

### 📡 Información Técnica Descubierta:
- **Sistema:** SugarCRM con API REST v4_1 (legacy)
- **Endpoint de autenticación:** `POST /service/v4_1/rest.php`
- **Método de autenticación:** Session-based con MD5 password hashing
- **API alternativa disponible:** `/api/rest.php` (requiere configuración OAuth adicional - no utilizada)

**Ejecutar diagnóstico:**
```bash
docker exec taskflow_app_dev php artisan sweetcrm:diagnose
```

---

## 📋 Resumen

Esta integración permite que los usuarios de Taskflow se autentiquen utilizando sus credenciales de SugarCRM y sincronizar datos de clientes automáticamente.

## ✨ Funcionalidades Implementadas

### 1. **Autenticación SSO (Single Sign-On)**
- ✅ Login con credenciales de SweetCRM
- ✅ Creación automática de usuarios desde SweetCRM
- ✅ Sincronización de datos de usuario
- ✅ Mapeo de roles entre SweetCRM y Taskflow
- ✅ Fallback a autenticación local si SweetCRM falla

### 2. **Sincronización de Datos**
- ✅ Sincronización de clientes desde SweetCRM
- ✅ Sincronización individual o masiva
- ✅ Cache de datos para mejorar performance
- ✅ Tracking de última sincronización

### 3. **Gestión de Perfiles**
- ✅ Vinculación de usuarios con SweetCRM
- ✅ Actualización automática de datos
- ✅ Sincronización manual disponible

---

## 🚀 Configuración

### 1. Variables de Entorno

Agrega las siguientes variables en tu archivo `.env`:

```env
# SweetCRM Integration
SWEETCRM_ENABLED=true
SWEETCRM_URL=https://tu-instancia-sweetcrm.com
SWEETCRM_API_TOKEN=tu_token_de_api
SWEETCRM_SYNC_INTERVAL=3600  # Intervalo de sincronización en segundos (1 hora)
```

### 2. Ejecutar Migraciones

```bash
# Dentro del contenedor Docker
docker exec taskflow_app_dev php artisan migrate

# O localmente
php artisan migrate
```

### 3. Limpiar Cachés

```bash
docker exec taskflow_app_dev php artisan config:cache
docker exec taskflow_app_dev php artisan route:cache
```

---

## 📡 API Endpoints

### Autenticación

#### POST `/api/v1/auth/login`
Login estándar con opción de SweetCRM

**Request:**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "contraseña",
  "use_sweetcrm": true  // opcional, default: según config
}
```

**Response:**
```json
{
  "message": "Login exitoso",
  "user": {
    "id": 1,
    "name": "Usuario",
    "email": "usuario@ejemplo.com",
    "role": "user",
    "sweetcrm_id": "abc123"
  },
  "token": "token_de_acceso",
  "expires_in": 3600,
  "auth_source": "sweetcrm"
}
```

#### POST `/api/v1/auth/sweetcrm-login`
Login exclusivo con SweetCRM usando **username**

**Request:**
```json
{
  "username": "usuario_sweetcrm",
  "password": "contraseña"
}
```

**Nota:** SweetCRM utiliza `username` en lugar de `email` para autenticación.

### Sincronización

#### GET `/api/v1/sweetcrm/ping`
Verificar conexión con SweetCRM

**Response:**
```json
{
  "connected": true,
  "service": "SweetCRM",
  "url": "https://tu-instancia-sweetcrm.com"
}
```

#### POST `/api/v1/sweetcrm/sync-clients`
Sincronizar todos los clientes

**Request:**
```json
{
  "filters": {  // opcional
    "status": "active",
    "industry": "Technology"
  }
}
```

**Response:**
```json
{
  "message": "Sincronización completada",
  "total": 150,
  "synced": 148,
  "created": 50,
  "updated": 98,
  "errors": []
}
```

#### POST `/api/v1/sweetcrm/sync-client/{sweetcrmId}`
Sincronizar un cliente específico

**Response:**
```json
{
  "message": "Cliente created exitosamente",
  "client": { /* datos del cliente */ },
  "action": "created"  // o "updated"
}
```

#### GET `/api/v1/sweetcrm/user/{sweetcrmId}`
Obtener datos de usuario desde SweetCRM

#### POST `/api/v1/sweetcrm/sync-me`
Sincronizar datos del usuario actual

---

## 💻 Uso en el Frontend

### Login con Toggle SweetCRM

El componente de login (`LoginView.vue`) incluye un toggle para elegir entre autenticación local y SweetCRM:

```vue
<template>
  <!-- Toggle para SweetCRM -->
  <div class="sweetcrm-toggle">
    <span>Autenticar con SweetCRM</span>
    <button @click="useSweetCrm = !useSweetCrm"></button>
  </div>
</template>

<script setup>
const useSweetCrm = ref(false)

const handleLogin = async () => {
  if (useSweetCrm.value) {
    result = await authStore.sweetCrmLogin(credentials.value)
  } else {
    result = await authStore.login(credentials.value)
  }
}
</script>
```

### Servicios de API

```javascript
import { sweetCrmAPI } from '@/services/api'

// Verificar conexión
const { data } = await sweetCrmAPI.ping()

// Sincronizar clientes
await sweetCrmAPI.syncClients({ status: 'active' })

// Sincronizar cliente específico
await sweetCrmAPI.syncClient('abc123')

// Sincronizar usuario actual
await sweetCrmAPI.syncMe()
```

---

## 🔧 Arquitectura

### Backend (Laravel)

```
app/
├── Http/Controllers/Api/
│   ├── AuthController.php          # Autenticación con soporte SweetCRM
│   └── SweetCrmController.php      # Endpoints de sincronización
├── Services/
│   └── SweetCrmService.php         # Lógica de integración
└── Models/
    ├── User.php                     # Campos: sweetcrm_id, sweetcrm_user_type, sweetcrm_synced_at
    └── Client.php                   # Campos: sweetcrm_id, sweetcrm_synced_at
```

### Frontend (Vue.js)

```
src/
├── services/
│   └── api.js                       # Endpoints de SweetCRM
├── stores/
│   └── auth.js                      # Login con SweetCRM
└── views/
    └── LoginView.vue                # UI con toggle SweetCRM
```

---

## 🔐 Seguridad

### 1. **Autenticación**
- El token de SweetCRM se almacena de forma segura en variables de entorno
- Los passwords nunca se almacenan, solo se verifican contra SweetCRM
- Fallback a autenticación local si SweetCRM no está disponible

### 2. **Autorización**
- Los roles de SweetCRM se mapean a roles de Taskflow:
  - `admin` → `admin`
  - `manager` → `project_manager`
  - `user` / `client` → `user`

### 3. **Cache**
- Los datos de SweetCRM se cachean por 1 hora (configurable)
- El cache se invalida automáticamente al sincronizar
- Protección contra llamadas excesivas a la API

---

## 📊 Flujo de Autenticación

```
Usuario Ingresa Credenciales
         ↓
   ¿SweetCRM Habilitado?
         ↓
    [SÍ]        [NO]
     ↓           ↓
 SweetCRM     Local
   API         DB
     ↓           ↓
¿Usuario    ¿Usuario
 Existe?     Existe?
     ↓           ↓
[SÍ] [NO]   [SÍ] [NO]
  ↓    ↓      ↓    ↓
 Act  Crear  OK  Error
  ↓    ↓      ↓
 OK   OK   Redirect
  ↓    ↓      ↓
  Dashboard
```

---

## 🧪 Testing

### Verificar Conexión

```bash
curl -X GET http://localhost:8080/api/v1/sweetcrm/ping \
  -H "Authorization: Bearer {token}"
```

### Login con SweetCRM

```bash
curl -X POST http://localhost:8080/api/v1/auth/sweetcrm-login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "usuario_sweetcrm",
    "password": "contraseña"
  }'
```

### Sincronizar Clientes

```bash
curl -X POST http://localhost:8080/api/v1/sweetcrm/sync-clients \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "filters": {
      "status": "active"
    }
  }'
```

---

## 🐛 Troubleshooting

### Error: "No se pudo conectar con SweetCRM"

**Solución:**
1. Verificar que `SWEETCRM_URL` es correcta
2. Verificar que `SWEETCRM_API_TOKEN` es válida
3. Verificar conectividad de red

```bash
curl -X GET {SWEETCRM_URL}/api/ping
```

### Error: "Class 'Pusher\Pusher' not found"

Este error es independiente de SweetCRM. Ver documentación de Broadcasting.

### Los usuarios no se crean automáticamente

**Solución:**
1. Verificar que `SWEETCRM_ENABLED=true`
2. Verificar que el endpoint de SweetCRM `/api/auth/login` retorna datos de usuario
3. Revisar logs: `storage/logs/laravel.log`

---

## 📝 Estructura de Respuesta de SweetCRM

Para que la integración funcione correctamente, SweetCRM debe retornar la siguiente estructura al autenticar:

**Endpoint:** `POST {SWEETCRM_URL}/api/auth/login`

**Request:**
```json
{
  "username": "usuario_sweetcrm",
  "password": "contraseña"
}
```

**Response esperada:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "abc123",
      "name": "Usuario Ejemplo",
      "username": "usuario_sweetcrm",
      "email": "usuario@ejemplo.com",  // opcional
      "role": "user",
      "user_type": "client"  // opcional
    },
    "token": "sweetcrm_token"  // opcional
  }
}
```

**Notas importantes:**
- SweetCRM debe aceptar `username` en lugar de `email` para login
- Si el usuario no tiene `email`, se generará uno automático: `{username}@sweetcrm.local`
- El campo `name` o `username` se usará como nombre de usuario en Taskflow

---

## 🔄 Sincronización Automática

### Configurar Cron Job

Para sincronizar automáticamente, agregar a `crontab`:

```bash
# Sincronizar clientes cada hora
0 * * * * docker exec taskflow_app_dev php artisan app:sync-sweetcrm-clients
```

### Crear Comando Artisan

```bash
php artisan make:command SyncSweetCrmClients
```

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SweetCrmService;
use App\Http\Controllers\Api\SweetCrmController;

class SyncSweetCrmClients extends Command
{
    protected $signature = 'app:sync-sweetcrm-clients';
    protected $description = 'Sync clients from SweetCRM';

    public function handle(SweetCrmService $sweetCrmService)
    {
        $this->info('Starting SweetCRM sync...');

        $clients = $sweetCrmService->getClients();

        // Lógica de sincronización

        $this->info("Synced {count($clients)} clients");
    }
}
```

---

## 📚 Recursos Adicionales

- [Documentación de Laravel HTTP Client](https://laravel.com/docs/11.x/http-client)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Pinia State Management](https://pinia.vuejs.org/)

---

## ✅ Checklist de Implementación

- [x] Migración de base de datos
- [x] Servicio de integración (SweetCrmService)
- [x] Endpoints de autenticación
- [x] Endpoints de sincronización
- [x] Frontend con toggle SweetCRM
- [x] Store de Pinia actualizado
- [x] Mapeo de roles
- [x] Cache de datos
- [x] Manejo de errores
- [x] Logs de auditoría
- [ ] Tests unitarios
- [ ] Tests de integración
- [ ] Sincronización automática (cron)

---

## 🎯 Próximos Pasos

1. **Webhooks de SweetCRM**: Recibir notificaciones de cambios en tiempo real
2. **Sync bidireccional**: Enviar cambios de Taskflow a SweetCRM
3. **Gestión de conflictos**: Resolver conflictos cuando hay cambios en ambos sistemas
4. **Dashboard de sincronización**: Panel para ver el estado de sincronización
5. **Logs detallados**: Historial de sincronizaciones

---

## 👨‍💻 Soporte

Para reportar problemas o solicitar funcionalidades:
- Crear un issue en el repositorio
- Contactar al equipo de desarrollo

---

**Última actualización:** 2025-12-23
**Versión:** 1.0.0
