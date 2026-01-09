# 🚀 GUÍA COMPLETA DE DESPLIEGUE - SISTEMA DE SOLICITUD DE CIERRE

**Autor:** Claude Code
**Fecha:** 8 de enero 2026
**Ambiente:** Docker Compose (Producción)
**Estado:** LISTA PARA DESPLIEGUE

---

## 📋 RESUMEN EJECUTIVO

Este documento te guía para desplegar la **FASE 3 (Sistema de Solicitud de Cierre de Casos)** en tu VPS con Docker Compose. El despliegue es seguro, reversible y automatizado.

**Tiempo estimado:** 20-30 minutos
**Riesgo:** BAJO (Docker + Rollback disponible)
**Disponibilidad:** Zero-downtime (con plan de monitoreo)

---

## 🏗️ ARQUITECTURA DE DESPLIEGUE

### Composición Docker (docker-compose.prod.yml)
```
┌─────────────────────────────────────────┐
│         NGINX Gateway (Port 80/443)     │
├─────────────────────────────────────────┤
│  Frontend (Vue.js)  │  Backend (Laravel)│
├─────────────────────────────────────────┤
│  MariaDB  │  Redis  │  Queue  │ Soketi │
└─────────────────────────────────────────┘
```

### Servicios a Desplegar
1. **Frontend** - Vue.js compilado + Nginx interno
2. **Backend** - PHP-FPM + Laravel (con FASE 3)
3. **Queue** - Laravel Queue Worker (jobs async)
4. **Database** - MariaDB 10.11
5. **Cache** - Redis
6. **WebSockets** - Soketi
7. **Gateway** - Nginx (proxy reverso)

---

## ✅ PRE-REQUISITOS

### En tu VPS
- [x] Docker instalado (`docker --version`)
- [x] Docker Compose instalado (`docker-compose --version`)
- [x] Git instalado y clonado el repositorio
- [x] ~5GB de espacio disponible
- [x] Puertos 80 y 443 disponibles
- [x] Usuario con permisos de docker

### En tu máquina local
- [x] Acceso SSH a VPS
- [x] Credenciales de BD listos
- [x] Configuración de dominio DNS lista (opcional)

---

## 🔧 PASO 1: PREPARAR TU VPS

### 1.1 Conectar al VPS
```bash
ssh usuario@tu_vps_ip
```

### 1.2 Navegar al proyecto
```bash
cd /ruta/a/Taskflow-Icontel
pwd  # Verificar ubicación correcta
```

### 1.3 Verificar estado actual
```bash
# Ver si hay contenedores corriendo
docker-compose ps

# Ver si hay cambios pendientes en git
git status

# Ver el último commit
git log -1 --oneline
```

**Debería mostrar:** `FASE 3: Limpieza de código legacy y documentación completa`

---

## 📥 PASO 2: ACTUALIZAR CÓDIGO

### 2.1 Pull de cambios
```bash
# En el directorio raíz de Taskflow-Icontel
git pull origin main

# Verificar que se descargaron los cambios
git log -1 --oneline
# Debe mostrar el commit de FASE 3
```

### 2.2 Verificar cambios descargados
```bash
# Ver qué cambió en backend
git diff HEAD~1..HEAD taskflow-backend/ --stat | head -20

# Ver qué cambió en frontend
git diff HEAD~1..HEAD taskflow-frontend/ --stat | head -20
```

---

## 🔐 PASO 3: CONFIGURAR VARIABLES DE ENTORNO

### 3.1 Revisar .env.docker actual
```bash
cd taskflow-backend
nano .env.docker
```

**Verificar estos valores (CRÍTICOS):**
```
APP_ENV=production          ✅
APP_DEBUG=false             ✅
DB_HOST=db                  ✅ (Docker)
REDIS_HOST=redis            ✅ (Docker)
SWEETCRM_ENABLED=true       ✅
```

