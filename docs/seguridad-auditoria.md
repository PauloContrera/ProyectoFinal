# Seguridad, permisos y auditoria

## Roles preparados

- `superadmin`: acceso administrativo completo.
- `admin`: administra usuarios no privilegiados y heladeras, pero no puede modificar perfiles de `admin` ni `superadmin`.
- `client`: administra sus grupos, heladeras y stock.
- `visitor`: acceso de lectura. Puede ver heladeras compartidas, temperaturas, historial y stock, pero no puede crear, editar ni borrar grupos, heladeras, rangos o stock.

## Credenciales

Los cambios de `username` y `password` quedan restringidos al propio usuario. Un administrador no puede cambiar credenciales de otra cuenta desde los endpoints de usuario; para recuperación debe usarse el flujo de reset de password.

## Auditoria en base de datos

La base ya tenia logs para usuarios, accesos y heladeras. Se agrego `stock_item_change_log` para registrar:

- alta de items de stock;
- cambios de nombre, cantidad o vencimiento;
- baja de items;
- usuario que ejecuto la accion;
- heladera asociada y fecha del evento;
- `stock_item_id` historico, incluso despues de borrar el item.

La migracion esta en `Database/security_audit.sql` y el script `iniciar-temp-segura.bat` la aplica despues del seed de desarrollo.

## Observabilidad transversal

Se agrego `Database/audit_observability.sql` para registrar toda comunicacion HTTP relevante sin guardar secretos:

- `api_request_logs`: una fila por request API con `request_id`, usuario autenticado si existe, metodo, ruta, estado HTTP, duracion, IP, user-agent, body sanitizado para metodos de escritura y resumen de respuesta.
- `audit_events`: eventos de auditoria generales como excepciones, errores fatales y consultas de auditoria.
- Los campos sensibles se redactan automaticamente: `password`, `token`, `secret`, `shared_secret`, `signature`, `palabra_clave`, `authorization`, `api_key`, `mail_pass`, entre otros.
- Cada respuesta JSON incluye `request_id` y el header `X-Request-ID`, para cruzar navegador, backend, logs de archivo y base de datos.

El script `iniciar-temp-segura.bat` aplica esta migracion automaticamente despues de `security_audit.sql`.

## Endpoints de auditoria

Solo `admin` y `superadmin` pueden consultar estos endpoints:

- `GET /api/audit/requests`: requests HTTP. Filtros: `limit`, `request_id`, `method`, `path`, `status`, `user_id`.
- `GET /api/audit/events`: eventos transversales. Filtros: `limit`, `event_type`, `entity_type`, `severity`, `action`, `user_id`.
- `GET /api/audit/auth-events`: eventos historicos de autenticacion en `event_logs`. Filtros: `limit`, `event_type`, `user_id`.
- `GET /api/audit/changes`: cambios de datos en usuarios, heladeras, grupos y stock. Filtros: `limit`, `entity=all|device|group|stock|user`.
- `GET /api/audit/summary`: resumen operativo. Filtros: `hours` entre 1 y 168.

## Hardening aplicado

- `rate_limit_events`: limita login, registro, recuperacion de contrasena y endpoints ESP por IP o identidad.
- JWT: `firebase/php-jwt` actualizado a v7, validacion estricta de `Bearer`, `jti` por token y rechazo de secretos inseguros en produccion.
- CORS: solo responde `Access-Control-Allow-Origin` cuando el origen esta permitido y emite `Vary: Origin`.
- API: headers `nosniff`, `DENY` para frames, `no-store` en JSON y limite de tamano de body.
- ESP: registro con `palabra_clave`, secreto por dispositivo provisionado una sola vez, ACK idempotente y errores sin filtrado de secretos.
- Frontend: el cliente API rechaza URLs absolutas para evitar fugas accidentales del token.

## Frontend

La vista de stock usa los mismos componentes para demo y datos reales. La demo usa datos locales; la vista autenticada usa la API. En modo visitante los controles de edicion se ocultan o quedan deshabilitados.

La vista de temperatura conserva la misma UI para demo/API. En modo visitante los controladores muestran los rangos pero no permiten guardar cambios.
