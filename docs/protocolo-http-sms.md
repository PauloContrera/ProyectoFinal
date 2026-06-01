# Protocolo HTTP/SMS para ESP32

Version objetivo: `2.0`.

Este documento explica como debe comunicarse el ESP32 con la API de Temp Segura. Sirve para firmware real, simulacion en Postman y pruebas desde scripts.

## Principios

- El ESP siempre envia JSON.
- Las lecturas se envian por lotes, no una por request.
- Cada lote firmado lleva `packet_id`; si el ESP reintenta el mismo lote, debe reenviar el mismo `packet_id`.
- El backend responde con `ack.packet_id`; solo despues de recibir ACK se puede borrar el lote de la cola local.
- La hora debe ser epoch UTC en segundos.
- Para produccion usar HTTPS con validacion de certificado. En desarrollo local se puede usar HTTP.
- Si falla internet, el ESP guarda lecturas localmente y reintenta con backoff exponencial.
- Si falla el servidor primario y existe `url_backup`, el ESP intenta enviar al backup.

## Endpoints

Base local:

```text
http://localhost:8000/api
```

Endpoints:

```text
GET  /api/esp/time
POST /api/esp
POST /api/esp/register
POST /api/esp/sync
POST /api/esp/command-response
```

`POST /api/esp` despacha automaticamente:

- `accion: "registro"` -> registro inicial.
- `respuesta_comando` presente -> confirmacion de comando.
- cualquier otro payload valido -> envio de lecturas.

## Hora del servidor

Usar SNTP/NTP como primera opcion. Si no hay hora valida, pedir:

```http
GET /api/esp/time
```

Respuesta:

```json
{
  "success": true,
  "server_time": 1716020000,
  "server_time_iso": "2024-05-18T10:33:20+00:00",
  "timestamp_tolerance_seconds": 900,
  "request_id": "c0f8f4e1b651a8d1"
}
```

El ESP debe ajustar su reloj si la diferencia supera 60 segundos.

## Firma HMAC

Los endpoints `/api/esp/sync` y `/api/esp/command-response` requieren `signature`.

Formula:

```text
HMAC_SHA256(mac + timestamp + json_data, shared_secret)
```

Donde:

- `mac`: MAC normalizada en mayusculas con `:`, ejemplo `A1:B2:C3:D4:E5:01`.
- `timestamp`: epoch UTC en segundos.
- `json_data`: JSON canonico del payload sin `mac`, `timestamp` ni `signature`.
- Los objetos se ordenan alfabeticamente por clave; los arrays conservan orden.
- Se acepta firma hexadecimal o Base64.

En desarrollo:

```text
ESP_DEFAULT_SECRET=local-dev-esp-secret
ESP_ACTIVATION_KEYWORD=clavesecreta4321
ESP_REQUIRE_ACTIVATION_KEY=true
```

En protocolo v2, el registro requiere `palabra_clave`. Para dispositivos nuevos el servidor responde una sola vez con `provisioning.shared_secret`; el firmware debe guardarlo en memoria persistente y usarlo para firmar todos los sync siguientes. En produccion no usar `local-dev-esp-secret`.

Ejemplo de `json_data` canonico para sync:

```json
{"data":[{"temp":4.3,"time":1716019900}],"local_alerts":[],"optional":{"battery_level":89,"signal_strength":-67,"uptime":86400},"packet_id":"sync-1716020000-1","seq":1}
```

## Caso 1: primer encendido / registro

Request:

```json
{
  "accion": "registro",
  "mac": "A1:B2:C3:D4:E5:F6",
  "modelo": "ESP32-GSM-V3",
  "firmware_version": "2.0.0",
  "protocol_version": "2.0",
  "sim_imei": "354829071122334",
  "timestamp": 1716020000,
  "palabra_clave": "clavesecreta4321"
}
```

Respuesta:

```json
{
  "success": true,
  "server_time": 1716020000,
  "request_id": "37a8260c41d7f617",
  "estado_cuenta": true,
  "palabra_clave": "clavesecreta4321",
  "config_version": 1,
  "policy": {
    "max_batch_size": 120,
    "retry_base_seconds": 30,
    "timestamp_tolerance_seconds": 900,
    "record_max_age_seconds": 604800,
    "record_future_tolerance_seconds": 900
  },
  "mensaje": "Dispositivo registrado exitosamente",
  "config": {
    "temp_min": 2,
    "temp_max": 8,
    "grupo": "",
    "area": "ESP A1:B2:C3:D4:E5:F6",
    "telefonos": [],
    "ubicacion": "Pendiente de asignar",
    "tiempo_espera": 900,
    "url_backup": "",
    "config_version": 1,
    "protocol_version": "2.0",
    "max_batch_size": 120,
    "retry_base_seconds": 30
  },
  "provisioning": {
    "shared_secret": "64_hex_chars_generados_por_el_servidor",
    "store": "Guardar en NVS/Preferences del ESP32 y usarlo para firmar los siguientes paquetes."
  }
}
```

