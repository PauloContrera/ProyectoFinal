# Protocolo de comunicacion ESP32 v2.1

Este documento define como debe comunicarse un ESP32 con Temp Segura usando HTTP como canal principal y SMS como fallback operativo cuando no hay internet. La version `2.1` mantiene compatibilidad con los endpoints actuales y formaliza los casos de uso, errores y acciones esperadas del firmware.

## Objetivos

- Asegurar que cada lectura llegue una sola vez o sea reconocida como duplicada.
- Evitar replay y manipulacion de payloads con HMAC-SHA256.
- Permitir que el ESP trabaje offline con cola local.
- Separar mediciones, alertas locales, diagnostico y respuestas de comandos.
- Devolver instrucciones claras al firmware ante errores, cambios de configuracion o cuenta deshabilitada.
- Dejar trazabilidad en `api_request_logs`, `audit_events`, `esp_sync_batches`, `esp_local_alerts`, `esp_diagnostics`, `esp_command_responses` y `temperatures`.

## Canales

| Canal | Uso | Requisito |
| --- | --- | --- |
| HTTPS | Produccion. Canal principal para registro, sync y comandos. | Validar certificado y host. |
| HTTP | Desarrollo local. | Solo en LAN/local. |
| SMS | Fallback de emergencia cuando no hay internet. | No reemplaza el sync; al recuperar internet se reporta el evento. |

En produccion el ESP debe usar HTTPS. Si el servidor primario no responde y existe `url_backup`, el ESP intenta el backup antes de conservar el lote en cola.

## Endpoints

Base local:

```text
http://127.0.0.1:8000/api
```

| Metodo | Endpoint | Firma | Uso |
| --- | --- | --- | --- |
| `GET` | `/api/esp/time` | No | Sincronizar hora cuando SNTP/NTP falla. |
| `POST` | `/api/esp/register` | No | Alta/provisioning inicial con `palabra_clave`. |
| `POST` | `/api/esp/sync` | Si | Enviar lecturas, alertas locales y diagnostico. |
| `POST` | `/api/esp/command-response` | Si | Confirmar comandos o configuracion aplicada. |
| `POST` | `/api/esp` | Segun caso | Entrada unica: despacha registro, sync o comando. |

## Identidad del dispositivo

Cada ESP queda identificado por `mac` normalizada:

```text
AA:BB:CC:DD:EE:01
```

Reglas:

- Aceptar entrada con o sin `:`, pero normalizar en mayusculas con separador `:`.
- El servidor guarda `mac_address` como valor unico.
- El backend crea `device_code` automatico `ESP-<MAC_COMPACTA>` si el dispositivo no estaba preprovisionado.
- Si el admin preprovisiona una heladera con `mac_address`, el registro del ESP actualiza firmware/modelo y usa esa heladera.

## Seguridad

### Activacion

El registro requiere:

```json
{
  "palabra_clave": "clavesecreta4321"
}
```

La palabra clave solo sirve para el alta inicial. No se debe usar para firmar lecturas.

### Secreto por dispositivo

En un registro nuevo, el backend devuelve una sola vez:

```json
{
  "provisioning": {
    "shared_secret": "64_hex_chars"
  }
}
```

El firmware debe guardarlo en NVS/Preferences y usarlo para firmar `sync` y `command-response`.

Si se pierde el secreto:

1. El ESP debe dejar de enviar sync.
2. Debe solicitar reprovisionamiento operativo.
3. Un admin puede crear/recrear el dispositivo o reactivar el provisioning segun el flujo que se defina para produccion.

### Firma HMAC

Formula:

```text
HMAC_SHA256(mac + timestamp + json_data, shared_secret)
```

`json_data` es el JSON canonico del payload sin `mac`, `timestamp` ni `signature`.

Reglas canonicas:

- Objetos ordenados alfabeticamente por clave.
- Arrays conservan orden.
- JSON sin escapes innecesarios.
- Se acepta firma hexadecimal o Base64.
- `timestamp` es epoch UTC en segundos.

Ejemplo de `json_data` para sync:

