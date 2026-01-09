# 📖 Guía de Traducción: Implementación de Tareas SuiteCRM

**Propósito**: Traducir términos técnicos a conceptos de negocio
**Idioma**: Español
**Fecha**: 2026-01-09

---

## 🔄 Traducción de Conceptos

### Términos Técnicos → Términos de Negocio

| Técnico | En Español | Explicación |
|---------|------------|-------------|
| **parent** | Elemento padre / Vinculado a | La tarea está vinculada a un Caso u Oportunidad |
| **sweetcrm_id** | ID de SuiteCRM | Identificador único en el sistema externo |
| **name_value_list** | Lista de campos | Formato de datos para enviar a SuiteCRM |
| **set_entry** | Crear registro | Operación de creación en SuiteCRM |
| **sync** | Sincronización | Mantener datos igual en ambos sistemas |
| **retry** | Reintento | Intentar nuevamente si falla |
| **validation** | Validación | Verificar que los datos sean correctos |

---

## 📊 Mapeo de Campos en Español

### Base de Datos Local → SuiteCRM

```
CAMPOS LOCALES                    CAMPOS DE SUITECRM           SIGNIFICADO
─────────────────────────────────────────────────────────────────────────
title (Título)                    name (Nombre)                Lo que se llama la tarea
priority (Prioridad)              priority (Prioridad)         Qué tan urgente es
status (Estado)                   status (Estado)              Qué está pasando ahora
estimated_start_at                date_start                   Cuándo empieza
(Fecha estimada de inicio)        (Fecha de inicio)
estimated_end_at                  date_due                     Cuándo debe terminar
(Fecha estimada de fin)           (Fecha de término)
description (Descripción)         description (Descripción)    Detalles adicionales
parent_type (Tipo de vínculo)     parent_type (Tipo padre)     ¿Es un Caso u Oportunidad?
parent_id (ID del vínculo)        parent_id (ID padre)         ¿Cuál Caso u Oportunidad?
assigned_user_id (Asignado a)     assigned_user_id (Usuario)   ¿Quién es responsable?
```

---

## 🎯 Flujo de Trabajo (En Palabras Simples)

### 1. Usuario crea una tarea
```
"Quiero crear una tarea para seguimiento con cliente"
```

### 2. Sistema valida los datos
```
✓ ¿Tiene título? → Sí
✓ ¿Tiene descripción? → Sí
✓ ¿Está vinculada a un Caso? → Sí
✓ ¿El Caso existe en nuestro sistema? → Sí
✓ ¿Las fechas son válidas? → Sí
→ VALIDACIÓN APROBADA
```

### 3. Se guarda en base de datos local
```
Se crea la tarea inmediatamente en nuestro sistema
- ID: 456
- Título: "Seguimiento con cliente"
- Vinculada a: Caso "Proyecto ABC"
```

### 4. Se sincroniza con SuiteCRM
```
Se envía la tarea a SuiteCRM automáticamente
- Intento 1: Envía los datos
  - Si funciona → Exitoso ✓
  - Si no funciona → Reintenta en 2 segundos
- Intento 2: Reintenta los datos
  - Si funciona → Exitoso ✓
  - Si no funciona → Reintenta en 2 segundos
- Intento 3: Reintenta los datos
  - Si funciona → Exitoso ✓
  - Si no funciona → Guarda error en log
```

### 5. Usuario ve la tarea creada
```
La tarea aparece en su dashboard
- Local: ID 456
- SuiteCRM: ID task-456-xyz
- Estado: Sincronizado ✓
```

---

## 🔐 Reglas de Validación (Explicadas)

### 1. Campos Obligatorios
```
Estos campos SIEMPRE son requeridos:
- Título: ¿Cuál es el nombre de la tarea?
- Prioridad: ¿Qué tan urgente es?
- Tipo de vínculo: ¿Es para un Caso u Oportunidad?
- Vínculo: ¿Cuál Caso u Oportunidad específicamente?
- Fecha de inicio: ¿Cuándo empieza?
- Fecha de término: ¿Cuándo debe terminar?
```