### 3.2 Actualizar valores sensibles si es necesario
```bash
# Si necesitas cambiar credenciales:
# APP_KEY=base64:... (no cambiar si funciona)
# DB_PASSWORD=...
# DB_ROOT_PASSWORD=...
```

**⚠️ IMPORTANTE:** No subas credenciales a Git. Usa variables de entorno del VPS.

### 3.3 Verificar .env.production (Frontend)
```bash
cd ../taskflow-frontend
nano .env.production
```

**Completar estos valores:**
```bash
VITE_API_BASE_URL=/api/v1           # ✅ Correcto
VITE_PUSHER_HOST=TU_VPS_IP_O_DOMINIO  # ⚠️ Cambiar
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http              # Cambiar a 'https' si tienes SSL
```

---

## 🐳 PASO 4: DESPLIEGUE DOCKER

### 4.1 Detener contenedores antiguos (si existen)
```bash
cd /ruta/a/Taskflow-Icontel

# Ver contenedores corriendo
docker-compose ps

# Detener todos
docker-compose down

# Verificar que se detuvieron
docker-compose ps
```

### 4.2 Compilar imágenes nuevas
```bash
# OPCIÓN A: Con caché (más rápido, recomendado para updates)
docker-compose -f docker-compose.prod.yml build

# OPCIÓN B: Sin caché (más lento, cuando hay problemas)
docker-compose -f docker-compose.prod.yml build --no-cache
```

**Esto toma 3-5 minutos.** Puedes ir a tomar café ☕

### 4.3 Iniciar servicios
```bash
# Iniciar todos los servicios en background
docker-compose -f docker-compose.prod.yml up -d

# Verificar que iniciaron
docker-compose ps
```

**Deberías ver:**
```
taskflow_frontend    ✓ Up
taskflow_backend     ✓ Up
taskflow_queue       ✓ Up
taskflow_gateway     ✓ Up
taskflow_db          ✓ Up
taskflow_redis       ✓ Up
taskflow_soketi      ✓ Up
```

### 4.4 Esperar a que MariaDB esté listo
```bash
# La BD necesita 15-30 segundos para inicializar
sleep 30

# Verificar que está lista
docker-compose logs db | tail -10
# Debe mostrar: "Server socket created on IP: '0.0.0.0'..."
```

---

## 🔄 PASO 5: MIGRACIONES Y SEEDING

### 5.1 Ejecutar migraciones
```bash
# Ejecutar todas las migraciones (incluyendo FASE 3)
docker-compose exec backend php artisan migrate --force

# Ver migraciones ejecutadas
docker-compose exec backend php artisan migrate:status | tail -20
```

**Deberías ver las migraciones de FASE 3:**
```
✓ create_case_closure_requests_table
✓ add_closure_fields_to_crm_cases_table
```

### 5.2 Limpiar caché
```bash
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan view:clear
```

### 5.3 Optimizar para producción
```bash
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
docker-compose exec backend php artisan view:cache
```

---

## ✨ PASO 6: COMPILAR FRONTEND

### 6.1 Construir assets de Vue.js
```bash
cd taskflow-frontend

# El Dockerfile ya hace npm run build durante la construcción
# Pero si necesitas rebuildear:
docker-compose exec frontend npm run build
```

### 6.2 Verificar que Frontend está sirviendo
```bash
# Acceder al frontend
curl http://localhost/

# Debería retornar HTML del Vue.js
```

---

## 🧪 PASO 7: VERIFICACIÓN INICIAL

### 7.1 Verificar que los servicios responden
```bash
# Frontend
curl http://localhost/
# Debe retornar HTML (no error)

# API Backend
curl http://localhost/api/v1/health
# Debe retornar JSON (si existe endpoint)

# O prueba un endpoint real:
curl http://localhost/api/v1/cases
# Debe retornar JSON (probablemente necesite autenticación)
```

### 7.2 Verificar logs
```bash
# Ver logs de Nginx Gateway
docker-compose logs gateway | tail -20

# Ver logs del Backend
docker-compose logs backend | tail -20

# Ver logs de MariaDB
docker-compose logs db | tail -10

# Ver logs de Redis
docker-compose logs redis | tail -10
```