```json
{"data":[{"temp":4.3,"time":1780344957}],"local_alerts":[],"optional":{"battery_level":89,"signal_strength":-67,"uptime":86400},"packet_id":"sync-001","seq":1}
```

## Envelope de respuesta

Respuesta exitosa comun:

```json
{
  "success": true,
  "server_time": 1780345017,
  "request_id": "c0f8f4e1b651a8d1",
  "estado_cuenta": true,
  "config_version": 1,
  "policy": {
    "max_batch_size": 120,
    "retry_base_seconds": 30,
    "timestamp_tolerance_seconds": 900,
    "record_max_age_seconds": 604800,
    "record_future_tolerance_seconds": 900
  }
}
```

Respuesta de error comun:

```json
{
  "success": false,
  "server_time": 1780345017,
  "request_id": "c0f8f4e1b651a8d1",
  "error": {
    "code": "ERR_FIRMA",
    "message": "Firma no valida o ausente",
    "detalle": "Recalcular HMAC_SHA256(mac + timestamp + json_data)."
  },
  "retry_after_seconds": 30,
  "estado_cuenta": true,
  "policy": {
    "max_batch_size": 120,
    "retry_base_seconds": 30,
    "timestamp_tolerance_seconds": 900,
    "record_max_age_seconds": 604800,
    "record_future_tolerance_seconds": 900
  }
}
```

El backend nunca debe devolver `shared_secret` ni `palabra_clave` en errores.

## Casos operativos

### Caso 1: arranque y sincronizacion de hora

Orden recomendado:

1. Intentar SNTP/NTP.
2. Si falla, pedir `GET /api/esp/time`.
3. Si la diferencia supera 60 segundos, ajustar reloj.
4. No registrar ni enviar sync si la hora no es confiable.

Request:

```http
GET /api/esp/time
```

Accion ante error:

- Si no hay internet, continuar midiendo y conservar cola local.
- Reintentar con backoff.

### Caso 2: registro inicial

Request:

```json
{
  "accion": "registro",
  "mac": "AA:BB:CC:DD:EE:01",
  "modelo": "ESP32-GSM-V3",
  "firmware_version": "2.1.0",
  "protocol_version": "2.1",
  "sim_imei": "354829071122334",
  "timestamp": 1780345017,
  "palabra_clave": "clavesecreta4321"
}
```

Respuesta:

```json
{
  "success": true,
  "estado_cuenta": true,
  "config_version": 1,
  "config": {
    "temp_min": 2,
    "temp_max": 8,
    "grupo": "",
    "area": "ESP AA:BB:CC:DD:EE:01",
    "telefonos": [],
    "ubicacion": "Pendiente de asignar",
    "tiempo_espera": 900,
    "url_backup": "",
    "config_version": 1,
    "protocol_version": "2.1",
    "max_batch_size": 120,
    "retry_base_seconds": 30
  },
  "provisioning": {
    "shared_secret": "64_hex_chars",
    "store": "Guardar en NVS/Preferences del ESP32 y usarlo para firmar los siguientes paquetes."
  }
}
```

Accion del ESP:

- Guardar `shared_secret` si llega.
- Guardar `config`.
- Programar el intervalo con `tiempo_espera`.
- No borrar cola local por registrarse; solo se borra con ACK de sync.

### Caso 3: sync normal

Request firmado:

```json
{
  "mac": "AA:BB:CC:DD:EE:01",
  "timestamp": 1780345017,
  "packet_id": "sync-1780345017-1",
  "seq": 1,
  "data": [
    { "temp": 4.3, "time": 1780344957 },
    { "temp": 4.6, "time": 1780344987 }
  ],
  "local_alerts": [],
  "optional": {
    "uptime": 86400,
    "signal_strength": -67,
    "battery_level": 89
  },
  "signature": "..."
}
```

Respuesta:

```json
{
  "success": true,
  "message": "2 registros insertados correctamente",
  "cambio": false,
  "duplicate": false,
  "ack": {
    "packet_id": "sync-1780345017-1",
    "status": "accepted",
    "inserted": 2,
    "duplicates": 0
  }
}
```