### 2. Validación de Fechas
```
- Fecha de inicio: DEBE ser anterior a fecha de término
- Formato: DEBE ser YYYY-MM-DD HH:MM:SS
  ✓ Válido: 2026-01-15 09:00:00
  ✗ Inválido: 15/01/2026
  ✗ Inválido: 2026-01-15 (sin hora)
```

### 3. Validación de Vínculo
```
El Caso u Oportunidad que selecciones:
- DEBE existir en nuestro sistema
- DEBE estar activo
- PUEDE encontrarse por:
  - ID local (número del caso)
  - ID de SuiteCRM (código único)
```

---

## 📈 Ventajas de esta Implementación

### Para el Usuario
```
ANTES:
- Crear tarea sin vínculo con Caso
- Hacer vínculo manualmente después
- Mayor trabajo manual

AHORA:
- Crear tarea vinculada directamente
- Sincronización automática con SuiteCRM
- Menos pasos, menos errores
```

### Para el Sistema
```
ANTES:
- Una falla = La tarea no se crea
- Sin reintentos automáticos
- Difícil saber qué salió mal

AHORA:
- Reintentos automáticos (3 intentos)
- Logging detallado de cada paso
- Fácil ver qué salió mal
```

### Para TI
```
ANTES:
- Una llamada de usuario = Investigación larga
- Pocas pistas de qué pasó
- Debugging difícil

AHORA:
- Logs detallados de cada operación
- Puedo ver exactamente qué pasó
- Puedo solucionarlo en minutos
```

---

## 🛠️ Cómo Leer los Logs

### Log de Tarea Exitosa
```
[2026-01-09 14:30:00] INFO: Parent Case found
  → Significa: El Caso vinculado existe ✓

[2026-01-09 14:30:01] INFO: Sending task to SuiteCRM
  date_start: 2026-01-15 09:00:00
  → Significa: Se está enviando a SuiteCRM con fecha válida

[2026-01-09 14:30:02] INFO: Task created in SuiteCRM successfully
  sweetcrm_id: task-456-xyz
  → Significa: La tarea se creó en SuiteCRM ✓
```

### Log de Reintento Exitoso
```
[2026-01-09 14:31:00] WARNING: SuiteCRM set_entry HTTP error
  status: 500
  → Significa: Primer intento falló

[2026-01-09 14:31:00] INFO: Retrying SuiteCRM task creation
  attempt: 1, next_attempt: 2
  → Significa: Sistema reintentará en 2 segundos

[2026-01-09 14:31:02] INFO: Task created in SuiteCRM successfully
  attempt: 2
  → Significa: Segundo intento tuvo éxito ✓
```

### Log de Error
```
[2026-01-09 14:32:00] ERROR: Caso/Oportunidad no encontrado
  parent_id: abc-123-xyz
  → Problema: El Caso que seleccionó no existe

[2026-01-09 14:33:00] CRITICAL: Job failed after all retries
  → Problema: 3 intentos fallidos, no se pudo sincronizar
```

---

## ❓ Preguntas Frecuentes (FAQ)

### P: ¿Qué significa "parent"?
**R**: Es el elemento padre al que está vinculada la tarea. Puede ser:
- Un **Caso** (por ejemplo: "Proyecto ABC")
- Una **Oportunidad** (por ejemplo: "Venta XYZ")

### P: ¿Qué es sincronización?
**R**: Mantener dos copias de lo mismo en sitios diferentes:
- Una copia en nuestra **BD local** (Taskflow)
- Una copia en **SuiteCRM** (sistema externo)

### P: ¿Qué pasa si SuiteCRM no responde?
**R**: El sistema reintenta automáticamente:
- Intento 1: Inmediato
- Intento 2: Espera 2 segundos
- Intento 3: Espera 2 segundos más
- Si todo falla: Registra error en logs

