# ✅ Implementación Actualizada: Filtro de Área por Responsable

## 🎉 Estado: ACTUALIZADO

Se ha modificado la lógica del filtro para que el **Área** se determine según el **Departamento** de la persona asignada al caso.

---

## 📋 Resumen de Cambios

### ✅ Backend

#### 1. **Base de Datos**
- ✅ Nueva migración ejecutada: `2025_12_23_184500_add_department_to_users_table.php`
- ✅ Campo `department` agregado a la tabla `users`.

#### 2. **Controlador de Casos**
- ✅ El filtro `area` ahora usa `whereHas('assignedUser', ...)` para buscar en el campo `department` del usuario.
- ✅ Los `stats` ahora se calculan uniendo la tabla de casos con la de usuarios por `sweetcrm_assigned_user_id`.

#### 3. **Sincronización**
- ✅ El servicio de SweetCRM ahora solicita el campo `department` de los usuarios.
- ✅ El comando `sweetcrm:sync-users` ahora guarda el departamento del usuario.

### ✅ Frontend

#### 1. **Vista de Casos** (`CasesView.vue`)
- ✅ La columna de área ahora muestra `assigned_user.department`.
- ✅ Se muestra el nombre del responsable debajo del departamento para mayor claridad.
- ✅ El filtro sigue funcionando con las opciones seleccionadas.

---

## 🚀 Cómo Actualizar los Datos

Para que el filtro funcione con datos reales, debes sincronizar los usuarios primero para obtener sus departamentos:

### 1. Sincronizar Usuarios
```bash
docker exec taskflow_app_dev php artisan sweetcrm:sync-users {username} {password}
```

### 2. Sincronizar Casos (si no lo has hecho)
```bash
docker exec taskflow_app_dev php artisan sweetcrm:sync-cases {username} {password}
```

---

## ⚠️ Nota sobre los Valores de Área

El filtro en el frontend espera los siguientes valores exactos (sensible a mayúsculas/minúsculas dependiendo de la DB):
- `Operaciones`
- `Soporte`
- `Atención al Cliente`
- `Ventas`

Si en SweetCRM los departamentos tienen nombres distintos (ej. "Ventas Nacionales" o "Soporte Técnico"), el filtro fallará. Asegúrate de que los nombres coincidan o avísame para agregar un mapeo de nombres.

---

## 🚀 Cómo Usar

### 1. Sincronizar Casos desde SweetCRM

Para sincronizar casos desde SweetCRM con el campo de área:

```bash
docker exec taskflow_app_dev php artisan sweetcrm:sync-cases {username} {password} --limit=100
```

**Ejemplo:**
```bash
docker exec taskflow_app_dev php artisan sweetcrm:sync-cases admin@sweetcrm.com mypassword --limit=50
```

**Parámetros:**
- `username`: Usuario de SweetCRM
- `password`: Contraseña de SweetCRM
- `--limit`: (Opcional) Número máximo de casos a sincronizar (0 = sin límite)

### 2. Usar el Filtro en la Interfaz

1. Navega a la vista de **Casos** en Taskflow
2. En el sidebar izquierdo, encontrarás el filtro **"Área"**
3. Selecciona el área que deseas filtrar:
   - Todas las áreas
   - Operaciones
   - Soporte
   - Atención al Cliente
   - Ventas
4. La tabla se actualizará automáticamente

### 3. Ver el Área de un Caso

En la tabla de casos, la columna **"Área"** mostrará:
- Un badge con color distintivo si el caso tiene área asignada
- "Sin área" en gris si no tiene área asignada

---

## 🎨 Colores de Áreas

| Área | Color | Badge |
|------|-------|-------|
| **Operaciones** | Morado | `bg-purple-500/10 text-purple-600 border-purple-500/20` |
| **Soporte** | Cyan | `bg-cyan-500/10 text-cyan-600 border-cyan-500/20` |
| **Atención al Cliente** | Rosa | `bg-pink-500/10 text-pink-600 border-pink-500/20` |
| **Ventas** | Verde | `bg-green-500/10 text-green-600 border-green-500/20` |