Accion del ESP:

- Guardar `config`.
- Si llega `provisioning.shared_secret`, guardarlo en NVS/Preferences y reemplazar el secreto de desarrollo.
- Usar `tiempo_espera` como intervalo normal de envio.
- Usar `url_backup` si el primario falla.
- Si `estado_cuenta` es `false`, no borrar lecturas locales.

## Caso 2: envio normal de lecturas

Request firmado:

```json
{
  "mac": "A1:B2:C3:D4:E5:01",
  "timestamp": 1716020000,
  "packet_id": "sync-1716020000-1",
  "seq": 1,
  "data": [
    { "temp": 4.3, "time": 1716019900 },
    { "temp": 4.6, "time": 1716019960 }
  ],
  "local_alerts": [],
  "optional": {
    "battery_level": 89,
    "signal_strength": -67,
    "uptime": 86400
  },
  "signature": "..."
}
```

Respuesta:

```json
{
  "success": true,
  "server_time": 1716020001,
  "request_id": "1059d5df78155a0b",
  "estado_cuenta": true,
  "palabra_clave": "clavesecreta4321",
  "config_version": 1,
  "policy": {
    "max_batch_size": 120,
    "retry_base_seconds": 30,
    "timestamp_tolerance_seconds": 900,
    "record_max_age_seconds": 604800,
    "record_future_tolerance_seconds": 900
  },
  "message": "2 registros insertados correctamente",
  "cambio": false,
  "duplicate": false,
  "ack": {
    "packet_id": "sync-1716020000-1",
    "status": "accepted",
    "inserted": 2,
    "duplicates": 0
  }
}
```

Accion del ESP:

- Si `ack.status` es `accepted`, borrar ese lote de la cola local.
- Si `duplicate` es `true`, tambien borrar el lote: el servidor ya lo tenia.
- Si `cambio` es `true`, aplicar `config` y confirmar por `/api/esp/command-response`.

## Caso 3: reintento del mismo lote

Si no hubo respuesta por timeout, el ESP reintenta el mismo JSON con el mismo `packet_id` y la misma `signature`.

El backend responde:

```json
{
  "success": true,
  "duplicate": true,
  "ack": {
    "packet_id": "sync-1716020000-1",
    "status": "duplicate",
    "inserted": 2,
    "duplicates": 0
  }
}
```

Accion del ESP:

- Borrar el lote local.
- No generar otro `packet_id` para el mismo lote.

## Caso 4: alerta local y SMS

El ESP manda alertas locales junto con lecturas:

```json
{
  "local_alerts": [
    { "type": "temp_high", "temp": 10.1, "time": 1716019600 },
    { "type": "power_outage", "time": 1716019700 }
  ]
}
```

Tipos recomendados:

- `temp_high`
- `temp_low`
- `power_outage`
- `sensor_error`
- `door_open`
- `gsm_sms_sent`
- `gsm_sms_failed`

Si no hay internet y la temperatura esta fuera de rango, el ESP puede enviar SMS con SIM800. Cuando vuelva internet, debe incluir una alerta `gsm_sms_sent` o `gsm_sms_failed` para que quede registro en backend.

## Caso 5: cambio de configuracion

Cuando el panel cambia rangos, telefonos, intervalo o backup, el backend sube `config_version`.

En el siguiente sync:

```json
{
  "success": true,
  "cambio": true,
  "config": {
    "temp_min": 2,
    "temp_max": 8,
    "telefonos": ["2630203044"],
    "tiempo_espera": 900,
    "url_backup": "https://backup.tempsegura.local/api/esp/sync",
    "config_version": 2
  }
}
```

Accion del ESP:

1. Aplicar la config.
2. Guardarla en memoria persistente.
3. Confirmar con `/api/esp/command-response`.

## Caso 6: confirmar comando/config

Request firmado:

```json
{
  "mac": "A1:B2:C3:D4:E5:01",
  "timestamp": 1716020500,
  "packet_id": "cmd-1716020500-2",
  "seq": 2,
  "respuesta_comando": {
    "tipo": "cambio_config",
    "estado": "ok",
    "detalle": "Parametros aplicados correctamente"
  },
  "signature": "..."
}
```

Respuesta:

```json
{
  "success": true,
  "message": "Respuesta de comando recibida",
  "duplicate": false,
  "ack": {
    "packet_id": "cmd-1716020500-2",
    "status": "accepted",
    "inserted": 1,
    "duplicates": 0
  }
}
```

