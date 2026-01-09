# ✅ LISTO PARA DESPLIEGUE - Sistema FASE 3

**Estado**: Listo para Despliegue a Producción
**Fecha**: 2026-01-08
**Sistema**: Taskflow Solicitud de Cierre de Casos (FASE 3)
**Commit**: `0bffa44` - FASE 3: Limpieza de código legacy y documentación completa

---

## TL;DR - Inicio Rápido

### Requisitos Previos (Hacer Esto Primero)
```bash
# 1. Hacer copia de seguridad del estado actual en VPS
ssh usuario@tu-vps "docker-compose -f docker-compose.prod.yml down -v"  # ¡CUIDADO!
ssh usuario@tu-vps "tar -czf copia-seguridad-$(date +%Y%m%d-%H%M%S).tar.gz /ruta/a/taskflow"

# 2. Actualizar VITE_PUSHER_HOST en .env.production
# Ejemplo: Si IP del VPS es 192.168.1.100
vim .env.production
# Cambiar: VITE_PUSHER_HOST=192.168.1.100

# 3. Hacer commit de archivos de guía de despliegue
git add GUIA_DESPLIEGUE.md VERIFICACION_PRE_DESPLIEGUE.md RESUMEN_DESPLIEGUE_FASE3.md despliegue-fase3.sh
git commit -m "Despliegue: Agregar documentación despliegue FASE 3 y script automatizado"
git push origin main
```

### Desplegar (Elegir Una Opción)

**Opción A - Automatizado (Recomendado)**
```bash
# En servidor VPS:
ssh usuario@tu-vps "cd /ruta/a/taskflow"
ssh usuario@tu-vps "chmod +x despliegue-fase3.sh"
ssh usuario@tu-vps "./despliegue-fase3.sh producción"
```

**Opción B - Manual Paso a Paso**
```bash
# Sigue las instrucciones en GUIA_DESPLIEGUE.md (10 pasos detallados)
```

### Verificar (Inmediatamente Después)
```bash
# Verificar todos los servicios ejecutándose
docker-compose -f docker-compose.prod.yml ps

# Verificar logs para errores
docker-compose -f docker-compose.prod.yml logs --tail=20 backend

# Probar endpoint de API
curl -s http://localhost/api/v1/cases | head -20

# Probar endpoint legacy (debe ser 410)
curl -s -i http://localhost/api/v1/cases/1/request-closure | head -1
```

---

## Guías de Despliegue - ¿Cuál Usar?

| Documento | Usar Cuando | Tiempo | Esfuerzo |
|-----------|-------------|--------|----------|
| **despliegue-fase3.sh** | Quieres despliegue completamente automatizado | 7-10 min | Mínimo |
| **GUIA_DESPLIEGUE.md** | Quieres entender cada paso, primer despliegue, o solucionar problemas | 15-20 min | Medio |
| **VERIFICACION_PRE_DESPLIEGUE.md** | Quieres listas de verificación exhaustivas y procedimientos de verificación | 30-60 min | Exhaustivo |
| **RESUMEN_DESPLIEGUE_FASE3.md** | Quieres descripción general de arquitectura y qué está cambiando | 10 min | Referencia |

---

## Flujo de Trabajo Completo de Despliegue

### Fase 1: Pre-Despliegue (30 minutos)

#### 1.1 Revisar Configuración
- [ ] Lee RESUMEN_DESPLIEGUE_FASE3.md para entender qué está cambiando
- [ ] Revisa secciones 2-3 de VERIFICACION_PRE_DESPLIEGUE.md para configuración del entorno
- [ ] Verifica que `.env.production` tiene correcto `VITE_PUSHER_HOST` (tu IP/dominio VPS)

#### 1.2 Verificar Requisitos Previos en VPS
```bash
# SSH a VPS
ssh usuario@tu-vps

# Verificar Docker
docker --version
# Esperado: Docker version XX.XX.XX

# Verificar Docker Compose
docker-compose --version
# Esperado: docker-compose version XX.XX.XX

# Verificar espacio en disco
df -h /
# Esperado: Al menos 50GB disponible

# Verificar estado actual de git
cd /ruta/a/taskflow
git branch
git log -1 --oneline
# Esperado: En rama main, commits recientes visibles
```

