# Guía de Diagnóstico - Problema de Login Local

**Problema**: No puedo ingresar con credenciales y no veo los logs del backend
**Ambiente**: Local (Docker)
**Fecha**: 2026-01-08

---

## 🔍 Paso 1: Verificar que Docker Está Ejecutándose Correctamente

### 1.1 Ver estado de contenedores
```bash
# Ver todos los contenedores
docker-compose ps

# Resultado esperado:
# NAME                COMMAND             STATUS              PORTS
# taskflow-backend    "php-fpm"          Up                  9000/tcp
# taskflow-frontend   "npm run dev"      Up                  5173/tcp
# taskflow-db         "mysql"            Up                  3306/tcp
# taskflow-redis      "redis"            Up                  6379/tcp
```

### 1.2 Si algún contenedor está parado
```bash
# Reiniciar todos los contenedores
docker-compose down
docker-compose up -d

# Esperar 30 segundos a que se estabilicen
sleep 30
docker-compose ps
```

---

## 🗂️ Paso 2: Acceder a los Logs del Backend

### Opción A: Logs en Tiempo Real (Mejor)
```bash
# Ver logs en vivo del backend
docker-compose logs -f backend

# O especificar número de líneas
docker-compose logs --tail=100 backend

# Ctrl+C para detener
```

### Opción B: Ver archivo de logs en el contenedor
```bash
# Acceder al shell del contenedor
docker-compose exec backend bash

# Una vez dentro del contenedor:
cd /var/www/html

# Ver logs
ls -la storage/logs/
cat storage/logs/laravel.log | tail -100

# Ver si hay errores recientes
cat storage/logs/laravel.log | grep -i error | tail -20

# Salir del contenedor
exit
```

### Opción C: Copiar logs a máquina local
```bash
# Copiar archivo de logs
docker-compose exec backend cat storage/logs/laravel.log > ~/laravel-logs.txt

# Ver en tu máquina
cat ~/laravel-logs.txt | tail -100
```

---

## 🔐 Paso 3: Diagnosticar Problema de Login

### 3.1 Verificar Credenciales en Base de Datos
```bash
# Acceder a MySQL
docker-compose exec db mysql -u root -p

# En MySQL, ejecutar:
use taskflow_dev;
SELECT id, name, email, password FROM users LIMIT 5;

# Ver si existen usuarios
SELECT COUNT(*) as total_users FROM users;

# Salir
exit
```

### 3.2 Verificar Tabla de Usuarios
```bash
# Dentro de MySQL:
DESCRIBE users;

# Debe tener al menos estas columnas:
# id, name, email, password, created_at, updated_at
```

### 3.3 Verificar Hash de Contraseña
```bash
# Las contraseñas deben estar hasheadas (no en texto plano)
# Ejemplo correcto:
# $2y$10$gSvqqUNYYE47qKjNNdF2QuYP7Oi5cMhCJdVnYwVlwJV...

# Si ves contraseñas en texto plano, la contraseña no se hasheó correctamente
```

---

## 🔧 Paso 4: Problemas Comunes y Soluciones

### Problema: No Hay Usuarios en la Base de Datos
```bash
# Ejecutar seeding de usuarios
docker-compose exec backend php artisan db:seed --class=UserSeeder

# O regenerar la BD desde 0
docker-compose exec backend php artisan migrate:fresh --seed
```

### Problema: Archivo de Log No Existe
```bash
# Crear directorio de logs
docker-compose exec backend mkdir -p storage/logs

# Crear archivo de log
docker-compose exec backend touch storage/logs/laravel.log

# Dar permisos
docker-compose exec backend chmod 755 storage
docker-compose exec backend chmod 755 storage/logs
docker-compose exec backend chmod 644 storage/logs/laravel.log
```

### Problema: Permisos Incorrectos
```bash
# Dar permisos correctos
docker-compose exec backend chmod -R 755 storage
docker-compose exec backend chmod -R 755 bootstrap/cache

# Cambiar propietario
docker-compose exec backend chown -R www-data:www-data storage
docker-compose exec backend chown -R www-data:www-data bootstrap/cache
```

### Problema: .env.docker No Configurado Correctamente
```bash
# Verificar .env.docker
cat taskflow-backend/.env.docker

# Debe tener:
APP_ENV=local
APP_DEBUG=true
DB_HOST=db
DB_DATABASE=taskflow_dev
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### Problema: Caché de Configuración Vieja
```bash
# Limpiar caché
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan view:clear

