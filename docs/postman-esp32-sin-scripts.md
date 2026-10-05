# Postman ESP32 sin scripts

Esta guia acompana la coleccion:

```text
postman-esp32-manual-json.postman_collection.json
```

La coleccion no usa scripts de Postman. No tiene `pre-request`, `tests`, `event` ni calculos automaticos. La idea es ver y editar el JSON tal como lo enviaria el ESP32.

## Lo importante

El backend ESP actual usa estos endpoints:

```text
GET  /api/esp/time
POST /api/esp/register
POST /api/esp/sync
POST /api/esp/command-response
POST /api/esp
```

- `/api/esp/time` no requiere firma.
- `/api/esp/register` no requiere firma, pero requiere `palabra_clave`.
- `/api/esp/sync` requiere `signature` dentro del JSON.
- `/api/esp/command-response` requiere `signature` dentro del JSON.
- `/api/esp` es la entrada unica: si viene `accion=registro`, registra; si viene `respuesta_comando`, confirma comando; si no, hace sync.

## Variables manuales de Postman

Despues de importar la coleccion, revisar estas variables:

```text
base_url              http://127.0.0.1:8000/api
esp_mac               A1:B2:C3:D4:E5:01
esp_shared_secret     local-dev-esp-secret
activation_keyword    clavesecreta4321
esp_timestamp         copiar desde GET /api/esp/time
record_time_1         esp_timestamp - 60
record_time_2         esp_timestamp - 30
record_time_3         esp_timestamp - 10
sync_packet_id        cambiar en cada lote nuevo
signature_*           pegar la firma HMAC calculada
```

Para usar el dispositivo seed de desarrollo, dejar:

```text
esp_mac = A1:B2:C3:D4:E5:01
esp_shared_secret = local-dev-esp-secret
```

Para simular un ESP nuevo:

1. Ejecutar `0.1 Consultar hora del servidor`.
2. Copiar `server_time` a `esp_timestamp`.
3. Ejecutar `0.2 Registro inicial de nueva MAC`.
4. Copiar `provisioning.shared_secret` a `esp_shared_secret`.
5. Cambiar `esp_mac` por la MAC registrada si se quiere usar esa MAC para los sync siguientes.

## Firma manual

Formula real del backend:

```text
HMAC_SHA256(mac + timestamp + json_data, shared_secret)
```

Donde:

- `mac` va normalizada en mayusculas con `:`.
- `timestamp` es epoch UTC en segundos.
- `json_data` es el mismo JSON del request, pero sin `mac`, sin `timestamp` y sin `signature`.
- En `json_data`, los objetos van ordenados alfabeticamente por clave.
- Los arrays conservan su orden.
- El resultado puede pegarse como hexadecimal en `signature`.

Postman no puede calcular este HMAC sin script. Por eso esta coleccion deja el campo `signature` visible para pegarlo manualmente.

## Ejemplo: sync normal

Request visible en Postman:

```json
{
  "mac": "A1:B2:C3:D4:E5:01",
  "timestamp": 1780345017,
  "packet_id": "sync-manual-001",
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
  "signature": "PEGAR_HMAC_SYNC_NORMAL"
}
```

JSON que se firma:

```json
{"data":[{"temp":4.3,"time":1780344957},{"temp":4.6,"time":1780344987}],"local_alerts":[],"optional":{"battery_level":89,"signal_strength":-67,"uptime":86400},"packet_id":"sync-manual-001","seq":1}
```

Mensaje final para HMAC:

```text
A1:B2:C3:D4:E5:011780345017{"data":[{"temp":4.3,"time":1780344957},{"temp":4.6,"time":1780344987}],"local_alerts":[],"optional":{"battery_level":89,"signal_strength":-67,"uptime":86400},"packet_id":"sync-manual-001","seq":1}
```

Clave HMAC:

```text
local-dev-esp-secret
```

El resultado hexadecimal se pega en:

```text
signature_sync_normal
```

## Ejemplo: command-response

Request visible:

```json
{
  "mac": "A1:B2:C3:D4:E5:01",
  "timestamp": 1780345017,
  "packet_id": "cmd-manual-001",
  "seq": 10,
  "respuesta_comando": {
    "tipo": "cambio_config",
    "estado": "ok",
    "detalle": "Parametros aplicados correctamente"
  },
  "signature": "PEGAR_HMAC_COMMAND_RESPONSE"
}
```

JSON que se firma:

```json
{"packet_id":"cmd-manual-001","respuesta_comando":{"detalle":"Parametros aplicados correctamente","estado":"ok","tipo":"cambio_config"},"seq":10}
```

Mensaje final:

```text
A1:B2:C3:D4:E5:011780345017{"packet_id":"cmd-manual-001","respuesta_comando":{"detalle":"Parametros aplicados correctamente","estado":"ok","tipo":"cambio_config"},"seq":10}
```

## Orden recomendado para probar

1. `0.1 Consultar hora del servidor`.
2. Copiar `server_time` a `esp_timestamp`.
3. Ajustar `record_time_1`, `record_time_2`, `record_time_3`.
4. Si usas seed, mantener `esp_mac` y `esp_shared_secret`.
5. Si usas MAC nueva, ejecutar registro y copiar el `shared_secret`.
6. Calcular `signature_sync_normal`.
7. Ejecutar `1.1 Sync normal`.
8. Ejecutar `1.2 Reintento exacto del mismo sync` sin cambiar nada.
9. Calcular firmas para los otros JSON cuando quieras probar alertas, replay o command-response.

## Respuestas esperadas

Sync aceptado:

```json
{
  "success": true,
  "duplicate": false,
  "ack": {
    "packet_id": "sync-manual-001",
    "status": "accepted"
  }
}
```

Reintento exacto:

```json
{
  "success": true,
  "duplicate": true,
  "ack": {
    "packet_id": "sync-manual-001",
    "status": "duplicate"
  }
}
```

Firma incorrecta:

```json
{
  "success": false,
  "error": {
    "code": "ERR_FIRMA"
  }
}
```

Mismo `packet_id` con otro contenido:

```json
{
  "success": false,
  "error": {
    "code": "ERR_REPLAY"
  }
}
```

## Notas para el firmware

- El ESP debe guardar `shared_secret` en NVS/Preferences.
- El ESP no debe borrar lecturas hasta recibir `ack.status=accepted` o `ack.status=duplicate`.
- Si no hay internet, conserva cola local y reintenta con el mismo `packet_id`.
- Si cambia el contenido, debe cambiar el `packet_id`.
- Nunca loguear `palabra_clave`, `shared_secret` ni firmas completas en serial de produccion.