**Buscar errores:** ❌ ERROR, ❌ Exception, ❌ CRITICAL

### 7.3 Verificar que FASE 3 está cargada
```bash
# Verificar que existen las tablas nuevas
docker-compose exec db mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev -e "SHOW TABLES LIKE 'case_closure%';"

# Debería mostrar: case_closure_requests

# Verificar que existen los campos nuevos en crm_cases
docker-compose exec db mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev -e "DESC crm_cases;" | grep closure
```

---

## 🧩 PASO 8: TESTING DE FASE 3

### 8.1 Crear usuario de prueba
```bash
# Acceder a shell de Laravel
docker-compose exec backend php artisan tinker

# Dentro de tinker, crear usuarios:
$admin = User::create([
  'name' => 'Admin SAC',
  'email' => 'admin@taskflow.local',
  'password' => bcrypt('password'),
  'role' => 'admin',
  'department' => 'SAC'
]);

$user = User::create([
  'name' => 'Usuario Regular',
  'email' => 'user@taskflow.local',
  'password' => bcrypt('password'),
  'role' => 'user',
  'department' => 'Operations'
]);

# Verificar creación
User::count()
# Salir: Ctrl+D
```

### 8.2 Test manual (sin UI todavía)
```bash
# Obtener token de acceso
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@taskflow.local",
    "password": "password"
  }'

# Guardar el token del response en una variable
TOKEN="eyJ..."

# Crear un caso
curl -X POST http://localhost/api/v1/cases \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "case_number": "CASE-TEST-001",
    "subject": "Test Case",
    "status": "Open"
  }'

# Intentar solicitar cierre (debe funcionar)
curl -X POST http://localhost/api/v1/cases/1/request-closure \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Test closure request",
    "completion_percentage": 100
  }'

# Verificar respuesta: Debería ser 201 Created con status 'pending'
```

### 8.3 Verificar que endpoints legacy retornan 410
```bash
# Este endpoint debería retornar 410 Gone
curl -X POST http://localhost/api/v1/cases/1/approve-closure \
  -H "Authorization: Bearer $TOKEN"

# Respuesta esperada: 410 Gone con mensaje de deprecación
```

---

## 📊 PASO 9: MONITOREO POST-DESPLIEGUE

### 9.1 Configurar monitoreo en tiempo real
```bash
# Terminal 1: Ver logs del backend
docker-compose logs -f backend | grep -E "DEPRECATED|ERROR|closure|Created|updated"

# Terminal 2: Ver logs de nginx
docker-compose logs -f gateway | grep -E "POST|GET|ERROR"

# Terminal 3: Ver logs de BD
docker-compose logs -f db | grep -E "ERROR|exception"
```

### 9.2 Verificar que no hay errores críticos
```bash
# Buscar errores en logs
docker-compose logs backend | grep -i error
docker-compose logs gateway | grep -i "5[0-9]{2}"  # HTTP 5xx errors
docker-compose logs db | grep -i error

# Deberían estar vacíos o con muy pocos errores
```

### 9.3 Verificar recursos del sistema
```bash
# Uso de CPU y memoria
docker stats

# Deberían estar por debajo de:
# - Backend: <30% CPU, <500MB RAM
# - Frontend: <20% CPU, <200MB RAM
# - MariaDB: <40% CPU, <1GB RAM
```

### 9.4 Verificar conectividad
```bash
# Test de API
curl -i http://localhost/api/v1/cases
# Debería retornar 200 o 401 (no 500 o 503)

# Test de WebSockets (Soketi)
curl -i http://localhost:6001/
# Debería responder
```

---

## 🔄 PASO 10: CONFIGURACIÓN POST-DESPLIEGUE