Accion del ESP:

- Borrar el lote local solo si `ack.status` es `accepted` o `duplicate`.
- Si `cambio=true`, aplicar `config`, persistirla y confirmar por `/api/esp/command-response`.

### Caso 4: reintento idempotente

Si hubo timeout o corte de red, reenviar exactamente el mismo payload:

- mismo `packet_id`;
- misma `seq`;
- mismo `data`;
- misma `signature`.

Respuesta esperada:

```json
{
  "success": true,
  "duplicate": true,
  "ack": {
    "packet_id": "sync-1780345017-1",
    "status": "duplicate"
  }
}
```

Accion del ESP:

- Borrar el lote local.
- No crear un `packet_id` nuevo para el mismo lote.

### Caso 5: replay conflictivo

Si se reutiliza un `packet_id` con otro contenido, el backend responde:

```json
{
  "success": false,
  "error": {
    "code": "ERR_REPLAY"
  }
}
```

Accion del ESP:

- No borrar cola si el lote real no fue confirmado.
- Marcar error de firmware si el `packet_id` se reutilizo incorrectamente.

### Caso 6: cola offline

Cuando no hay internet:

1. Medir y guardar lecturas en memoria persistente.
2. Mantener orden por `time`.
3. Agrupar hasta `policy.max_batch_size`.
4. Usar `packet_id` estable por lote.
5. Reintentar con backoff y jitter.
6. Borrar lote solo al recibir ACK.

Recomendacion de `packet_id`:

```text
sync-<mac_compacta>-<primer_time>-<seq>
```

### Caso 7: alertas locales y SMS

`local_alerts` permite reportar eventos detectados por firmware:

```json
{
  "local_alerts": [
    { "type": "temp_high", "temp": 10.1, "time": 1780344987 },
    { "type": "power_outage", "time": 1780344990 },
    { "type": "gsm_sms_sent", "phone": "+5492610000000", "time": 1780345000 }
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
- `boot`
- `config_applied`

Si se envia SMS por emergencia, el ESP debe reportarlo cuando vuelva internet para que quede auditado.

### Caso 8: diagnostico

El bloque `optional` registra telemetria operativa:

```json
{
  "optional": {
    "uptime": 86400,
    "signal_strength": -67,
    "battery_level": 89,
    "free_heap": 120000,
    "wifi_rssi": -62,
    "reset_reason": "power_on"
  }
}
```

El backend guarda los campos conocidos y conserva el payload completo.

### Caso 9: cambio de configuracion

Cuando el backend responde:

```json
{
  "cambio": true,
  "config": {
    "temp_min": 2,
    "temp_max": 8,
    "telefonos": ["+5492610000000"],
    "tiempo_espera": 900,
    "url_backup": "https://backup.tempsegura.local/api",
    "config_version": 2
  }
}
```

El ESP debe:

1. Validar valores.
2. Persistir config.
3. Aplicar intervalos y rangos.
4. Responder con `command-response`.

### Caso 10: respuesta de comando

Request firmado:

```json
{
  "mac": "AA:BB:CC:DD:EE:01",
  "timestamp": 1780345017,
  "packet_id": "cmd-1780345017-1",
  "seq": 2,
  "respuesta_comando": {
    "tipo": "cambio_config",
    "estado": "ok",
    "detalle": "Parametros aplicados correctamente"
  },
  "signature": "..."
}
```

Valores de `estado`:

- `ok`
- `error`
- `parcial`
- `ignorado`

### Caso 11: cuenta deshabilitada

Respuesta:

```json
{
  "success": false,
  "estado_cuenta": false,
  "error": {
    "code": "ERR_CUENTA"
  },
  "retry_after_seconds": 30
}
```

Accion del ESP:

- Mantener cola local.
- No borrar lecturas.
- Reintentar luego de `retry_after_seconds`.
- No saturar el backend.

### Caso 12: backup URL

Si falla el primario:

1. Reintentar primario hasta 3 veces.
2. Esperar `retry_base_seconds` con jitter.
3. Intentar `config.url_backup` si existe.
4. Si backup confirma ACK, borrar lote.
5. Si backup falla, conservar cola.

### Caso 13: rate limit

HTTP `429`:

```json
{
  "success": false,
  "status": 429,
  "data": {
    "retry_after_seconds": 300
  }
}
```

Accion del ESP:

- Respetar `Retry-After` si viene en header.
- Si no esta, usar `policy.retry_base_seconds`.
- Agregar jitter para evitar reintentos simultaneos.

## Codigos de error

| Codigo | HTTP | Causa | Accion del ESP |
| --- | --- | --- | --- |
| `ERR_ACTIVACION` | 401 | `palabra_clave` ausente o incorrecta. | Revisar provisioning. No reenviar en bucle. |
| `ERR_FIRMA` | 401 | HMAC ausente o invalido. | Recalcular firma, validar secreto y canonical JSON. |
| `ERR_TIMESTAMP` | 400 | Hora fuera de tolerancia. | Sincronizar por SNTP o `/esp/time`. |
| `ERR_FORMATO` | 400 | JSON/campos invalidos. | Corregir firmware o payload. |
| `ERR_DISPOSITIVO` | 404 | MAC no registrada. | Registrar o preprovisionar. |
| `ERR_PACKET_ID` | 400 | `packet_id` invalido. | Regenerar con caracteres permitidos. |
| `ERR_REPLAY` | 409 | `packet_id` repetido con otro contenido. | No reutilizar IDs. Revisar cola. |
| `ERR_BATCH_GRANDE` | 413 | Lote excede limite. | Partir en lotes menores. |
| `ERR_CUENTA` | 403 | Dispositivo deshabilitado. | Mantener cola y reintentar mas tarde. |

## Reglas de validacion

- `timestamp` debe estar dentro de `policy.timestamp_tolerance_seconds`.
- Cada lectura debe tener `temp` y `time` numericos.
- `temp` valida: `-50` a `80`.
- `time` no puede ser futuro mas alla de `record_future_tolerance_seconds`.
- `time` no puede ser mas viejo que `record_max_age_seconds`.
- `packet_id`: 1 a 80 caracteres, solo letras, numeros, `.`, `_`, `:`, `-`.
- `data` y `local_alerts` deben ser arrays.
- Lote maximo: `policy.max_batch_size`.

## Persistencia backend

| Tabla | Que guarda |
| --- | --- |
| `devices` | Identidad, secreto, configuracion, ultimo sync y estado. |
| `temperatures` | Lecturas aceptadas. |
| `esp_sync_batches` | Idempotencia, ACK, hash y antireplay. |
| `esp_local_alerts` | Alertas generadas por firmware. |
| `esp_diagnostics` | Diagnostico opcional por sync. |
| `esp_command_responses` | Confirmaciones de comandos/config. |
| `api_request_logs` | Request HTTP completo con `request_id`. |
| `audit_events` | Eventos de auditoria transversales. |

## Checklist firmware

- Guardar `shared_secret` en NVS/Preferences.
- Sincronizar hora antes de firmar.
- Firmar con JSON canonico.
- Usar `packet_id` estable por lote.
- Mantener cola local hasta ACK.
- Respetar `retry_after_seconds`, `retry_base_seconds` y `max_batch_size`.
- Reportar SMS enviados/fallidos en `local_alerts`.
- Confirmar cambios de configuracion con `command-response`.
- No loguear `shared_secret`, `palabra_clave` ni firmas completas en serial de produccion.

## Pruebas Postman

Colecciones:

- `temp-segura-backend-completo.postman_collection.json`: pruebas manuales de backend y protocolo.
- `postman-esp32-simulator.json`: simulador ESP con scripts de timestamp y HMAC.

Casos cubiertos:

- hora del servidor;
- registro valido;
- registro sin `palabra_clave`;
- timestamp invalido;
- sync valido;
- sync duplicado;
- replay conflictivo;
- alertas locales y SMS fallback;
- diagnostico;
- firma invalida;
- `packet_id` invalido;
- temperatura fuera de rango;
- respuesta de comando;
- entrada unica `/api/esp`;
- auditoria de requests/eventos.