# Regenerar caché
docker-compose exec backend php artisan config:cache
```

---

## 🔍 Paso 5: Verificar Configuración de Autenticación

### 5.1 Revisar archivo de auth
```bash
# Ver configuración de autenticación
docker-compose exec backend cat config/auth.php

# Debe tener guardián 'web' configurado para 'users'
```

### 5.2 Revisar modelo de User
```bash
# Ver modelo
cat taskflow-backend/app/Models/User.php

# Debe tener:
# use Laravel\Sanctum\HasApiTokens;
# use Authenticatable;
```

### 5.3 Verificar factory/seeder de usuarios
```bash
# Ver contenido
cat taskflow-backend/database/seeders/UserSeeder.php

# Debe crear usuarios con contraseñas hasheadas
# Ejemplo correcto:
# 'password' => Hash::make('password123')
```

---

## ✅ Paso 6: Diagnóstico Completo - Checklist

Ejecuta estos comandos en orden:

```bash
# 1. Verificar Docker
docker-compose ps

# 2. Verificar logs
docker-compose logs --tail=50 backend

# 3. Verificar BD
docker-compose exec db mysql -u root -ptaskflow_dev -e "SELECT COUNT(*) FROM taskflow_dev.users;"

# 4. Verificar archivo de logs existe
docker-compose exec backend ls -la storage/logs/laravel.log

# 5. Ver últimos errores
docker-compose exec backend tail -20 storage/logs/laravel.log

# 6. Limpiar caché y regenerar
docker-compose exec backend php artisan config:clear && \
docker-compose exec backend php artisan cache:clear && \
docker-compose exec backend php artisan config:cache

# 7. Reintentar login
# Abre http://localhost en tu navegador
```

---

## 🚀 Solución Rápida Completa

Si nada funciona, ejecuta esto para resetear todo:

```bash
# 1. Detener contenedores
docker-compose down

# 2. Limpiar volúmenes (¡CUIDADO: borra BD!)
docker-compose down -v

# 3. Reconstruir
docker-compose build --no-cache

# 4. Iniciar
docker-compose up -d

# 5. Esperar 30 segundos
sleep 30

# 6. Ejecutar migraciones y seeding
docker-compose exec -T backend php artisan migrate:fresh --seed

# 7. Verificar
docker-compose exec backend php artisan tinker
>>> User::all();
>>> exit
```

---

## 📞 Comandos Útiles de Referencia

```bash
# Ver logs en vivo
docker-compose logs -f backend

# Ver últimas N líneas
docker-compose logs --tail=100 backend

# Ver logs de un servicio específico
docker-compose logs db
docker-compose logs redis

# Acceder al contenedor del backend
docker-compose exec backend bash

# Ejecutar artisan dentro del contenedor
docker-compose exec backend php artisan tinker

# Ejecutar MySQL desde CLI
docker-compose exec db mysql -u root taskflow_dev

# Ver espacio usado
docker system df

# Limpiar imágenes no usadas
docker system prune

# Ver red de Docker
docker network ls
docker network inspect taskflow_network
```

---

## 💡 Credenciales por Defecto (Si Fueron Seeded)

Típicamente después de seeding:
- **Email**: admin@taskflow.local (o user@taskflow.local)
- **Contraseña**: password (o lo definido en UserSeeder.php)

Si no funcionan, verifica:
```bash
docker-compose exec backend php artisan tinker
>>> User::pluck('email')->all();
```

---

## 🆘 Si Aún No Funciona

Ejecuta este comando de diagnóstico completo:

```bash
#!/bin/bash

echo "=== DIAGNÓSTICO COMPLETO ==="
echo ""
echo "1. Estado de Docker:"
docker-compose ps
echo ""
echo "2. Últimos 20 logs de backend:"
docker-compose logs --tail=20 backend
echo ""
echo "3. Usuarios en BD:"
docker-compose exec db mysql -u root taskflow_dev -e "SELECT email FROM users LIMIT 5;"
echo ""
echo "4. Archivo de logs existe:"
docker-compose exec backend ls -la storage/logs/laravel.log
echo ""
echo "5. Contenido del .env:"
docker-compose exec backend cat .env.docker | grep -E "DB_|APP_"
echo ""
```

Guarda esto en un archivo `diagnostico.sh` y ejecuta:
```bash
chmod +x diagnostico.sh
./diagnostico.sh
```

---

**Próximo paso**: Proporciona los resultados de los comandos anteriores para que pueda darte la solución exacta a tu problema.