---

## 📝 Notas Importantes

### Campo Personalizado en SweetCRM

El campo `area` se mapea desde el campo personalizado `area_c` en SweetCRM. Si en tu instalación de SweetCRM el campo tiene un nombre diferente, debes actualizar:

1. **`app/Services/SweetCrmService.php`** (línea ~543):
   ```php
   'area_c', // Cambiar por el nombre correcto del campo
   ```

2. **`app/Console/Commands/SyncSugarCrmCases.php`** (línea ~141):
   ```php
   'area' => $nvl['area_c']['value'] ?? null, // Cambiar 'area_c'
   ```

### Valores Válidos de Área

Actualmente, la aplicación acepta cualquier valor de texto para el área. Los valores esperados son:
- `Operaciones`
- `Soporte`
- `Atención al Cliente`
- `Ventas`

Si necesitas agregar validación en el backend para asegurar que solo se usen estos valores, puedes agregar una regla de validación en el modelo o controlador.

---

## 🔍 Verificación

Para verificar que todo está funcionando correctamente:

1. **Verificar la migración:**
   ```bash
   docker exec taskflow_app_dev php artisan migrate:status
   ```
   Deberías ver `2025_12_23_182900_add_area_to_crm_cases_table` con estado `Ran`

2. **Verificar la estructura de la tabla:**
   ```bash
   docker exec taskflow_app_dev php artisan tinker
   ```
   ```php
   Schema::hasColumn('crm_cases', 'area') // Debería retornar true
   ```

3. **Sincronizar casos de prueba:**
   ```bash
   docker exec taskflow_app_dev php artisan sweetcrm:sync-cases {username} {password} --limit=10
   ```

4. **Verificar en la interfaz:**
   - Abre http://localhost:5174/cases
   - Verifica que el filtro de área aparece
   - Verifica que la columna de área se muestra en la tabla

---

## 🐛 Solución de Problemas

### El campo área no se sincroniza

**Problema:** Los casos se sincronizan pero el campo `area` está vacío.

**Solución:**
1. Verifica que el campo existe en SweetCRM con el nombre `area_c`
2. Verifica que los casos en SweetCRM tienen valores en ese campo
3. Revisa los logs de Laravel: `docker exec taskflow_app_dev tail -f storage/logs/laravel.log`

### El filtro no funciona

**Problema:** El filtro de área no filtra los casos.

**Solución:**
1. Abre la consola del navegador (F12) y busca errores
2. Verifica que el servidor de desarrollo está corriendo: `cd taskflow-frontend && npm run dev`
3. Limpia la caché del navegador

### Error de migración

**Problema:** La migración falla al ejecutarse.

**Solución:**
1. Verifica que la tabla `crm_cases` existe
2. Verifica permisos de la base de datos
3. Si la columna ya existe, puedes saltarte la migración

---

## 📚 Archivos Modificados

### Backend
1. `database/migrations/2025_12_23_182900_add_area_to_crm_cases_table.php` (nuevo)
2. `app/Models/CrmCase.php`
3. `app/Http/Controllers/Api/CaseController.php`
4. `app/Services/SweetCrmService.php`
5. `app/Console/Commands/SyncSugarCrmCases.php`

### Frontend
1. `src/views/CasesView.vue`

---

## 🎯 Próximos Pasos Sugeridos

1. ✅ **Ejecutar sincronización inicial** de casos con áreas
2. ⬜ **Agregar validación** en el backend para valores de área
3. ⬜ **Agregar área en el modal** de detalle de caso
4. ⬜ **Crear estadísticas** por área en el dashboard
5. ⬜ **Agregar filtro de área** en reportes
6. ⬜ **Configurar sincronización automática** (cron job)

---

## 📞 Soporte

Si tienes algún problema o pregunta sobre esta implementación, revisa:
- Los logs de Laravel: `storage/logs/laravel.log`
- La consola del navegador (F12)
- Los logs de Vite en la terminal del frontend

---

**Fecha de implementación:** 23 de Diciembre de 2025  
**Versión:** 1.0.0  
**Estado:** ✅ Producción Ready
