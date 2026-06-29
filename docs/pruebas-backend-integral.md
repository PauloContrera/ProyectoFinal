# Pruebas integrales de backend

Esta guia documenta como validar el backend simulando los roles reales de la aplicacion y el ESP32. La idea es que cualquier persona del equipo pueda levantar el entorno, ejecutar una suite repetible y entender que contrato se esta probando.

## Requisitos

- MySQL corriendo con la base configurada en `Backend/.env`.
- Dependencias PHP instaladas en `Backend/vendor`.
- Migraciones aplicadas:
  - `Database/schema.sql`
  - `Database/protocol_http_sms.sql`
  - `Database/security_audit.sql`
  - `Database/audit_observability.sql`
- Backend escuchando en `http://127.0.0.1:8000/api` o una URL equivalente.

Para preparar todo desde cero se puede usar:

```bat
iniciar-temp-segura.bat
```

## Comandos

Levantar solo el backend:

```bat
cd /d "C:\Dev\projects\Temp Segura\ProyectoFinal\Backend"
php -S 127.0.0.1:8000 -t public
```

Ejecutar toda la suite:

```bat
cd /d "C:\Dev\projects\Temp Segura\ProyectoFinal"
test-backend.bat
```

Ejecutar manualmente desde PHP:

```bat
cd /d "C:\Dev\projects\Temp Segura\ProyectoFinal\Backend"
php tests\run_backend_suite.php
```

Usar otra URL:

```bat
set BACKEND_TEST_BASE_URL=http://127.0.0.1:8000/api
set ESP_TEST_BASE_URL=http://127.0.0.1:8000/api
php tests\run_backend_suite.php
```

## Archivos de prueba

- `Backend/tests/access_control_security_test.php`: crea dos clientes, dos visitantes, admin y superadmin para intentar saltos IDOR con IDs validos de otros usuarios. Verifica que el cliente vea solo sus datos, que admin pueda editar, que visitor sea solo lectura y que pueda leer multiples productos si tiene acceso explicito.
- `Backend/tests/backend_roles_flow_test.php`: crea usuarios temporales y prueba roles, permisos, grupos, heladeras, historial de temperaturas, stock y auditoria.
- `Backend/tests/protocol_http_sms_test.php`: simula un ESP32 por HTTP, prueba registro, palabra clave de activacion, HMAC, sync idempotente, firma invalida, comandos y persistencia en base.
- `Backend/tests/run_backend_suite.php`: runner que ejecuta todos los tests en orden.

## Datos temporales

La suite crea usuarios con prefijos `acltest_` y `flowtest_`, heladeras con prefijos `ACL-` y `FLOWT-`, grupos, temperaturas y stock. Al finalizar elimina los datos operativos temporales para no contaminar la demo ni los usuarios reales.

Los logs de auditoria quedan guardados a proposito. Esa persistencia permite comprobar que el backend registra requests, eventos y cambios aunque los datos temporales se limpien.

## Flujo por usuarios

| Rol | Que se prueba |
| --- | --- |
| `superadmin` | Login, listado de usuarios y lectura de endpoints de auditoria. |
| `admin` | Creacion de visitantes, edicion administrativa de usuarios no privilegiados y bloqueo al intentar crear otro admin. |
| `client` | Bloqueo de listado de usuarios, CRUD de grupos, alta/edicion de heladera, historial de temperaturas, CRUD de stock y baja de grupo sin heladeras asociadas. |
| `visitor` | Login, lectura de heladeras compartidas, lectura de temperaturas y stock, bloqueo de edicion de heladera, grupos y stock. |

## Seguridad ACL/IDOR

El test `access_control_security_test.php` arma datos de dos clientes distintos y despues prueba ataques con IDs reales del otro dueno:

- `client A` no puede listar, leer, editar, borrar ni compartir heladeras, grupos, temperaturas o stock de `client B`.
- `client B` no puede ver recursos de `client A`.
- `visitor A` puede leer dos heladeras compartidas y varios productos de esas heladeras, pero no puede modificar nada aunque el request de grant envie `can_modify=true`.
- `visitor B` no ve recursos si no tiene acceso explicito.
- `admin` puede listar y editar heladeras, grupos, stock y datos de usuarios clientes.
- Los intentos denegados quedan registrados en `api_request_logs` y los cambios permitidos quedan en `audit_events`.

## Flujo ESP32

El test del ESP ejecuta estos pasos:

1. Intenta registrar sin `palabra_clave` y espera `401 ERR_ACTIVACION`.
2. Registra el dispositivo con `palabra_clave` valida.
3. Valida que el backend devuelva `config` y `provisioning.shared_secret`.
4. Firma un lote con HMAC SHA-256 y lo envia a `/api/esp/sync`.
5. Reenvia el mismo `packet_id` para comprobar idempotencia.
6. Envia una firma invalida y espera `401 ERR_FIRMA`.
7. Envia una respuesta de comando.
8. Verifica en base registros de temperaturas, alertas locales, diagnosticos, comandos y batches.

## Auditoria validada

La prueba verifica que:

- Todas las respuestas JSON principales incluyan `request_id`.
- `api_request_logs` registre los logins y requests ejecutados.
- `audit_events` registre cambios operativos de heladeras, grupos y stock.
- Solo `admin` y `superadmin` puedan leer `/api/audit/*`.
- Usuarios sin privilegios reciban `403` al consultar auditoria administrativa.

## Resultado esperado

Una corrida correcta termina con:

```text
OK seguridad ACL/IDOR por roles verificada.
OK flujo integral de usuarios, permisos, stock, temperaturas y auditoria verificado.
OK protocolo HTTP/SMS verificado por HTTP y DB
OK suite backend completa.
```

## Problemas comunes

`No se pudo conectar con http://127.0.0.1:8000/api`

El backend no esta corriendo o la URL no coincide. Levantar el servidor o configurar `BACKEND_TEST_BASE_URL`.

`DB_NAME no esta configurado`

Revisar `Backend/.env`.

`SQLSTATE[42S02] Table not found`

Falta aplicar migraciones. Ejecutar `iniciar-temp-segura.bat` o importar los SQL indicados en requisitos.

`EMAIL_NOT_VERIFIED`

No deberia pasar en esta suite porque los usuarios de prueba se crean verificados. Si aparece, revisar que la tabla `email_verifications` exista y tenga el esquema actual.
