# 🧪 GUÍA DE TESTING - TEMP SEGURA
## Backend API + Frontend Integration

---

## 📋 PARTE 1: TESTING DEL BACKEND

### Opción A: Testing Automático (Script Batch)

**Ubicación:** `C:\Dev\projects\Temp Segura\ProyectoFinal\test-backend.bat`

**Ejecución:**
```bash
cd C:\Dev\projects\Temp Segura\ProyectoFinal
test-backend.bat
```

**¿Qué prueba?**
- ✅ Registro de usuario (POST /auth/register)
- ✅ Login (POST /auth/login)
- ✅ Obtener dispositivos (GET /devices con JWT)

**Resultado esperado:**
```
[TEST 1] Registrando nuevo usuario...
Response: {"success":true,"status":201,...}

[TEST 2] Iniciando sesión (Login)...
Response: {"success":true,"status":200,"data":{"token":"eyJ...",...}}

[TEST 3] Accediendo a dispositivos...
Response: {"success":true,"status":200,"data":[...]}
```

---

### Opción B: Testing con Postman (Recomendado)

**Importar Collection:**

1. Abre Postman: https://www.postman.com/downloads/
2. Click en **Import** (esquina superior izquierda)
3. Selecciona: `C:\Dev\projects\Temp Segura\ProyectoFinal\temp-segura-postman.json`
4. Collection "Temp Segura API" se importa automáticamente

**Variables Postman:**

Edita las variables en la collection:

```
api_url     = http://localhost:8000/api
token       = [obtén este valor haciendo login]
```

**Endpoints para probar:**

#### 1. Registrar Usuario
```
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "name": "Test User",
  "username": "testuser123",
  "email": "test@example.com",
  "password": "Test1234",
  "phone": "+541112345678"
}
```

**Respuesta esperada:**
```json
{
  "success": true,
  "status": 201,
  "message": "Usuario registrado exitosamente",
  "data": {
    "user_id": 1
  },
  "timestamp": "2026-05-25 12:00:00"
}
```

#### 2. Hacer Login
```
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "Test1234"
}
```

**Respuesta esperada:**
```json
{
  "success": true,
  "status": 200,
  "message": "Login exitoso",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "name": "Test User",
      "username": "testuser123",
      "email": "test@example.com",
      "role": "cliente",
      "is_verified": false
    }
  },
  "timestamp": "2026-05-25 12:00:00"
}
```

**⚠️ IMPORTANTE:** Copia el valor de `token` y pégalo en la variable `token` de Postman

#### 3. Obtener Dispositivos (Con JWT)
```
GET http://localhost:8000/api/devices
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Respuesta esperada:**
```json
{
  "success": true,
  "status": 200,
  "message": "Dispositivos obtenidos",
  "data": [],
  "timestamp": "2026-05-25 12:00:00"
}
```

#### 4. Crear Dispositivo
```
POST http://localhost:8000/api/devices
Authorization: Bearer [TOKEN]
Content-Type: application/json

{
  "device_code": "DEV001",
  "name": "Heladera Principal",
  "location": "Cocina",
  "max_temp": 25,
  "min_temp": 0
}
```

---

### Testing Manual con cURL

**Si prefieres línea de comandos:**

#### Registro:
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Test User",
    "username":"testuser123",
    "email":"test@example.com",
    "password":"Test1234",
    "phone":"+541112345678"
  }'
```

#### Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email":"test@example.com",
    "password":"Test1234"
  }'
```

#### Obtener Dispositivos (con JWT):
```bash
curl -X GET http://localhost:8000/api/devices \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 📊 PARTE 2: TESTING DEL FRONTEND

### Opción 1: Login Page Mejorada

**Ubicación:** `C:\Dev\projects\Temp Segura\ProyectoFinal\Frontend\src\pages\LoginPage.tsx`

**URL:** `http://localhost:5173` (si está disponible)

**Pasos para probar:**

1. **Abre el navegador** en `http://localhost:5173`

2. **Verifica que el Login Page cargue** con:
   - ✅ Input de Email
   - ✅ Input de Contraseña
   - ✅ Botón "Iniciar sesión"
   - ✅ Toggle a "Crear cuenta"

3. **Intenta registrar:**
   - Email: `test123@example.com`
   - Username: `testuser456`
   - Contraseña: `Test1234`
   - Teléfono: `+541112345678`
   - Click en "Crear cuenta"

4. **Verifica el resultado:**
   - ✅ Sin errores en consola
   - ✅ Se debería redirigir a dashboard
   - ✅ Token guardado en localStorage

5. **Intenta login:**
   - Email: `test123@example.com`
   - Contraseña: `Test1234`
   - Click en "Iniciar sesión"

---

### Opción 2: Verificar Comunicación Backend-Frontend

**Pasos:**

1. Abre **Chrome DevTools** (F12)
2. Ve a **Network** tab
3. Intenta login
4. **Verifica:**
   - ✅ Request a `POST http://localhost:8000/api/auth/login`
   - ✅ Status 200 OK
   - ✅ Response JSON con token
   - ✅ Headers contienen `Authorization: Bearer`

**Verificar localStorage:**

Abre DevTools → **Application** → **Local Storage**

Deberías ver:
```
token: "eyJ0eXAiOiJKV1QiLCJhbGc..."
user: {"id":1,"name":"Test User",...}
```

---

## ✅ CHECKLIST DE TESTING

