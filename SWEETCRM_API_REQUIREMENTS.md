# 🔍 Requerimientos para Integración con API de SweetCRM

## 📊 Estado Actual

### ✅ Configuración Completada
- URL de SweetCRM: `https://sweet.icontel.cl/`
- Token OAuth proporcionado: Configurado (36 caracteres)
- Conectividad: El servidor responde correctamente

### ❌ Problema Identificado
Todos los endpoints de la API requieren autenticación, pero el token OAuth proporcionado no está siendo aceptado. El servidor responde con:

```json
{
  "error": "access_denied",
  "error_description": "The resource owner or authorization server denied the request.",
  "hint": "Missing \"Authorization\" header",
  "message": "The resource owner or authorization server denied the request."
}
```

**Nota:** El mensaje indica "Missing Authorization header" incluso cuando se envía el header `Authorization: Bearer {token}`.

---

## ❓ Preguntas Críticas para el Equipo de SweetCRM

### 1. 🔐 Autenticación y Autorización

**1.1. ¿Qué tipo de token es el proporcionado?**
- Token actual: `737812fd-2290-03a4-3dde-694a972e8788`
- ¿Es un Client ID, Client Secret, API Key, o Access Token?
- ¿Necesita ser intercambiado por un access token antes de usar?

**1.2. ¿Cómo se debe enviar el token en las peticiones?**
- [ ] `Authorization: Bearer {token}`
- [ ] `Authorization: OAuth {token}`
- [ ] `Authorization: Token {token}`
- [ ] Como parámetro en query string: `?access_token={token}`
- [ ] En otro header: `X-API-Key: {token}` o similar
- [ ] Otra forma: _________________________________

**1.3. ¿Hay un flujo OAuth2 que debemos implementar?**
- ¿Necesitamos implementar el flujo Client Credentials?
- ¿Hay un endpoint `/oauth/token` para obtener access tokens?
- Si es así, ¿cuáles son los parámetros requeridos?

### 2. 📡 Endpoints de la API

**2.1. ¿Cuál es la URL base correcta de la API?**
- Probamos: `https://sweet.icontel.cl/api/`
- ¿Es correcta o debe ser diferente? (ej: `/api/v1/`, `/rest/`, etc.)

**2.2. ¿Qué endpoints están disponibles para autenticación de usuarios?**

Necesitamos un endpoint para validar credenciales de usuario (username/password) y obtener información del usuario.

**Ejemplo de lo que necesitamos:**
```
POST {base_url}/auth/login
Body: {
  "username": "ecerpa",
  "password": "usuario_password"
}

Response: {
  "success": true,
  "user": {
    "id": "123",
    "username": "ecerpa",
    "name": "Eduardo Cerpa",
    "email": "ecerpa@icontel.cl",
    "role": "user"
  },
  "token": "user_access_token"  // opcional
}
```

**Pregunta:** ¿Cuál es el endpoint correcto y la estructura exacta de request/response?

**2.3. ¿Qué otros endpoints están disponibles?**

Necesitamos endpoints para:
- [ ] Obtener listado de usuarios: `GET /users`
- [ ] Obtener un usuario específico: `GET /users/{id}`
- [ ] Obtener listado de clientes: `GET /clients` o `/accounts`
- [ ] Obtener un cliente específico: `GET /clients/{id}`
- [ ] Verificar conexión/salud: `GET /ping` o `/health`

Por favor, proporcionen la lista completa de endpoints disponibles.

### 3. 📄 Documentación

**3.1. ¿Existe documentación de la API?**
- URL de la documentación
- ¿Hay ejemplos de uso (Postman collection, cURL, etc.)?
- ¿Hay un ambiente de pruebas/sandbox disponible?

**3.2. ¿Hay límites de rate limiting?**
- ¿Cuántas peticiones por minuto/hora están permitidas?
- ¿Cómo se manejan los errores de rate limiting?

### 4. 🔄 Sincronización de Datos

**4.1. ¿Qué información de clientes podemos obtener?**
Estructura esperada de un objeto cliente:
```json
{
  "id": "cliente_id",
  "name": "Nombre Cliente",
  "industry": "Tecnología",
  "contact_email": "contacto@cliente.com",
  "phone": "+56912345678",
  // ¿Qué otros campos están disponibles?
}
```

**4.2. ¿Hay webhooks disponibles?**
- ¿Podemos recibir notificaciones cuando se crea/actualiza un cliente?
- ¿Cuando se crea/actualiza un usuario?

**4.3. ¿Soportan búsqueda/filtrado?**
- ¿Podemos filtrar clientes por industria, estado, fecha de creación, etc.?
- ¿Qué parámetros de búsqueda están disponibles?

---

## 🧪 Pruebas Realizadas

### Intento 1: Bearer Token
```bash
curl -X GET https://sweet.icontel.cl/api/users \
  -H "Authorization: Bearer 737812fd-2290-03a4-3dde-694a972e8788"
```
**Resultado:** 401 - Missing "Authorization" header

### Intento 2: OAuth Prefix
```bash
curl -X GET https://sweet.icontel.cl/api/users \
  -H "Authorization: OAuth 737812fd-2290-03a4-3dde-694a972e8788"
```
**Resultado:** 401 - Missing "Authorization" header

### Intento 3: Query Parameter
```bash
curl -X GET "https://sweet.icontel.cl/api/users?access_token=737812fd-2290-03a4-3dde-694a972e8788"
```
**Resultado:** 401 - Missing "Authorization" header

### Intento 4: POST Login
```bash
curl -X POST https://sweet.icontel.cl/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "ecerpa", "password": "test123"}'
```
**Resultado:** 401 - Missing "Authorization" header

---

## ✅ Lo que Necesitamos para Completar la Integración

### Información Mínima Requerida:

1. **Formato correcto de autenticación** con el token proporcionado
2. **Endpoint de login** con estructura request/response
3. **Endpoints de usuarios y clientes** con sus estructuras de datos
4. **Documentación oficial** de la API (si existe)

### Opcional pero Útil:

5. Colección de Postman con ejemplos
6. Ambiente de pruebas
7. Lista de códigos de error y sus significados
8. Información sobre webhooks (si existen)

---

## 📞 Contacto

Por favor, enviar la información a: [Tu Email/Contacto]

**Urgencia:** Alta - La integración está lista desde nuestro lado, solo necesitamos la información correcta de la API para completarla.

---

## 📝 Comando de Diagnóstico

Hemos creado un comando para diagnosticar la conexión:

```bash
docker exec taskflow_app_dev php artisan sweetcrm:diagnose
```

Este comando verifica:
- Configuración
- Conectividad al servidor
- Accesibilidad de endpoints
- Proporciona recomendaciones

---

**Última actualización:** 2025-12-23
**Versión:** 1.0.0
