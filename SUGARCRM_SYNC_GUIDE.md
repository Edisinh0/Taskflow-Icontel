# 📥 Guía de Sincronización de Clientes desde SugarCRM

## 🎯 Descripción

Esta guía explica cómo sincronizar clientes (Accounts) desde SugarCRM hacia Taskflow usando la API REST v4_1.

## ✅ Prerequisitos

- Credenciales válidas de SugarCRM (username y password)
- SugarCRM debe tener módulo "Accounts" con clientes
- Laravel debe estar configurado con la URL de SugarCRM correcta

##  Métodos de Sincronización

### 1. 🖥️ Comando Artisan (Recomendado)

El método más fácil y rápido para sincronizar clientes.

#### Sintaxis:

```bash
docker exec taskflow_app_dev php artisan sweetcrm:sync-clients {username} {password} [--limit=100]
```

#### Parámetros:

- `username`: Tu nombre de usuario de SugarCRM
- `password`: Tu contraseña de SugarCRM
- `--limit`: (Opcional) Número máximo de clientes a sincronizar (default: 100)

#### Ejemplos:

```bash
# Sincronizar hasta 100 clientes
docker exec taskflow_app_dev php artisan sweetcrm:sync-clients admin password123

# Sincronizar hasta 500 clientes
docker exec taskflow_app_dev php artisan sweetcrm:sync-clients admin password123 --limit=500

# Sincronizar todos los clientes disponibles
docker exec taskflow_app_dev php artisan sweetcrm:sync-clients admin password123 --limit=1000
```

#### Salida del Comando:

```
🔄 Sincronizando clientes desde SugarCRM...

1️⃣  Autenticando con SugarCRM...
   ✅ Autenticación exitosa

2️⃣  Obteniendo clientes de SugarCRM...
   ✅ 45 clientes obtenidos

3️⃣  Sincronizando clientes...
 45/45 [============================] 100%

📊 Resumen de sincronización:
┌──────────────────┬──────────┐
│ Métrica          │ Cantidad │
├──────────────────┼──────────┤
│ Total procesados │ 45       │
│ Sincronizados    │ 45       │
│ Creados          │ 12       │
│ Actualizados     │ 33       │
│ Errores          │ 0        │
└──────────────────┴──────────┘

✅ Sincronización completada
```

---

### 2. 🌐 API REST Endpoint

Para integración programática desde el frontend u otras aplicaciones.

#### Endpoint:

```
POST /api/v1/sweetcrm/sync-clients
```

#### Headers:

```
Authorization: Bearer {tu_token_taskflow}
Content-Type: application/json
```

#### Request Body:

```json
{
  "username": "tu_usuario_sugarcrm",
  "password": "tu_password_sugarcrm",
  "filters": {
    "max_results": 100,
    "query": "",
    "order_by": ""
  }
}
```

#### Parámetros del Request:

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `username` | string | ✅ Sí | Usuario de SugarCRM |
| `password` | string | ✅ Sí | Contraseña de SugarCRM |
| `filters` | object | ❌ No | Filtros opcionales |
| `filters.max_results` | number | ❌ No | Máximo de registros (default: 100) |
| `filters.query` | string | ❌ No | Consulta SQL WHERE para filtrar |
| `filters.order_by` | string | ❌ No | Orden de resultados |

#### Response (200 OK):

```json
{
  "message": "Sincronización completada",
  "total": 45,
  "synced": 45,
  "created": 12,
  "updated": 33,
  "errors": []
}
```

#### Ejemplo con cURL:

```bash
curl -X POST http://localhost:8080/api/v1/sweetcrm/sync-clients \
  -H "Authorization: Bearer tu_token_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "password123",
    "filters": {
      "max_results": 50
    }
  }'
```

#### Ejemplo con JavaScript/Fetch:

```javascript
const syncClients = async () => {
  const response = await fetch('http://localhost:8080/api/v1/sweetcrm/sync-clients', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${yourToken}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      username: 'admin',
      password: 'password123',
      filters: {
        max_results: 100
      }
    })
  });

  const result = await response.json();
  console.log(result);
};
```

---

## 📊 Datos Sincronizados

Los siguientes campos se sincronizan desde SugarCRM (Accounts) a Taskflow (Clients):

| Campo Taskflow | Campo SugarCRM | Descripción |
|----------------|----------------|-------------|
| `name` | `name` | Nombre del cliente |
| `industry_id` | `industry` | Industria (se crea automáticamente si no existe) |
| `contact_email` | `email1` | Email principal |
| `contact_phone` | `phone_office` | Teléfono de oficina |
| `address` | `billing_address_*` | Dirección (calle, ciudad, país) |
| `notes` | `description` | Descripción/Notas |
| `sweetcrm_id` | `id` | ID de SugarCRM (para tracking) |
| `sweetcrm_synced_at` | - | Timestamp de última sincronización |