## Caso 7: servidor primario caido

Orden recomendado:

1. Reintentar primario 3 veces.
2. Backoff: 1s, 2s, 4s, con jitter aleatorio.
3. Si falla y hay `url_backup`, intentar backup.
4. Si todo falla, guardar en cola local.
5. No borrar lecturas hasta recibir ACK.

La politica base del backend llega en `policy.retry_base_seconds`.

## Caso 8: cuenta deshabilitada

Respuesta:

```json
{
  "success": false,
  "error": {
    "code": "ERR_CUENTA",
    "message": "Cuenta deshabilitada temporalmente",
    "detalle": "Guardar lecturas en cola local y reintentar mas tarde."
  },
  "estado_cuenta": false,
  "retry_after_seconds": 30
}
```

Accion del ESP:

- Mantener lecturas locales.
- No enviar SMS por cuenta deshabilitada salvo emergencia de temperatura.
- Reintentar despues de `retry_after_seconds` o `tiempo_espera`.

## Errores

Formato:

```json
{
  "success": false,
  "server_time": 1716020000,
  "request_id": "c0f8f4e1b651a8d1",
  "error": {
    "code": "ERR_FIRMA",
    "message": "Firma no valida o ausente",
    "detalle": "Recalcular HMAC_SHA256(mac + timestamp + json_data)."
  },
  "retry_after_seconds": 30,
  "estado_cuenta": true
}
```

Los errores no devuelven `palabra_clave` ni `shared_secret`. El firmware debe tratar esos valores como secretos locales.

Codigos:

- `ERR_CUENTA`: cuenta deshabilitada.
- `ERR_FIRMA`: firma invalida o ausente.
- `ERR_TIMESTAMP`: timestamp fuera de tolerancia.
- `ERR_FORMATO`: JSON o campos invalidos.
- `ERR_DISPOSITIVO`: MAC no registrada.
- `ERR_ACTIVACION`: `palabra_clave` ausente o incorrecta durante registro.
- `ERR_PACKET_ID`: `packet_id` invalido.
- `ERR_REPLAY`: `packet_id` repetido con contenido distinto.
- `ERR_BATCH_GRANDE`: lote mayor a `policy.max_batch_size`.
- `ERR_DESCONOCIDO`: error interno.
- HTTP `429`: rate limit; respetar `Retry-After` y aplicar backoff.

## Base de datos

Aplicar:

```bat
cmd /c "mysql -u root temp_segura < Database\protocol_http_sms.sql"
cmd /c "mysql -u root temp_segura < Database\security_audit.sql"
cmd /c "mysql -u root temp_segura < Database\audit_observability.sql"
```

El script `iniciar-temp-segura.bat` ya aplica estas migraciones antes de levantar los servidores.

Tablas agregadas:

- `esp_sync_batches`: idempotencia, ACK y antireplay por `packet_id`.
- `esp_local_alerts`
- `esp_diagnostics`
- `esp_command_responses`
- `rate_limit_events`: rate limiting de login, recuperacion de contrasena y endpoints ESP.

Columnas agregadas a `devices`:

- `mac_address`
- `shared_secret`
- `account_enabled`
- `activation_keyword`
- `sms_phones`
- `send_interval_seconds`
- `retry_base_seconds`
- `max_batch_size`
- `backup_url`
- `config_version`
- `last_config_version_sent`
- `registered_model`
- `sim_imei`
- `protocol_version`
- `last_sync_at`
- `last_sequence`
- `last_packet_id`
- `last_packet_at`

## Firmware de referencia

Ver:

```text
ESP32/temp_segura_protocol/temp_segura_protocol.ino
```

Ese sketch muestra:

- sincronizacion de hora por SNTP y fallback `/api/esp/time`;
- firma HMAC-SHA256;
- `packet_id` estable por lote;
- reintentos con backoff;
- uso de `url_backup`;
- aplicacion de configuracion remota.

## Pruebas

Con backend corriendo:

```bat
php Backend\tests\protocol_http_sms_test.php
```

La prueba:

1. Crea un ESP temporal por `/api/esp/register`.
2. Envia lecturas firmadas por `/api/esp/sync`.
3. Reenvia el mismo lote y verifica idempotencia.
4. Verifica rechazo de firma invalida.
5. Confirma un comando por `/api/esp/command-response`.
6. Verifica registros en DB.
7. Limpia el dispositivo temporal.

## Referencias tecnicas

- Espressif ESP-TLS: validacion de certificado, SNI y TLS para HTTPS.
- Espressif ESP HTTP Client: cliente HTTP/S oficial para ESP-IDF.
- Espressif System Time/SNTP: sincronizacion de hora del dispositivo.
- OWASP API Security: autenticacion, autorizacion, validacion y antireplay.
