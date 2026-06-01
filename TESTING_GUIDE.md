# Guia de testing - Temp Segura

Esta guia reemplaza los endpoints viejos por el flujo actual de la API.

## Backend

1. Levantar la base, migraciones y servidores:

```bat
iniciar-temp-segura.bat
```

2. Ejecutar la suite integral:

```bat
test-backend.bat
```

La suite ejecuta:

- `Backend/tests/backend_roles_flow_test.php`
- `Backend/tests/protocol_http_sms_test.php`

Documentacion completa: `docs/pruebas-backend-integral.md`.

## Postman manual

Coleccion recomendada:

```text
temp-segura-backend-completo.postman_collection.json
```

Esta coleccion no usa scripts para tokens. Importala en Postman, ejecuta los login de cada rol y copia manualmente `data.token` a estas variables:

- `superadmin_token`
- `admin_token`
- `client_token`
- `visitor_token`

Tambien copia manualmente los IDs devueltos por los POST a `managed_client_id`, `managed_visitor_id`, `group_id`, `device_id` y `stock_id`. Incluye requests positivos, CRUD completo, permisos, sin token, token invalido, token vencido y validaciones negativas.

## Que cubre

- Login de `superadmin`, `admin`, `client` y `visitor`.
- Permisos de usuarios administrativos y no administrativos.
- CRUD de grupos para usuarios con permisos.
- Alta y edicion de heladeras.
- Lectura de historial de temperaturas con consistencia de ultima lectura.
- CRUD de stock.
- Vista solo lectura para visitante.
- Logs en `api_request_logs`.
- Eventos en `audit_events`.
- Endpoints administrativos `/api/audit/*`.
- Simulacion ESP32 con registro, HMAC, sync, idempotencia y respuesta de comandos.

## URLs actuales

Backend local:

```text
http://127.0.0.1:8000/api
```

Frontend local:

```text
http://127.0.0.1:5173
```

## Variables utiles

```bat
set BACKEND_TEST_BASE_URL=http://127.0.0.1:8000/api
set ESP_TEST_BASE_URL=http://127.0.0.1:8000/api
```

## Endpoints principales

Autenticacion:

```text
POST /api/register
POST /api/login
GET  /api/verify-email
POST /api/request-password-reset
POST /api/reset-password
```

Usuarios:

```text
GET    /api/users
POST   /api/users
GET    /api/users/{id}
PUT    /api/users/{id}
DELETE /api/users/{id}
PUT    /api/users/{id}/admin
PUT    /api/users/{id}/change-password
PUT    /api/users/{id}/change-username
```

Heladeras, grupos y stock:

```text
GET    /api/devices
POST   /api/devices
GET    /api/devices/{id}
PUT    /api/devices/{id}
DELETE /api/devices/{id}
POST   /api/devices/{id}/grant-access
POST   /api/devices/{id}/revoke-access
POST   /api/devices/{id}/assign-group
GET    /api/devices/{id}/temperatures
GET    /api/devices/{id}/stock
POST   /api/devices/{id}/stock
PUT    /api/stock/{id}
DELETE /api/stock/{id}
GET    /api/device-groups
POST   /api/device-groups
PUT    /api/device-groups/{id}
DELETE /api/device-groups/{id}
```

Auditoria:

```text
GET /api/audit/requests
GET /api/audit/events
GET /api/audit/auth-events
GET /api/audit/changes
GET /api/audit/summary
```

ESP32:

```text
GET  /api/esp/time
POST /api/esp
POST /api/esp/register
POST /api/esp/sync
POST /api/esp/command-response
```

## Resultado esperado

Una corrida correcta debe cerrar con:

```text
OK suite backend completa.
```