---

## 🔄 Lógica de Sincronización

### Creación vs Actualización

- **Si el cliente NO existe** en Taskflow (no hay `sweetcrm_id` coincidente):
  - Se **crea** un nuevo registro
  - Contador `created` se incrementa

- **Si el cliente YA existe** en Taskflow (hay `sweetcrm_id` coincidente):
  - Se **actualiza** el registro existente
  - Contador `updated` se incrementa
  - Se preserva el `id` de Taskflow

### Industrias

- Si la industria no existe en Taskflow, **se crea automáticamente**
- Se asigna al cliente mediante `industry_id`

---

## ⏱️ Sincronización Automática (Opcional)

Puedes configurar un cron job para sincronizar automáticamente cada cierto tiempo.

### Agregar a Crontab:

```bash
# Sincronizar cada 6 horas
0 */6 * * * cd /path/to/taskflow && docker exec taskflow_app_dev php artisan sweetcrm:sync-clients admin password123 --limit=200 >> /var/log/sugarcrm-sync.log 2>&1
```

### Usar Laravel Scheduler:

Agregar en `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sincronizar cada 6 horas
    $schedule->command('sweetcrm:sync-clients', [
        config('services.sweetcrm.username'),
        config('services.sweetcrm.password'),
        '--limit' => 200
    ])->everySixHours();
}
```

---

## 🛡️ Seguridad

### ⚠️ Importante:

- **NUNCA** expongas las credenciales de SugarCRM en el frontend
- El endpoint de API requiere autenticación de Taskflow
- Las credenciales de SugarCRM se envían en el body del POST (HTTPS requerido en producción)
- Considera usar variables de entorno para credenciales en cron jobs

### Recomendaciones:

1. Usa HTTPS en producción
2. Crea un usuario específico en SugarCRM solo para sincronización
3. Limita permisos del usuario de sincronización (solo lectura en Accounts)
4. Monitorea los logs de sincronización

---

## 🐛 Troubleshooting

### Error: "Error de autenticación con SugarCRM"

**Causa:** Credenciales incorrectas o usuario bloqueado

**Solución:**
1. Verifica username y password
2. Intenta loguearte manualmente en SugarCRM
3. Revisa logs: `storage/logs/laravel.log`

### Error: "No se recibió session ID"

**Causa:** Respuesta inesperada de SugarCRM

**Solución:**
1. Verifica que la URL de SugarCRM sea correcta
2. Ejecuta diagnóstico: `php artisan sweetcrm:diagnose`
3. Revisa que SugarCRM API v4_1 esté disponible

### Sincronización lenta

**Causa:** Muchos registros o conexión lenta

**Solución:**
1. Reduce el `--limit` para procesar en lotes más pequeños
2. Ejecuta la sincronización en horarios de bajo tráfico
3. Considera usar jobs de Laravel Queue

### Clientes duplicados

**Causa:** El `sweetcrm_id` no coincide

**Solución:**
1. Verifica que el campo `sweetcrm_id` esté correctamente indexado
2. Revisa si hay clientes con `sweetcrm_id` NULL
3. Ejecuta: `SELECT * FROM clients WHERE sweetcrm_id IS NULL`

---

## 📝 Logs

Los logs de sincronización se encuentran en:

```
taskflow-backend/storage/logs/laravel.log
```

Buscar entradas con:
```bash
docker exec taskflow_app_dev grep "SugarCRM" storage/logs/laravel.log
```

---

## 🧪 Prueba de Sincronización

Para probar la sincronización sin afectar datos reales:

1. Usa un entorno de desarrollo/staging
2. Limita el número de registros: `--limit=5`
3. Verifica los datos en la base de datos después
4. Revisa que las industrias se hayan creado correctamente

```bash
# Prueba con solo 5 clientes
docker exec taskflow_app_dev php artisan sweetcrm:sync-clients test_user test_pass --limit=5

# Verificar en la base de datos
docker exec taskflow_mariadb_dev mysql -u taskflow -ppassword -D taskflow_dev -e "SELECT id, name, industry_id, sweetcrm_id FROM clients ORDER BY id DESC LIMIT 5;"
```

---

## 📞 Soporte

Para problemas o preguntas:
- Revisar logs de Laravel
- Ejecutar comando de diagnóstico
- Consultar documentación de SugarCRM API v4_1

---

**Última actualización:** 2025-12-23
**Versión:** 1.0.0