### Backend
- [ ] Servidor PHP corriendo en `http://localhost:8000`
- [ ] MySQL conectado y BD creada
- [ ] POST `/auth/register` funciona
- [ ] POST `/auth/login` retorna token
- [ ] GET `/devices` requiere JWT
- [ ] Errores retornan formato JSON correcto
- [ ] CORS headers presentes
- [ ] Logs se crean en `backend/logs/`

### Frontend
- [ ] Servidor Vite corriendo en `http://localhost:5173`
- [ ] LoginPage carga correctamente
- [ ] Validación de formulario funciona
- [ ] Errores se muestran en UI
- [ ] Submit envía request a backend
- [ ] Token se guarda en localStorage
- [ ] Redirección a dashboard funciona
- [ ] No hay errores en consola

### Integración
- [ ] Frontend envía requests a backend
- [ ] JWT se incluye en headers
- [ ] Errores del backend se muestran en frontend
- [ ] Sesión se mantiene después de reload
- [ ] Logout limpia localStorage

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Error: "Network request failed"
```
Causa: Backend no está corriendo
Solución: Verifica que php -S localhost:8000 -t public está ejecutándose
```

### Error: "CORS policy blocked"
```
Causa: CORS headers no configurados
Solución: Verifica que ALLOWED_ORIGINS en .env incluye http://localhost:5173
```

### Error: "Cannot POST /api/auth/login"
```
Causa: Ruta no existe en backend
Solución: Verifica que routes/api.php incluye las rutas de autenticación
```

### Error: "Database connection failed"
```
Causa: MySQL no conecta
Solución: 
  1. Verifica MySQL está corriendo
  2. Credenciales en .env son correctas
  3. Base de datos temp_segura existe
```

### Token inválido después de login
```
Causa: JWT_SECRET diferente entre requests
Solución: Verifica que JWT_SECRET es el mismo en .env
```

### Errores de validación en frontend
```
Causa: Validación diferente en cliente y servidor
Solución: Usa los validadores de Validator.php en el backend
```

---

## 📈 PRUEBAS DE CARGA

**Para probar performance con múltiples requests:**

```bash
# Instala ApacheBench (si no lo tienes)
# Windows: https://httpd.apache.org/docs/2.4/programs/ab.html

# Test GET /devices (sin JWT, fallará)
ab -n 100 -c 10 http://localhost:8000/api/devices

# Test POST /auth/register
ab -n 50 -c 5 -T application/json \
  -p register.json \
  http://localhost:8000/api/auth/register
```

---

## 📊 OBSERVAR LOGS

**Backend logs:**

```bash
# Ver logs en tiempo real
tail -f C:\Dev\projects\Temp Segura\ProyectoFinal\backend\logs\app.log

# Ver solo errores
tail -f C:\Dev\projects\Temp Segura\ProyectoFinal\backend\logs\errors.log

# Ver logs de seguridad
tail -f C:\Dev\projects\Temp Segura\ProyectoFinal\backend\logs\security.log
```

**Frontend console:**

Abre DevTools (F12) → **Console** tab

Deberías ver:
```
✓ Token validado successfully
✓ User autenticado
```

No deberías ver:
```
❌ CORS error
❌ Network failure
❌ 401 Unauthorized
```

---

## 🎯 FLUJO DE TESTING RECOMENDADO

### 1. Configuración Inicial (5 min)
- [ ] Backend en puerto 8000
- [ ] Frontend en puerto 5173
- [ ] MySQL corriendo
- [ ] `.env` files configurados

### 2. Testing Backend (10 min)
- [ ] Ejecuta `test-backend.bat`
- [ ] Verifica respuestas en consola
- [ ] Importa Postman collection
- [ ] Prueba cada endpoint

### 3. Testing Frontend (10 min)
- [ ] Abre `http://localhost:5173`
- [ ] Intenta registrar
- [ ] Intenta login
- [ ] Verifica localStorage

### 4. Integración (5 min)
- [ ] Login redirects a dashboard
- [ ] Token se mantiene
- [ ] API requests funcionan
- [ ] Errores se muestran

### 5. Logs y Debugging (5 min)
- [ ] Revisa backend logs
- [ ] Revisa browser console
- [ ] Verifica network requests
- [ ] Documenta problemas

**Total: ~35 minutos**

---

## 📝 TEMPLATE PARA REPORTAR BUGS

Si encuentras un problema, reporta así:

```
TÍTULO: [BUG] Login falla con email inválido

DESCRIPCIÓN:
Cuando intento hacer login con email inválido, 
el frontend no muestra mensaje de error.

PASOS PARA REPRODUCIR:
1. Abre http://localhost:5173
2. Escribe "email_invalido" en el campo Email
3. Escribe cualquier contraseña
4. Click en Iniciar sesión

RESULTADO ESPERADO:
Se muestra error: "Email inválido"

RESULTADO ACTUAL:
Se envía request al backend (error innecesario)

LOGS:
[Copia los logs relevantes aquí]

BROWSER CONSOLE:
[Copia errores de consola aquí]

NETWORK:
[Status del request a /auth/login]
```

---

## ✨ SIGUIENTES PASOS DESPUÉS DE TESTING

1. ✅ Backend testing completado
2. ✅ Frontend login funciona
3. ⏳ Crear componentes del dashboard
4. ⏳ Implementar CRUD de dispositivos
5. ⏳ Agregar gráficos de temperatura
6. ⏳ Sistema de alertas
7. ⏳ Integración ESP32

---

**¿Preguntas sobre testing? Consulta los logs y DevTools** 🔍