### 10.1 SSL/HTTPS (Opcional pero Recomendado)
```bash
# 1. Obtener certificado Let's Encrypt
sudo certbot certonly --standalone -d tu_dominio.com

# 2. Copiar certificados
sudo cp /etc/letsencrypt/live/tu_dominio.com/fullchain.pem taskflow-backend/nginx/ssl/
sudo cp /etc/letsencrypt/live/tu_dominio.com/privkey.pem taskflow-backend/nginx/ssl/

# 3. Actualizar nginx gateway para usar HTTPS
nano nginx-gateway/conf.d/default.conf
# Descomentar línea de 443 en docker-compose.prod.yml

# 4. Reiniciar
docker-compose restart gateway
```

### 10.2 Firewall
```bash
# Abrir puertos
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp   # SSH

# Verificar
sudo ufw status
```

### 10.3 Backups automáticos
```bash
# Script para backup diario de BD
cat > /home/usuario/backup-db.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y-%m-%d_%H-%M-%S)
docker-compose exec -T db mysqldump -u root -p${DB_ROOT_PASSWORD} taskflow_dev > /backups/taskflow_$DATE.sql
EOF

chmod +x /home/usuario/backup-db.sh

# Agregar a crontab
crontab -e
# Agregar línea: 0 2 * * * /home/usuario/backup-db.sh
```

---

## 🆘 TROUBLESHOOTING

### Problema 1: MariaDB no inicia
```bash
# Symptoma: docker-compose ps muestra "Exited"

# Solución 1: Revisar logs
docker-compose logs db

# Solución 2: Limpiar volumen (⚠️ borra datos)
docker-compose down -v
docker-compose up -d db
sleep 30
```

### Problema 2: Backend devuelve 502 Bad Gateway
```bash
# Symptoma: curl devuelve "502 Bad Gateway"

# Causa: Backend no está respondiendo

# Solución:
docker-compose logs backend | tail -50
# Buscar errores de Laravel

docker-compose restart backend
sleep 10
```

### Problema 3: Frontend muestra página en blanco
```bash
# Symptoma: Abre pero no carga nada

# Solución 1: Revisar logs
docker-compose logs frontend

# Solución 2: Verificar .env.production
docker-compose exec frontend cat /app/.env.production

# Solución 3: Rebuild frontend
docker-compose down frontend
docker-compose -f docker-compose.prod.yml build frontend
docker-compose up -d frontend
```

### Problema 4: FASE 3 - Endpoints retornan 404
```bash
# Symptoma: POST /api/v1/cases/1/request-closure retorna 404

# Causa: Rutas no están registradas

# Solución:
docker-compose exec backend php artisan route:list | grep closure

# Si no aparecen, verificar:
docker-compose exec backend php artisan route:cache
docker-compose restart backend
```

### Problema 5: Migraciones no se ejecutan
```bash
# Symptoma: Tablas de case_closure_requests no existen

# Solución:
docker-compose exec backend php artisan migrate:reset --force
docker-compose exec backend php artisan migrate --force
docker-compose exec backend php artisan migrate:status

# Verificar tabla
docker-compose exec db mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev -e "SHOW TABLES LIKE 'case_closure%';"
```

---

## 📋 CHECKLIST DE DESPLIEGUE

### Pre-Despliegue
- [ ] Git pull completado
- [ ] .env.docker revisado y completo
- [ ] .env.production configurado con IP/dominio
- [ ] Espacio en disco verificado (~5GB disponibles)
- [ ] Puertos 80/443 disponibles
- [ ] SSH acceso confirmado

### Despliegue
- [ ] Contenedores antiguos detenidos
- [ ] Imágenes compiladas sin errores
- [ ] Servicios iniciados (docker-compose ps OK)
- [ ] MariaDB responde (30 segundos esperados)
- [ ] Migraciones ejecutadas sin errores
- [ ] Frontend compilado
- [ ] Caché limpiado y optimizado

### Post-Despliegue (Primeras 2 horas)
- [ ] API responde en http://localhost/api/v1/cases
- [ ] Frontend carga sin errores
- [ ] No hay errores 500 en logs
- [ ] Endpoints FASE 3 funcionan (request-closure)
- [ ] Endpoints legacy retornan 410 Gone
- [ ] Database tiene tablas de case_closure_requests
- [ ] Queue worker está activo
- [ ] Redis está conectado
- [ ] WebSockets (Soketi) funciona