### P: ¿Qué es "sweetcrm_id"?
**R**: El identificador único que le da SuiteCRM a la tarea. Ejemplo: `task-456-xyz`

### P: ¿Debo hacer algo manual después de crear la tarea?
**R**: No. Todo es automático:
- ✓ Crea tarea localmente (inmediato)
- ✓ Sincroniza con SuiteCRM (automático)
- ✓ Si falla, reintenta (automático)
- ✓ Registra todo (automático)

---

## 📋 Checklist de Validación

### Antes de crear tarea:
- [ ] ¿Tiene título la tarea?
- [ ] ¿Tiene descripción?
- [ ] ¿Seleccionó un Caso u Oportunidad?
- [ ] ¿El Caso/Oportunidad existe?
- [ ] ¿Seleccionó una fecha de inicio?
- [ ] ¿Seleccionó una fecha de término?
- [ ] ¿La fecha de inicio es antes que la de término?
- [ ] ¿Seleccionó una prioridad?

Si respondió "Sí" a todo → ✓ Puede crear la tarea

---

## 🚀 Ejemplo Práctico Paso a Paso

### Escenario: Crear seguimiento para cliente

**Usuario piensa**:
```
"Necesito hacer seguimiento con el cliente del Proyecto ABC"
```

**Usuario entra al sistema**:
```
1. Click en "Nueva Tarea" botón
2. Llena el formulario:
   - Título: "Seguimiento con cliente"
   - Descripción: "Obtener feedback sobre propuesta"
   - Tipo de vínculo: "Caso"
   - Búsqueda: "Proyecto ABC" (encuentra el caso)
   - Responsable: "Juan García"
   - Prioridad: "Alta"
   - Fecha inicio: "15/01/2026 09:00"
   - Fecha término: "20/01/2026 17:00"
3. Click en "Crear Tarea"
```

**Sistema internamente**:
```
✓ Valida que "Proyecto ABC" exista
✓ Convierte fecha a formato 2026-01-15 09:00:00
✓ Crea la tarea en BD local (ID: 456)
✓ Envía a SuiteCRM:
   - Intento 1: Éxito ✓
✓ Registra: sweetcrm_id = task-456-xyz
✓ Marca como sincronizado
```

**Usuario ve**:
```
¡Tarea creada!
- Título: "Seguimiento con cliente"
- Estado: "Pendiente"
- Vinculada a: "Proyecto ABC"
- Responsable: "Juan García"
- Sincronizado: ✓
```

---

## 🎯 Resumen en 30 Segundos

**¿Qué cambió?**
- Mejora en cómo se crean tareas en SuiteCRM

**¿Por qué importa?**
- Sincronización más confiable
- Reintentos automáticos si algo falla
- Logs detallados para debugging

**¿Qué debe hacer el usuario?**
- Crear tareas normalmente
- Todo lo demás es automático

**¿Qué debe hacer TI?**
- Monitorear logs
- Nada más (sistema es automático)

---

## 📚 Documentos Relacionados

1. **ACTUALIZACION_COMPLETADA_RESUMEN_EJECUTIVO.md**
   - Resumen técnico completo
   - Ejemplos de curl
   - Guía de despliegue

2. **RESUMEN_IMPLEMENTACION_TAREAS_SUITECRM.md**
   - Detalles de implementación
   - Mapeo completo de campos
   - Testing requerido

3. **IMPLEMENTACION_TAREAS_SUITECRM_COMPLETA.md**
   - Plan técnico detallado
   - Fases de implementación
   - Consideraciones de seguridad

---

**Documento Generado por**: Claude Code (Haiku 4.5)
**Fecha**: 2026-01-09
**Propósito**: Explicar cambios técnicos en lenguaje de negocio

✅ **Listo para compartir con stakeholders no-técnicos**

