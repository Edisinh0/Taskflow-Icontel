# Análisis: Sincronización de Oportunidades - Comparativa SweetCRM vs BD Local

**Fecha**: 8 de Enero de 2026  
**Objetivo**: Validar que la BD local capture toda la información de oportunidades de SweetCRM para sincronización eficiente

---

## 1. Campos que SweetCRM Proporciona para Oportunidades

**Fuente**: `app/Services/SweetCrmService.php:800-823`

SweetCRM retorna **18 campos** para cada oportunidad:

```php
'select_fields' => [
    'id',                      // ID único de SweetCRM (UUID)
    'name',                    // Nombre/título de la oportunidad
    'description',             // Descripción detallada
    'amount',                  // Monto en moneda local
    'amount_usdollar',         // Monto en USD
    'currency_id',             // ID de la moneda
    'sales_stage',             // Etapa de venta (Prospecting, Qualification, etc.)
    'probability',             // Probabilidad de cierre (0-100)
    'date_closed',             // Fecha esperada de cierre
    'next_step',               // Próximo paso a realizar
    'lead_source',             // Fuente del lead (referral, web, etc.)
    'opportunity_type',        // Tipo de oportunidad (New Business, Existing, etc.)
    'account_id',              // ID de la cuenta/cliente
    'account_name',            // Nombre de la cuenta/cliente
    'assigned_user_id',        // ID del usuario asignado
    'assigned_user_name',      // Nombre del usuario asignado
    'created_by',              // ID de quien creó el registro
    'date_entered',            // Fecha de creación
    'date_modified',           // Fecha de última modificación
]
```

---

## 2. Campos Actualmente Sincronizados en BD Local

**Tabla**: `crm_opportunities`

```sql
DESCRIBE crm_opportunities;
```

**Campos sincronizados**: 13

| Campo | Tipo | Sincronizado | Fuente SweetCRM |
|-------|------|:------------:|-----------------|
| id | bigint | ✅ | (auto) |
| name | varchar(255) | ✅ | name |
| sweetcrm_id | varchar(255) | ✅ | id |
| sales_stage | varchar(255) | ✅ | sales_stage |
| amount | decimal(15,2) | ✅ | amount |
| currency | varchar(255) | ✅ | currency_id |
| expected_closed_date | date | ✅ | date_closed |
| client_id | bigint (FK) | ✅ | account_id (join) |
| sweetcrm_assigned_user_id | varchar(255) | ✅ | assigned_user_id |
| description | text | ✅ | description |
| sweetcrm_synced_at | datetime | ✅ | (auto) |
| created_at | timestamp | ✅ | date_entered |
| updated_at | timestamp | ✅ | date_modified |
| deleted_at | timestamp | ✅ | (soft delete) |

---

## 3. Campos de SweetCRM NO Sincronizados

**5 campos faltan**:

| Campo SweetCRM | Importancia | Recomendación | Caso de Uso |
|----------------|:----------:|----------------|----|
| **amount_usdollar** | 🟡 Media | Agregar campo `amount_usd` | Reportes multimoneda |
| **probability** | 🟢 Alta | Agregar campo `probability` | Cálculo de pipeline ponderado |
| **next_step** | 🟡 Media | Agregar campo `next_step` | Seguimiento de acciones |
| **lead_source** | 🟡 Media | Agregar campo `lead_source` | Análisis de fuentes |
| **opportunity_type** | 🟡 Media | Agregar campo `type` | Clasificación de oportunidades |
| **created_by** | 🟢 Alta | Agregar campo `created_by_id` | Auditoría y permisos |

---

## 4. Comparativa: Estructura Casos vs Oportunidades

### Campos en Casos (CrmCase)

```php
'fillable' => [
    'case_number',                  // Número único del caso
    'subject',                      // Asunto principal
    'description',                  // Descripción
    'status',                       // Estado (Open, Closed, etc.)
    'priority',                     // Prioridad (High, Medium, Low)
    'type',                         // Tipo de caso
    'area',                         // Área responsable
    'client_id',                    // FK a cliente
    'created_by',                   // Creador
    'sweetcrm_id',                  // ID de SweetCRM
    'sweetcrm_account_id',          // ID de cuenta en SweetCRM
    'sweetcrm_assigned_user_id',    // Asignado a
    'sweetcrm_synced_at',           // Última sincronización
    'sweetcrm_created_at',          // Fecha creación en SweetCRM
    'original_creator_id',          // ID del creador
    'original_creator_name',        // Nombre del creador
    'assigned_user_name',           // Nombre del asignado
    'closure_requested',            // Flag: Solicitud cierre
    'closure_requested_at',         // Fecha solicitud cierre
    'closure_requested_by',         // Quién solicita cierre
    'closure_rejection_reason',     // Razón de rechazo
]
```