### Monitoreo Continuo (24 horas)
- [ ] Logs monitoreados cada hora
- [ ] Usuarios reportan funcionamiento normal
- [ ] Sin errores críticos
- [ ] Rendimiento dentro de parámetros
- [ ] Backups configurados

---

## 🔄 ROLLBACK (Si algo falla)

### Rollback Rápido (5 minutos)
```bash
# 1. Detener servicios actuales
docker-compose down

# 2. Revertir código al commit anterior
git revert HEAD
# O directamente al anterior:
git reset --hard HEAD~1

# 3. Revertir migraciones (CUIDADO)
docker-compose up -d db
sleep 30
docker-compose exec backend php artisan migrate:rollback

# 4. Reiniciar servicios
docker-compose -f docker-compose.prod.yml up -d
```

### Rollback Completo (15 minutos)
```bash
# 1. Detener todo
docker-compose down -v

# 2. Revertir código
git reset --hard HEAD~1

# 3. Restaurar backup de BD (si existe)
cat /backups/taskflow_backup.sql | docker-compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD}

# 4. Recompilar y reiniciar
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up -d
```

---

## 📞 CONTACTO Y SOPORTE

### Documentación de Referencia
- **API Migration:** `API_MIGRATION_GUIDE.md`
- **Changelog:** `CHANGELOG_CLOSURE_SYSTEM.md`
- **Resumen:** `IMPLEMENTACION_RESUMEN.md`
- **Deployment:** Este archivo (DEPLOYMENT_GUIDE.md)

### Comandos Útiles Post-Despliegue
```bash
# Ver estado de servicios
docker-compose ps

# Ver logs en tiempo real
docker-compose logs -f backend

# Acceder a shell del backend
docker-compose exec backend sh

# Ejecutar tinker (REPL de Laravel)
docker-compose exec backend php artisan tinker

# Ejecutar tests
docker-compose exec backend php artisan test

# Ver uso de recursos
docker stats

# Hacer backup manual
docker-compose exec -T db mysqldump -u root -p${DB_ROOT_PASSWORD} taskflow_dev > /backups/manual_$(date +%s).sql

# Ver estadísticas de BD
docker-compose exec db mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev -e "SELECT COUNT(*) as total_cases FROM crm_cases;"
```

---

## 🎉 RESUMEN

| Paso | Acción | Tiempo |
|------|--------|--------|
| 1 | Preparar VPS | 2 min |
| 2 | Git pull | 1 min |
| 3 | Configurar .env | 3 min |
| 4 | Docker build + up | 8 min |
| 5 | Migraciones | 2 min |
| 6 | Frontend | 2 min |
| 7 | Verificación | 3 min |
| 8 | Testing FASE 3 | 3 min |
| 9 | Monitoreo | Continuo |
| **Total** | | **~25-30 min** |

---

## ✅ ESTADO FINAL

Después de completar todos los pasos, tu VPS tendrá:

✅ **Sistema en Producción:**
- Frontend Vue.js corriendo en puerto 80
- Backend Laravel con FASE 3 corriendo
- MariaDB con datos sincronizados
- Redis para caché y sesiones
- Queue Worker para jobs async
- WebSockets (Soketi) para tiempo real
- Nginx Gateway como proxy reverso

✅ **FASE 3 Completamente Funcional:**
- Nuevos endpoints `/closure-requests/*`
- Métodos de autorización en User model
- Policy de permisos implementada
- Tests incluidos en código

✅ **Monitoreo y Rollback:**
- Logs disponibles
- Rollback disponible en 5-15 minutos
- Backups automáticos configurables

---

**¿Listo para desplegar? 🚀 ¡Adelante!**

Si encuentras algún problema, revisa la sección TROUBLESHOOTING o consulta los documentos de referencia.

