# Protocolo de comunicación ESP32 ↔ backend — v2.2

Especificación completa para implementar el firmware. Reemplaza a la v2.1: todo
lo de aquella sigue vigente, y se agrega el canal de RFID, la clave de activación
por dispositivo y el rechazo de dispositivos sin secreto propio.

**Base URL producción:** `https://tempsegura.orbitar.dev/api`
**Base URL desarrollo:** `http://127.0.0.1:8000/api`

---

## 1. Resumen en una página

```
  ARRANQUE
    1. Sincronizar reloj  ── SNTP, y si falla ── GET /api/esp/time
    2. Registrarse        ── POST /api/esp/register  (con palabra_clave)
                             └─ guardar shared_secret en NVS. Se entrega UNA vez.

  OPERACIÓN (cada tiempo_espera segundos)
    3. POST /api/esp/sync  firmado
         data[]         lecturas de temperatura
         local_alerts[] alertas que detectó el propio firmware
         rfid_events[]  tarjetas leídas (entradas y salidas de stock)
         optional{}     diagnóstico
       └─ respuesta: ack + config si cambió + policy

  EVENTUAL
    4. POST /api/esp/command-response  firmado, al aplicar un cambio de config
```

Reglas que no se negocian:

- **HTTPS en producción.** En LAN de desarrollo se acepta HTTP.
- **Todo sync y command-response va firmado.** Sin firma válida no se procesa nada.
- **El `shared_secret` se guarda en NVS.** Si se pierde, hay que re-registrar.
- **Nunca se envía la `palabra_clave` en un sync**, solo en el registro.

---

## 2. Endpoints

| Método | Ruta | Firma | Para qué |
|---|---|---|---|
| `GET` | `/api/esp/time` | no | Hora del servidor y tolerancias |
| `POST` | `/api/esp/register` | no | Alta del dispositivo, entrega el `shared_secret` |
| `POST` | `/api/esp/sync` | **sí** | Temperaturas, alertas locales, RFID y diagnóstico |
| `POST` | `/api/esp/command-response` | **sí** | Confirmar que se aplicó un comando |
| `POST` | `/api/esp` | según payload | Entrada única: enruta por contenido |

`/api/esp` decide solo: si trae `accion: "registro"` registra; si trae
`respuesta_comando` confirma el comando; si no, hace sync. Sirve para firmwares
que prefieren una sola URL.

---

## 3. Identidad y seguridad

### 3.1 MAC

Identifica al dispositivo. Se normaliza a mayúsculas con `:` — `A1:B2:C3:D4:E5:01`.
El servidor acepta cualquier separador y normaliza, pero **la firma se calcula
sobre la forma normalizada**, así que conviene mandarla ya normalizada.

### 3.2 Clave de activación (`palabra_clave`)

Solo en el registro. Desde la v2.2:

- Si la MAC **ya existe** (preprovisionada desde administración), vale
  **únicamente la `activation_keyword` de esa heladera**.
- Si la MAC **es nueva**, vale la clave global del servidor, y el alta automática
  puede estar deshabilitada (`ESP_ALLOW_SELF_REGISTRATION=false`), en cuyo caso
  responde `403 ERR_ACTIVACION`.

En producción lo recomendado es preprovisionar cada heladera desde el panel y
darle su propia clave.

### 3.3 Secreto compartido (`shared_secret`)

64 caracteres hex, único por dispositivo. Se entrega **una sola vez**, en la
respuesta del registro:

```json
"provisioning": {
  "shared_secret": "a3f1…",
  "store": "Guardar en NVS/Preferences del ESP32 y usarlo para firmar los siguientes paquetes."
}
```

> **v2.2:** ya no existe un secreto por defecto. Un dispositivo sin secreto propio
> recibe `401 ERR_FIRMA` con el detalle *"Dispositivo sin secreto aprovisionado"*.
> Si eso pasa, hay que volver a llamar al registro: el servidor reprovisiona la
> heladera y devuelve un secreto nuevo.