**Total**: 21 campos

### Campos en Oportunidades (CrmOpportunity)

```php
'fillable' => [
    'name',                         // Nombre
    'sweetcrm_id',                  // ID de SweetCRM
    'sales_stage',                  // Etapa de venta
    'amount',                       // Monto
    'currency',                     // Moneda
    'expected_closed_date',         // Fecha esperada cierre
    'client_id',                    // FK a cliente
    'sweetcrm_assigned_user_id',    // Asignado a
    'description',                  // Descripción
    'sweetcrm_synced_at',           // Última sincronización
]
```

**Total**: 10 campos

---

## 5. Análisis de Completitud

### ✅ Bien Sincronizado
- Información básica de oportunidad (nombre, monto, etapa)
- Fechas de cierre esperadas
- Usuario asignado
- Cliente vinculado
- Descripción

### 🟡 Parcialmente Sincronizado
- Datos financieros (solo monto en moneda local, falta USD)
- Campos de auditoría (falta `created_by`)
- Clasificación (falta `type` y `lead_source`)

### ❌ No Sincronizado
- **Probability**: Necesario para cálculos de pipeline ponderado
- **Next Step**: Importante para seguimiento
- **Lead Source**: Importante para análisis de fuentes
- **Opportunity Type**: Para segmentación
- **Amount USD**: Para reportes multimoneda
- **Created By**: Para auditoría

---

## 6. Recomendaciones de Mejora

### 6.1 **Campos Imprescindibles a Agregar** (Priority 🟢 Alta)

```sql
ALTER TABLE crm_opportunities ADD COLUMN (
    probability INT DEFAULT 0,                      -- 0-100
    amount_usd DECIMAL(15,2) DEFAULT 0,            -- Monto en USD
    created_by_id VARCHAR(255),                     -- ID creador
    created_by_name VARCHAR(255)                    -- Nombre creador
);
```

**Impacto Frontend**: 
- Permitirá mostrar probabilidad de cierre
- Cálculo automático de pipeline ponderado
- Información de auditoría completa

### 6.2 **Campos Recomendados a Agregar** (Priority 🟡 Media)

```sql
ALTER TABLE crm_opportunities ADD COLUMN (
    next_step TEXT,                                -- Próximo paso
    lead_source VARCHAR(255),                      -- Fuente del lead
    opportunity_type VARCHAR(255)                  -- Tipo de oportunidad
);
```

**Impacto Frontend**:
- Mejor seguimiento de acciones
- Análisis de fuentes de leads
- Mejor clasificación y filtrado

### 6.3 **Actualizar Modelo Eloquent**

**Archivo**: `app/Models/CrmOpportunity.php`

```php
protected $fillable = [
    'name',
    'sweetcrm_id',
    'sales_stage',
    'amount',
    'amount_usd',                    // NUEVO
    'currency',
    'probability',                   // NUEVO
    'expected_closed_date',
    'client_id',
    'sweetcrm_assigned_user_id',
    'description',
    'next_step',                     // NUEVO
    'lead_source',                   // NUEVO
    'opportunity_type',              // NUEVO
    'created_by_id',                 // NUEVO
    'created_by_name',               // NUEVO
    'sweetcrm_synced_at',
];

protected $casts = [
    'amount' => 'decimal:2',
    'amount_usd' => 'decimal:2',     // NUEVO
    'probability' => 'integer',      // NUEVO
    'expected_closed_date' => 'date',
    'sweetcrm_synced_at' => 'datetime',
];
```

---

## 7. Comparación de Presentación Frontend

### Casos (CasesView.vue)
- Tabla con: Número, Asunto, Estado, Prioridad, Usuario Asignado, Fecha Creación
- Filtros por: Estado, Prioridad, Usuario, Búsqueda
- Acciones: Ver Detalles, Editar, Solicitar Cierre
- Sección anidada: Tareas dentro de cada caso

