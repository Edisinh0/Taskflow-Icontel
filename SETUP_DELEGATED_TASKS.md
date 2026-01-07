# Guía de Setup: Tareas Delegadas en Docker

## Problema Identificado
Tu aplicación está corriendo en **Docker** con **MariaDB** como base de datos. Los comandos de migración deben ejecutarse dentro del contenedor de la aplicación.

## Solución Paso a Paso

### Paso 1: Verificar que los Contenedores están Corriendo

Primero, asegúrate que Docker está activo y tus contenedores están ejecutándose:

```bash
# Ve al directorio del backend
cd /Users/eddiecerpa/Taskflow-Icontel/taskflow-backend

# Verifica los contenedores en ejecución
docker-compose ps
```

**Resultado esperado:**
```
NAME               COMMAND                  STATUS
taskflow_app       "docker-php-entryp..."   Up
taskflow_mariadb   "docker-entrypoint..."   Up
taskflow_nginx     "nginx -g daemon of..."  Up
taskflow_redis     "redis-server"           Up
```

Si alguno no está **Up**, inicia los contenedores:

```bash
docker-compose up -d
```

### Paso 2: Ejecutar la Migración

Ejecuta la migración dentro del contenedor de la aplicación:

```bash
docker-compose exec app php artisan migrate --step
```

**Resultado esperado:**
```
 Migrating: 2026_01_07_add_created_by_to_tasks_table

 Migration table created successfully.
 Migrating: 2026_01_07_add_created_by_to_tasks_table
 Migrated:  2026_01_07_add_created_by_to_tasks_table (XXms)
```

### Paso 3: Verificar la Migración

Verifica que la migración se ejecutó correctamente:

```bash
docker-compose exec app php artisan migrate:status
```

Busca la migración `2026_01_07_add_created_by_to_tasks_table` y asegúrate que dice **Ran**.

### Paso 4: Ejecutar la Sincronización de SweetCRM

Ahora sincroniza las tareas desde SweetCRM:

```bash
docker-compose exec app php artisan sweetcrm:sync-cases
```

**Resultado esperado:**
```
🔄 Sincronizando Casos desde SweetCRM...
   📊 Total casos sincronizados: XX
   🔄 Sincronizando Tareas...
   📊 Total tareas sincronizadas: XX
   ✅ Sincronización completada en XX segundos
```

### Paso 5: Verificar en la Base de Datos

Verifica que el campo `created_by` está poblado:

```bash
docker-compose exec mariadb mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev -e "SELECT id, title, created_by, assignee_id FROM tasks LIMIT 5;"
```

**Resultado esperado:**
```
+----+------------------+------------+-------------+
| id | title            | created_by | assignee_id |
+----+------------------+------------+-------------+
|  1 | Tarea de CRM     |          2 |           3 |
|  2 | Otra tarea       |          1 |           2 |
|  3 | Más tareas       |          2 |           3 |
+----+------------------+------------+-------------+
```

Si `created_by` está vacío (NULL), significa que el usuario creador no está sincronizado o no existe en la tabla `users`.

### Paso 6: Verificar en el Dashboard

1. Abre la aplicación en tu navegador: http://localhost:8080
2. Inicia sesión con un usuario que haya delegado tareas
3. Ve al Dashboard
4. Busca la sección **"Tareas y Casos Delegados"** al final
5. Deberías ver todas las tareas que has delegado

## Troubleshooting

### Error: "No such service: app"

**Causa**: Los contenedores no están inicializados

**Solución**:
```bash
docker-compose up -d
```

### Error: "Access denied for user"

**Causa**: Las credenciales de BD están incorrectas en el docker-compose

**Solución**: Revisa el archivo `docker-compose.yml` y asegúrate que las variables de BD coinciden

```bash
# Verifica las credenciales en el archivo
cat docker-compose.yml | grep -A 5 "mariadb:"
```

### Las tareas no aparecen en el dashboard

**Verificación 1**: Confirma que la migración se ejecutó:
```bash
docker-compose exec app php artisan migrate:status | grep "created_by"
```

**Verificación 2**: Confirma que los datos fueron sincronizados:
```bash
docker-compose exec mariadb mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev -e "SELECT COUNT(*) FROM tasks WHERE created_by IS NOT NULL;"
```

**Verificación 3**: Revisa que el usuario creador existe:
```bash
docker-compose exec mariadb mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev -e "SELECT id, sweetcrm_id, name FROM users WHERE id = 2;" # Cambia el ID según sea necesario
```

### Ver los logs en tiempo real

Si algo no funciona, revisa los logs:

```bash
# Logs de la aplicación
docker-compose logs -f app

# Logs de la BD
docker-compose logs -f mariadb

# Logs de Nginx
docker-compose logs -f nginx
```

Presiona `Ctrl+C` para salir de los logs.

## Comandos Útiles

```bash
# Acceder a la consola PHP dentro del contenedor
docker-compose exec app bash

# Ver todas las migraciones disponibles
docker-compose exec app php artisan migrate:status

# Ejecutar seed si es necesario
docker-compose exec app php artisan db:seed

# Limpiar cache de Laravel
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Acceder a la BD con CLI
docker-compose exec mariadb mysql -u root -p${DB_ROOT_PASSWORD} taskflow_dev

# Ver logs de todo el sistema
docker-compose logs -f

# Reiniciar un contenedor
docker-compose restart app

# Detener todos los contenedores
docker-compose down

# Iniciar de nuevo
docker-compose up -d
```

## Variables de Entorno Importantes

En el `docker-compose.yml`, la configuración de BD es:

```yaml
environment:
  - DB_HOST=mariadb          # El nombre del servicio
  - DB_DATABASE=${DB_DATABASE}
  - DB_USERNAME=${DB_USERNAME}
  - DB_PASSWORD=${DB_PASSWORD}
```

Estas se obtienen del archivo `.env` en el directorio del backend.

## Próximos Pasos

Una vez que la migración y sincronización estén completas:

1. ✅ Los usuarios verán sus tareas delegadas en el dashboard
2. ✅ Las estadísticas de tareas delegadas se calcularán correctamente
3. ✅ Podrán hacer clic en cualquier tarea para ver más detalles

## Soporte

Si tienes problemas específicos de Docker:

1. Verifica que Docker Desktop está corriendo
2. Ejecuta `docker system prune` para limpiar espacios
3. Intenta `docker-compose down && docker-compose up -d --build`
4. Revisa los logs con `docker-compose logs -f`

Para problemas de BD:
1. Verifica credenciales en `.env` y `docker-compose.yml`
2. Asegúrate que MariaDB está inicializado completamente (espera ~30 segundos después de `up`)
3. Revisa que el puerto 3306 no está en uso por otra instancia

## Resumen Rápido

Si solo quieres ejecutar los comandos sin leer todo:

```bash
cd /Users/eddiecerpa/Taskflow-Icontel/taskflow-backend

# 1. Asegúrate que Docker está corriendo
docker-compose up -d

# 2. Ejecuta la migración
docker-compose exec app php artisan migrate --step

# 3. Sincroniza las tareas
docker-compose exec app php artisan sweetcrm:sync-cases

# 4. ¡Listo! Ve a tu dashboard
# http://localhost:8080
```