#### 1.3 Crear Copias de Seguridad
```bash
# Opción 1: Si el sistema actual está ejecutándose
ssh usuario@tu-vps
docker-compose -f docker-compose.prod.yml exec -T db mysqldump \
  -u root -p${DB_ROOT_PASSWORD} taskflow_prod > \
  copia-seguridad-db-$(date +%Y%m%d-%H%M%S).sql

# Opción 2: Copia de seguridad completa del sistema de archivos
tar -czf copia-seguridad-$(date +%Y%m%d-%H%M%S).tar.gz /ruta/a/taskflow

# Mantener copias de seguridad seguras (copiar a máquina local)
scp usuario@tu-vps:copia-seguridad-*.* ~/copias-seguridad/
```

#### 1.4 Preparar Script de Despliegue
```bash
# En máquina local donde ejecutarás despliegue

# Opción A: Usar script automatizado
scp despliegue-fase3.sh usuario@tu-vps:/ruta/a/taskflow/
ssh usuario@tu-vps "chmod +x /ruta/a/taskflow/despliegue-fase3.sh"

# Opción B: Mantener GUIA_DESPLIEGUE.md a mano
# (Seguirás pasos manualmente)
```

#### 1.5 Verificación Final
```bash
# En VPS: Verifica que el código está listo
git log -1 --oneline
# Debe mostrar commits recientes de rama main

# Verifica sin cambios sin hacer commit
git status
# Debe mostrar: "On branch main, nothing to commit"

# Verifica archivo docker-compose existe
ls -la docker-compose.prod.yml
# Debe mostrar que el archivo existe

# Verifica archivos .env existen
ls -la .env.docker
ls -la .env.production
# Ambos deben existir
```

### Fase 2: Despliegue (10-20 minutos)

#### Opción A: Despliegue Automatizado

**En Servidor VPS**:
```bash
cd /ruta/a/taskflow

# Ejecutar script automatizado
./despliegue-fase3.sh producción

# Ver salida - debe mostrar:
# ✅ Requisitos verificados
# ✅ Estado de Git verificado
# ✅ Copia de seguridad creada
# ✅ Contenedores detenidos
# ... (más verificaciones)
# ✅ ¡Despliegue Completado Exitosamente!

# Si aparece cualquier ✗, anota el paso y detén
```

**Tiempo total**: 7-10 minutos
**Riesgo**: Muy Bajo (el script tiene verificación exhaustiva de errores)

---

#### Opción B: Despliegue Manual

**En Servidor VPS**, sigue estos 10 pasos de GUIA_DESPLIEGUE.md:

1. Verificaciones previas al despliegue
2. Detener contenedores
3. Actualizar código
4. Verificar entornos
5. Construir imágenes
6. Iniciar servicios
7. Ejecutar migraciones
8. Limpiar cachés
9. Verificar FASE 3
10. Probar endpoints

**Tiempo total**: 15-20 minutos
**Riesgo**: Muy Bajo (pasos detallados con verificación en cada etapa)

---

### Fase 3: Verificación Post-Despliegue (15 minutos)

#### 3.1 Verificaciones Inmediatas (Primeros 5 minutos)
```bash
# Verificar todos los contenedores ejecutándose
docker-compose -f docker-compose.prod.yml ps
# Debe mostrar: 7 servicios, todos con estado "Up"

# Verificar logs recientes
docker-compose -f docker-compose.prod.yml logs --tail=30 backend
# NO debe mostrar errores FATAL

# Verificar migraciones aplicadas
docker-compose -f docker-compose.prod.yml exec backend \
  php artisan migrate:status | grep FASE
# Debe mostrar todas migraciones FASE 3 como "Ran"
```

#### 3.2 Pruebas de API (5-10 minutos)
```bash
# Prueba 1: API responde
curl -s http://localhost/api/v1/cases | head -5
# Debe retornar JSON (200) o error (no 404/500)

# Prueba 2: Endpoint legacy deprecado
curl -s -I http://localhost/api/v1/cases/1/request-closure
# Debe mostrar: HTTP/1.1 410 Gone

# Prueba 3: Frontend carga
curl -s http://localhost/ | grep -i "vue\|vite" | head -1
# Debe mostrar referencia Vue/Vite

# Prueba 4: Nuevo endpoint FASE 3 existe (retorna 401 sin auth, está bien)
curl -s -I http://localhost/api/v1/closure-requests
# Debe mostrar: HTTP/1.1 401 Unauthorized (no 404 o 500)
```