### Oportunidades (OpportunitiesView.vue)
- Grid de tarjetas con: Nombre, Etapa, Monto, Fecha Cierre, Cliente
- Filtros por: Búsqueda, Etapa de Venta
- Acciones: Ver Detalles, Lanzar Flujo Operativo
- Información adicional: Descripción

### Recomendaciones de Mejora para Frontend

Para mantener **consistencia de estilo** con Casos:

```vue
<!-- Agregar a tarjeta de oportunidad -->
<div class="flex items-center gap-2 text-sm text-slate-600">
  <Target class="w-4 h-4" />
  <span>Probabilidad: {{ opp.probability }}%</span>
</div>

<div class="flex items-center gap-2 text-sm text-slate-600">
  <User class="w-4 h-4" />
  <span>Creado por: {{ opp.created_by_name }}</span>
</div>

<div class="flex items-center gap-2 text-sm text-slate-600">
  <CheckCircle class="w-4 h-4" />
  <span>Próximo paso: {{ opp.next_step }}</span>
</div>

<!-- Estadísticas de pipeline -->
<div class="bg-slate-100 p-3 rounded-xl">
  <p class="text-xs font-bold text-slate-600">PIPELINE PONDERADO</p>
  <p class="text-lg font-black text-slate-800">
    {{ formatCurrency(totalWeightedPipeline) }}
  </p>
</div>
```

---

## 8. Plan de Implementación

### Fase 1: Extender BD (1-2 horas)
1. Crear migration para nuevos campos
2. Actualizar modelo `CrmOpportunity`
3. Actualizar seeders si existen

### Fase 2: Sincronización (2-3 horas)
1. Verificar que `SweetCrmService::getOpportunities()` ya trae estos campos ✅
2. Si no existe, crear `SyncSweetCrmOpportunities` command
3. Mapear campos de SweetCRM a BD local
4. Agregar lógica de creación/actualización

### Fase 3: API Backend (1-2 horas)
1. Actualizar endpoint `/dashboard/sales-content` para incluir nuevos campos
2. Agregar validaciones y transformaciones de datos

### Fase 4: Frontend (2-3 horas)
1. Actualizar `OpportunitiesView.vue` para mostrar nuevos campos
2. Agregar filtros por probabilidad, fuente de lead
3. Mostrar estadísticas de pipeline ponderado
4. Agregar información de creador y próximo paso

---

## 9. Conclusión

### Completitud Actual: **62%** (10 de 18 campos)

La tabla `crm_opportunities` captura los campos **esenciales** pero **pierde información valiosa** que SweetCRM proporciona:

- ✅ Estructura básica lista
- 🟡 Faltarían 6 campos recomendados
- ❌ Faltarían 2 campos críticos (probability, created_by)

### Recomendación Final

**Proceder con Sincronización PERO agregar campos antes:**

1. **Ahora**: Extender schema con al menos `probability` y `created_by_id`
2. **Luego**: Crear y ejecutar comando de sincronización
3. **Después**: Mejorar presentación frontend con nueva información

Esto asegurará que la sincronización sea **completa, auditable y con máximo valor** para el frontend.

---

## 10. SQL Scripts Listos

### Crear Migration

```bash
php artisan make:migration add_additional_fields_to_crm_opportunities
```

### Contenido Migration

```php
Schema::table('crm_opportunities', function (Blueprint $table) {
    $table->integer('probability')->nullable()->default(0)->comment('Probabilidad 0-100');
    $table->decimal('amount_usd', 15, 2)->nullable()->comment('Monto en USD');
    $table->string('created_by_id')->nullable()->comment('ID creador en SweetCRM');
    $table->string('created_by_name')->nullable()->comment('Nombre del creador');
    $table->text('next_step')->nullable()->comment('Próximo paso');
    $table->string('lead_source')->nullable()->comment('Fuente del lead');
    $table->string('opportunity_type')->nullable()->comment('Tipo de oportunidad');
    $table->timestamp('date_entered')->nullable()->comment('Fecha creación SweetCRM');
    $table->timestamp('date_modified')->nullable()->comment('Fecha última modificación');
});
```