Un re-registro de una MAC que **ya tiene** secreto **no** lo devuelve otra vez ni
lo rota: solo actualiza modelo, firmware e IMEI.

### 3.4 Firma HMAC

```
signature = HMAC_SHA256(mac + timestamp + json_canónico, shared_secret)
```

- `mac`: normalizada, tal cual va en el body.
- `timestamp`: epoch UTC en segundos, el mismo que va en el body.
- `json_canónico`: el JSON del body **sin** `mac`, `timestamp` ni `signature`, con
  las **claves de cada objeto ordenadas alfabéticamente** y los arrays en su orden
  original.
- Resultado en **hex minúscula** (también se acepta base64).
- Sin escapes innecesarios: equivalente a `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
- Se compara con `hash_equals`, así que no hay fuga por tiempo.

**Ejemplo trabajado.** Body a enviar:

```json
{
  "mac": "A1:B2:C3:D4:E5:01",
  "timestamp": 1780345017,
  "packet_id": "sync-001",
  "seq": 1,
  "data": [{ "temp": 4.3, "time": 1780344957 }],
  "local_alerts": [],
  "rfid_events": [],
  "signature": "…"
}
```

JSON canónico (claves ordenadas, sin mac/timestamp/signature):

```
{"data":[{"temp":4.3,"time":1780344957}],"local_alerts":[],"packet_id":"sync-001","rfid_events":[],"seq":1}
```

Mensaje que entra al HMAC:

```
A1:B2:C3:D4:E5:011780345017{"data":[{"temp":4.3,"time":1780344957}],"local_alerts":[],"packet_id":"sync-001","rfid_events":[],"seq":1}
```

Ojo con dos detalles que rompen la firma:

- Los objetos anidados también van ordenados (`optional`, cada elemento de
  `rfid_events`, etc.).
- Los números se serializan sin ceros de más: `4.3`, no `4.30`.

---

## 4. `GET /api/esp/time`

Sin firma, sin autenticación. Se usa al arrancar si SNTP falla.

```json
{
  "success": true,
  "server_time": 1786482871,
  "server_time_iso": "2026-08-11T21:14:31+00:00",
  "timestamp_tolerance_seconds": 900,
  "request_id": "5bbdc1da696ee798"
}
```

---

## 5. `POST /api/esp/register`

```json
{
  "accion": "registro",
  "mac": "A1:B2:C3:D4:E5:01",
  "modelo": "ESP32-doit-devkit-v1 + MFRC522",
  "firmware_version": "2.2.0",
  "protocol_version": "2.2",
  "sim_imei": "354829071122334",
  "timestamp": 1786482871,
  "palabra_clave": "…"
}
```

Obligatorios: `mac`, `timestamp`, `palabra_clave`. El resto es informativo.

Respuesta:

```json
{
  "success": true,
  "server_time": 1786482871,
  "request_id": "…",
  "estado_cuenta": true,
  "config_version": 1,
  "policy": { "…": "…" },
  "mensaje": "Dispositivo registrado exitosamente",
  "config": { "…": "…" },
  "provisioning": { "shared_secret": "…" }
}
```

`provisioning` **solo aparece la primera vez**. Si no viene y no tenés el secreto
guardado, no vas a poder firmar: hay que borrar la heladera desde administración
y registrar de nuevo, o pedir que la reprovisionen.

---

## 6. `POST /api/esp/sync`

El mensaje principal. Todos los arrays son opcionales, pero si van tienen que ser
arrays (no `null`).

```json
{
  "mac": "A1:B2:C3:D4:E5:01",
  "timestamp": 1786482871,
  "packet_id": "sync-1786482871-42",
  "seq": 42,

  "data": [
    { "temp": 4.3, "time": 1786482811 },
    { "temp": 4.6, "time": 1786482841 }
  ],

  "local_alerts": [
    { "type": "temp_high", "temp": 10.1, "time": 1786482820 },
    { "type": "power_outage", "time": 1786482830 }
  ],

  "rfid_events": [
    { "uid": "3A:5C:33:02", "movimiento": "descarga", "time": 1786482845 },
    { "uid": "33:94:BC:D9", "movimiento": "carga", "cantidad": 5, "time": 1786482850 }
  ],

  "optional": {
    "uptime": 86400,
    "signal_strength": -67,
    "battery_level": 89
  },

  "signature": "…"
}
```

### 6.1 `data[]` — temperaturas

| Campo | Tipo | Regla |
|---|---|---|
| `temp` | número | −50 a 80 |
| `time` | epoch | No futuro más allá de `record_future_tolerance_seconds` (900), no más viejo que `record_max_age_seconds` (7 días) |

Una lectura repetida (mismo dispositivo y mismo `time`) se descarta como duplicada
sin error. El servidor evalúa cada lectura **nueva** contra el rango configurado y
genera la alerta si corresponde.

### 6.2 `local_alerts[]` — alertas del firmware

Objetos libres; se guardan enteros. Si `type` es `temp_high` o `temp_low` además
se convierten en alerta del sistema, con la misma deduplicación por cooldown que
las que evalúa el servidor.

### 6.3 `rfid_events[]` — entradas y salidas de stock *(nuevo en v2.2)*

Cada elemento es una tarjeta leída.

| Campo | Obligatorio | Regla |
|---|---|---|
| `uid` | sí | Hasta 64 caracteres de `[A-Za-z0-9:_-]`. Alias: `rfid` |
| `movimiento` | sí | `carga`/`descarga`, `in`/`out`, `entrada`/`salida`, o el entero del enum `TipoRfid`: **1** = carga, **2** = descarga. Alias: `direction`, `tipo` |
| `cantidad` | no | Por defecto 1. Rango 1 a 10000. Alias: `quantity` |
| `time` | no | Epoch del escaneo. Por defecto, el momento de recepción |

El UID se puede mandar como lo imprime el MFRC522 (`"3A 5C 33 02"`) o con dos
puntos (`"3A:5C:33:02"`): el servidor normaliza a la segunda forma. **Para firmar,
usá el string tal cual lo vas a enviar.**

Límite por paquete: `policy.max_batch_size`, igual que `data`.

### 6.4 `optional{}` — diagnóstico

`uptime` en segundos, `signal_strength` en dBm, `battery_level` en porcentaje.
Se guarda el objeto completo.

### 6.5 Respuesta

```json
{
  "success": true,
  "server_time": 1786482871,
  "request_id": "…",
  "estado_cuenta": true,
  "config_version": 3,
  "policy": { "…": "…" },
  "message": "2 registros insertados correctamente",
  "cambio": true,
  "duplicate": false,
  "ack": {
    "packet_id": "sync-1786482871-42",
    "status": "accepted",
    "inserted": 2,
    "duplicates": 0,
    "rfid": {
      "received": 2,
      "applied": 2,
      "events": [
        {
          "index": 0,
          "uid": "3A:5C:33:02",
          "direction": "out",
          "status": "applied",
          "applied": true,
          "duplicate": false,
          "stock_item_id": 42,
          "quantity_before": 10,
          "quantity_after": 9,
          "reason": null
        }
      ]
    }
  },
  "config": { "…": "…" }
}
```

- `ack.rfid` **solo aparece si el paquete traía `rfid_events`**. Un firmware que no
  los envía recibe exactamente la misma respuesta que en v2.1.
- `cambio: true` significa que hay configuración nueva; viene en `config`.

### 6.6 Desenlaces de cada lectura RFID

| `status` | Qué pasó |
|---|---|
| `applied` | Se resolvió la tarjeta y se ajustó la cantidad |
| `ambiguous` | Varios ítems comparten el UID: se aplicó al de vencimiento más próximo (FEFO) |
| `unmatched` | Ningún ítem tiene esa tarjeta: se registra el movimiento pero no se toca el stock |
| `duplicate` | Ese escaneo ya se había procesado; no se hace nada |
| `invalid` | UID o movimiento mal formados; viene `reason` con el motivo |

**Una lectura inválida no tumba el paquete.** Las temperaturas y las demás
lecturas del mismo envío se procesan igual.

---

## 7. Idempotencia: las dos capas

Esto es lo más importante para un dispositivo que pierde red.

**Capa 1 — el paquete, por `packet_id`.** Si reenviás el mismo `packet_id` con el
mismo contenido, el servidor responde `duplicate: true` con el ACK original y no
reprocesa. Si lo reenviás con contenido **distinto**, es `409 ERR_REPLAY`.

**Capa 2 — el escaneo RFID, por su clave natural.** Un mismo escaneo físico
—`(dispositivo, uid, dirección, time)`— no se aplica dos veces **aunque venga en
paquetes distintos con `packet_id` diferentes**.

Por qué hacen falta las dos: si el ESP manda un sync, el servidor lo procesa y la
respuesta se pierde, el firmware reintenta. Si al reintentar arma un `packet_id`
nuevo (porque incrementó `seq`), la capa 1 no lo detecta. La capa 2 sí, y evita
descontar dos veces la misma extracción.

**Consecuencia para el firmware:** conservá el `time` original de cada escaneo al
reintentar. Si le ponés la hora del reenvío, el servidor lo va a tomar como un
escaneo nuevo y va a descontar de nuevo.

---

## 8. `POST /api/esp/command-response`

```json
{
  "mac": "A1:B2:C3:D4:E5:01",
  "timestamp": 1786482871,
  "packet_id": "cmd-1786482871-7",
  "seq": 43,
  "respuesta_comando": {
    "tipo": "cambio_config",
    "estado": "ok",
    "detalle": "Parámetros aplicados correctamente"
  },
  "signature": "…"
}
```

Se manda después de aplicar una `config` recibida en un sync con `cambio: true`.

---

## 9. Configuración que baja el servidor

```json
"config": {
  "temp_min": 2,
  "temp_max": 8,
  "grupo": "Vacunatorio Norte",
  "area": "Heladera Vacunas B",
  "ubicacion": "Box 3",
  "telefonos": ["+5492611234567"],
  "tiempo_espera": 900,
  "url_backup": "",
  "config_version": 3,
  "protocol_version": "2.2",
  "max_batch_size": 120,
  "retry_base_seconds": 30
}
```

Llega en el registro y en cualquier sync con `cambio: true`. El firmware la
persiste, la aplica y confirma con `command-response`.

`telefonos` son los destinos de SMS: **el SMS lo manda el ESP**, no el servidor.
El servidor manda el email al responsable.

---

## 10. Envelope y códigos de error

Todo error tiene esta forma:

```json
{
  "success": false,
  "server_time": 1786482871,
  "request_id": "…",
  "error": {
    "code": "ERR_FIRMA",
    "message": "Firma no valida o ausente",
    "detalle": "Recalcular HMAC_SHA256(mac + timestamp + json_data)."
  },
  "retry_after_seconds": 30,
  "estado_cuenta": true,
  "policy": { "…": "…" }
}
```

| Código | HTTP | Causa | Qué hacer |
|---|---|---|---|
| `ERR_ACTIVACION` | 401 / 403 | Clave de activación incorrecta, o alta automática deshabilitada | Revisar provisioning. **No reintentar en bucle** |
| `ERR_FIRMA` | 401 | HMAC inválido, ausente, o dispositivo sin secreto propio | Revisar canónico y secreto. Si dice "sin secreto aprovisionado", volver a registrar |
| `ERR_TIMESTAMP` | 400 | Hora fuera de tolerancia | Resincronizar por SNTP o `/esp/time` y reintentar |
| `ERR_FORMATO` | 400 | JSON o campos inválidos | Bug de firmware: corregir, no reintentar igual |
| `ERR_DISPOSITIVO` | 404 | MAC no registrada | Registrar |
| `ERR_PACKET_ID` | 400 | `packet_id` fuera de formato | Regenerar |
| `ERR_REPLAY` | 409 | Mismo `packet_id`, distinto contenido | No reutilizar IDs |
| `ERR_BATCH_GRANDE` | 413 | Lote sobre `max_batch_size` | Partir el lote |
| `ERR_CUENTA` | 403 | Heladera deshabilitada | Encolar y reintentar más tarde |
| — | 429 | Rate limit | Esperar `retry_after_seconds` |

Límites de frecuencia: sync 180 por 5 min por MAC, command-response 120 por 5 min,
registro 30 por hora por IP, `/esp/time` 120 por minuto por IP.

---

## 11. Comportamiento esperado del firmware

**Reintentos.** Backoff exponencial con jitter, arrancando en
`policy.retry_base_seconds`, con techo. Después de N intentos, probar `url_backup`
si está configurada.

**Cola offline.** Persistir en EEPROM/NVS lo que no se pudo enviar: lecturas,
alertas y **escaneos RFID con su `time` original**. Vaciar la cola en los
siguientes syncs respetando `max_batch_size`.

**Reloj.** Sin hora válida no se puede firmar dentro de tolerancia. Al arrancar:
SNTP, y si falla `GET /api/esp/time`. Si un sync devuelve `ERR_TIMESTAMP`,
resincronizar antes de reintentar.

**Qué NO hacer:**
- Reintentar en bucle ante `ERR_ACTIVACION` o `ERR_FORMATO`: son errores de
  configuración o de firmware, no transitorios.
- Cambiar el `time` de un escaneo al reintentar.
- Guardar el `shared_secret` en RAM: se pierde al reiniciar.
- Mandar `palabra_clave` en un sync.

### Checklist

- [ ] Reloj sincronizado antes del primer envío
- [ ] `shared_secret` en NVS, sobrevive al reinicio
- [ ] Canónico con claves ordenadas recursivamente
- [ ] `packet_id` único por paquete
- [ ] `time` original preservado en los reintentos
- [ ] Cola offline con las tres clases de evento
- [ ] Backoff con jitter y `url_backup`
- [ ] `config` persistida y confirmada con `command-response`
- [ ] HTTPS con certificado validado en producción

---

## 12. Estado del firmware actual

`Esp32/ESP32-doit_devkit_v1` implementa bien el registro, el sync de temperaturas
y la firma. **Le falta el canal RFID**, y hoy no compila:

1. `SystemController::procesarRfid()` (línea 119) llama a
   `serverClient.sendRfidEvent()`, que **no existe** en el `ServerClient` actual
   —solo sobrevive en `Anterior/` y en `tests/TestStorage/`— y
   `ENABLE_SERVER_COMMUNICATION` está en `1`, así que la llamada se compila.
2. La implementación vieja de `Anterior/` mandaba form-encoded
   (`user=&pass=&tipo=rfid&uid=`), un protocolo pre-v2.0 que ya no existe.
3. `RfidManager::isKnownUid()` compara contra dos UID hardcodeados. Con el
   servidor resolviendo contra `stock_items`, esa lista deja de tener sentido:
   conviene mandar cualquier UID leído y dejar que el servidor decida.

Para cerrarlo: reimplementar `sendRfidEvent` volcando las lecturas en el array
`rfid_events` del sync firmado, vaciar los `PendingRecord` con `kind = 2` de la
EEPROM en ese mismo array, y sacar la allowlist.

---

## 13. Cómo probarlo

| Herramienta | Para qué |
|---|---|
| `docs/postman-esp32-rfid-sin-scripts.postman_collection.json` | Simular a mano, sin scripts. Guía: `docs/postman-rfid-sin-scripts.md` |
| `docs/postman-rfid-stock.postman_collection.json` | Lo mismo pero con la firma automática |
| `php Backend/tests/firmar_payload.php` | Calcula la firma y arma el body listo para pegar |
| `php Backend/tests/rfid_stock_movements_test.php` | Suite automática, 30 asserts |

Detalle del lado servidor de los movimientos: `docs/stock-rfid.md`.