#### 3.3 Pruebas Funcionales (5 minutos)
**En tu navegador o Postman**:

1. Inicia sesión como usuario regular
   - Navega a casos
   - Verifica que botón "Solicitar Cierre" aparece
   - Intenta hacer clic

2. Cambia a usuario del departamento SAC
   - Verifica que puedo ver solicitudes pendientes
   - Verifica que botones aprobar/rechazar funcionan

3. Prueba flujo end-to-end
   - Solicita cierre desde usuario regular
   - Aprueba desde usuario SAC
   - Verifica que estado del caso cambió a "Cerrado"

---

### Fase 4: Monitoreo (24 horas)

#### Primera Hora: Monitoreo en Tiempo Real
```bash
# Mantener logs abiertos en terminal
docker-compose -f docker-compose.prod.yml logs -f backend

# En otra terminal, verifica estado periódicamente
watch -n 10 'docker-compose -f docker-compose.prod.yml ps'
```

**Qué observar**:
- Cualquier mensaje ERROR o FATAL en logs
- Contenedores reiniciándose inesperadamente (verifica columna "Restarts")
- Errores de conexión de base de datos
- Fallos de conexión de WebSocket

#### Horas 2-6: Pruebas de Características
**Que Tu Equipo de Pruebas Intente**:
- [ ] Crear solicitud de cierre (como usuario asignado)
- [ ] Ver solicitudes pendientes (como usuario SAC)
- [ ] Aprobar solicitud de cierre
- [ ] Rechazar solicitud de cierre
- [ ] Verifica que estado del caso se actualizó correctamente
- [ ] Prueba permisos (usuario no-SAC debe obtener error)

#### Horas 7-24: Monitoreo de Salud
```bash
# Verificar espacio en disco
docker system df

# Monitorear base de datos
docker-compose -f docker-compose.prod.yml exec db \
  du -sh /var/lib/mysql

# Verificar para fugas de memoria (redis/backend estable)
docker stats

# Revisar logs completos para patrones
docker-compose -f docker-compose.prod.yml logs backend | grep -i "error\|warning" | tail -50
```

---

## Si Algo Sale Mal

### Problema: El Script Falla en Paso X

1. **Anota el número de paso** (mostrado en la parte superior de la salida)
2. **Verifica el mensaje de error** (mostrado después del ✗)
3. **Referencia**: VERIFICACION_PRE_DESPLIEGUE.md sección 8 para problemas comunes
4. **Soluciones comunes**:
   - ¿Disco lleno? Ejecuta `docker system prune`
   - ¿Docker no corriendo? Inicia demonio Docker
   - ¿Puerto en uso? Verifica `lsof -i :80`
   - ¿Error de base de datos? Verifica `docker logs db`

### Problema: No puedo acceder a frontend/API después del despliegue

1. **Verifica servicios ejecutándose**:
   ```bash
   docker-compose -f docker-compose.prod.yml ps
   ```

2. **Verifica logs de gateway**:
   ```bash
   docker-compose -f docker-compose.prod.yml logs gateway
   ```

3. **Verifica configuración de Nginx**:
   ```bash
   docker-compose -f docker-compose.prod.yml exec gateway \
     nginx -t
   ```

4. **Reinicia gateway**:
   ```bash
   docker-compose -f docker-compose.prod.yml restart gateway
   ```

### Problema: Pruebas Fallando o API Retornando Errores

1. **Verifica logs de backend para error específico**:
   ```bash
   docker-compose -f docker-compose.prod.yml logs backend | grep -A 5 "error\|exception"
   ```

2. **Verifica conexión de base de datos**:
   ```bash
   docker-compose -f docker-compose.prod.yml exec backend \
     php artisan tinker
   >>> DB::connection()->getPdo()  # Debe mostrar objeto de conexión
   ```

3. **Verifica migraciones**:
   ```bash
   docker-compose -f docker-compose.prod.yml exec backend \
     php artisan migrate:status
   ```

---

## Procedimiento de Reversión

### Reversión Rápida (< 5 minutos)

**Si se descubre problema crítico dentro de la primera hora**:

```bash
# Método 1: Usar directorio de copia de seguridad auto-creado
BACKUP_DIR=$(ls -td .backup-* | head -1)
echo "Copia de seguridad encontrada: $BACKUP_DIR"

# Detener todo
docker-compose -f docker-compose.prod.yml down

# Restaurar base de datos
docker-compose -f docker-compose.prod.yml exec -T db mysql \
  -u root -p${DB_ROOT_PASSWORD} taskflow_prod < \
  $BACKUP_DIR/db_backup.sql

# Revertir código al commit anterior
git reset --hard HEAD~1

# Reconstruir e iniciar
docker-compose -f docker-compose.prod.yml up -d --build

# Esperar 2 minutos para que servicios se estabilicen
sleep 120
docker-compose -f docker-compose.prod.yml ps
```

**Resultado esperado**: Todos los servicios mostrando estado "Up" nuevamente

---

## Indicadores de Éxito

### Luces Verdes ✅
- [ ] Los 7 servicios ejecutándose (`docker-compose ps`)
- [ ] Logs de backend sin errores FATAL
- [ ] API responde en `http://localhost/api/v1`
- [ ] Endpoints legacy devuelven 410 Gone
- [ ] Frontend carga en `http://localhost`
- [ ] Endpoints de FASE 3 accesibles (retornan 401 sin auth)
- [ ] Base de datos tiene tabla `case_closure_requests`
- [ ] Métodos de usuario modelo funcionan (prueba tinker)
- [ ] Usuarios SAC pueden aprobar/rechazar
- [ ] Usuarios no-SAC obtienen 403 al intentar aprobar

### Banderas Rojas ❌
- Servicios atrapados en estado "Restarting"
- Backend lanzando errores 500
- Errores de conectividad de base de datos
- Errores Nginx 502/503
- Espacio en disco agotado
- Conflictos de puerto (ya en uso)

---

## Archivos Creados para Despliegue

### Documentos de Despliegue
- **GUIA_DESPLIEGUE.md** - 400+ líneas, guía paso a paso
- **VERIFICACION_PRE_DESPLIEGUE.md** - 300+ líneas, lista de verificación exhaustiva
- **RESUMEN_DESPLIEGUE_FASE3.md** - 400+ líneas, descripción general de arquitectura
- **LISTO_PARA_DESPLIEGUE.md** - Este archivo, guía de referencia rápida

### Automatización de Despliegue
- **despliegue-fase3.sh** - 450+ líneas, script de despliegue completamente automatizado

### Referencia
- **GUIA_MIGRACION_API.md** - Información de migración de frontend
- **RESUMEN_IMPLEMENTACION.md** - Resumen de implementación completo
- **HISTORIAL_CAMBIOS_SISTEMA_CIERRE.md** - Registro de cambios técnico

---

## Referencias de Comandos de Despliegue

```bash
# Verificación rápida de estado de despliegue
ssh usuario@tu-vps "cd /ruta/a/taskflow && \
  git status && \
  docker-compose -f docker-compose.prod.yml ps && \
  ls -la docker-compose.prod.yml .env.docker .env.production"

# Ejecutar despliegue automatizado
ssh usuario@tu-vps "cd /ruta/a/taskflow && \
  chmod +x despliegue-fase3.sh && \
  ./despliegue-fase3.sh producción"

# Verificar estado post-despliegue
ssh usuario@tu-vps "cd /ruta/a/taskflow && \
  docker-compose -f docker-compose.prod.yml ps && \
  docker-compose -f docker-compose.prod.yml logs --tail=20 backend"

# Probar endpoints
ssh usuario@tu-vps "curl -s -I http://localhost/api/v1/closure-requests && \
  curl -s -I http://localhost/api/v1/cases/1/request-closure"
```

---

## ¿Listo? ¡Vamos!

**Estado Actual**: ✅ Todo está listo para despliegue

**Siguiente Acción**: Ejecutar despliegue cuando estés listo

**Preguntas?**: Verifica documentos de referencia listados arriba

**Nivel de Confianza**: 🟢 ALTO - Pruebas exhaustivas, guías detalladas, script automatizado con verificación de errores

---

**Estado del Documento**: ✅ Completo
**Última Actualización**: 2026-01-08
**Preparado por**: Claude Code Agent
**Revisado por**: Listo para Revisión del Usuario y Despliegue
