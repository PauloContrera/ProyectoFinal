# Movimientos de stock por RFID

Cómo el servidor convierte una lectura de tarjeta en un cambio de cantidad.

## El flujo

El ESP tiene dos lectores MFRC522: uno en la puerta de **carga** y otro en la de
**descarga**. Cada tarjeta leída viaja dentro del `POST /api/esp/sync` que el
dispositivo ya usaba para mandar temperaturas, en un array nuevo `rfid_events`.

```
 tarjeta  →  lector  →  rfid_events[]  →  /api/esp/sync  →  stock_items.quantity ±
                                              │
                                              └──────────→  stock_movements (historial)
```

No hay endpoint aparte a propósito: al ir dentro del `sync` reutiliza la firma
HMAC, el `packet_id` y el anti-replay que ya estaban probados, y el ESP no
necesita una segunda conexión.

## Formato

```jsonc
{
  "mac": "AA:BB:CC:DD:EE:70",
  "timestamp": 1786500000,
  "packet_id": "pkt-1786500000-1",
  "seq": 1,
  "data": [ { "temp": 5.1, "time": 1786499940 } ],
  "local_alerts": [],
  "rfid_events": [
    { "uid": "3A:5C:33:02", "movimiento": "descarga", "time": 1786499945 },
    { "uid": "33:94:BC:D9", "movimiento": "carga", "cantidad": 5, "time": 1786499950 }
  ],
  "signature": "…"
}
```

| Campo | Obligatorio | Notas |
|---|---|---|
| `uid` | sí | Hasta 64 caracteres de `[A-Za-z0-9:_-]`. También se acepta `rfid` como nombre |
| `movimiento` | sí | `carga`/`descarga`, `in`/`out`, `entrada`/`salida`, o el entero del enum `TipoRfid` (**1** = carga, **2** = descarga). También se acepta `direction` o `tipo` |
| `cantidad` | no | Por defecto 1. Rango 1 a 10000. También se acepta `quantity` |
| `time` | no | Epoch del escaneo. Por defecto, el momento de recepción |

El firmware imprime el UID separado por espacios (`3A 5C 33 02`). El servidor lo
normaliza a `3A:5C:33:02`, así que se puede mandar de las dos formas.

## Respuesta

El `ack` incluye un bloque `rfid` **solo si el paquete traía lecturas**, para no
cambiarle la forma de la respuesta a los firmwares que no las envían:

```jsonc
"ack": {
  "packet_id": "pkt-1786500000-1",
  "status": "accepted",
  "inserted": 1,
  "duplicates": 0,
  "rfid": {
    "received": 1,
    "applied": 1,
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
}
```

## Cómo resuelve cada lectura

1. **Normaliza** el UID y la dirección. Si algo no cierra, esa lectura sale como
   `status: "invalid"` con el motivo y **el resto del paquete sigue su curso**:
   una tarjeta mal leída nunca tira abajo las temperaturas del mismo envío.
2. **Busca** items de esa heladera con ese `rfid`.
3. **Registra el movimiento primero.** El `INSERT` en `stock_movements` hace de
   cerrojo: la clave única `(device_id, rfid, direction, occurred_at)` rebota el
   duplicado antes de tocar ninguna cantidad.
4. **Ajusta la cantidad** en la propia sentencia SQL.

### Los cuatro desenlaces

| `status` | Cuándo | Qué pasa con el stock |
|---|---|---|
| `applied` | La tarjeta identifica exactamente un item | Suma o resta |
| `ambiguous` | Varios items comparten el UID | Se aplica al de **vencimiento más próximo** (FEFO) y queda marcado para revisar |
| `unmatched` | Ningún item tiene esa tarjeta | No se toca el stock; el movimiento queda asentado para reconciliar |
| `duplicate` | Ese mismo escaneo ya se procesó | No se toca nada |

## Las tres decisiones que importan

**Idempotencia por escaneo, no por paquete.** El `packet_id` ya evitaba procesar
dos veces el mismo paquete, pero un ESP que se queda sin red puede reenviar la
misma lectura dentro de un paquete distinto. Por eso la clave única es el escaneo
físico —dispositivo, tarjeta, dirección e instante— y no el envío. Sin esto, una
reconexión descontaría dos veces la misma extracción.

**La resta se hace en SQL, no leyendo y reescribiendo:**

```sql
UPDATE stock_items SET quantity = GREATEST(0, quantity - :q) WHERE id = :id
```

Dos lecturas simultáneas del mismo item no se pisan, y `GREATEST` evita que la
cantidad quede negativa si se retira más de lo que figura cargado.

**Una tarjeta desconocida no se descarta.** Se guarda como `unmatched` en vez de
ignorarla: si alguien retiró algo con una tarjeta que nadie dio de alta, eso es
justamente lo que hay que poder auditar después.

## Consultar

```
GET /api/devices/{id}/stock?rfid=3A:5C:33:02   resolver una tarjeta
GET /api/devices/{id}/movements?limit=100      historial de entradas y salidas
```

Ambos respetan los permisos habituales del stock: dueño, `device_access` o
admin. El de búsqueda ordena FEFO, igual que el criterio con el que el servidor
resuelve un escaneo ambiguo.

## Probarlo

- **Postman**: `docs/postman-rfid-stock.postman_collection.json`. La firma HMAC la
  calcula sola; hay que completar `password` y ajustar `activation_keyword`.
  Usar una cuenta **admin**: la heladera que se da de alta desde el ESP queda sin
  dueño y un `client` no la ve hasta que se la asignen.
- **Suite**: `php Backend/tests/rfid_stock_movements_test.php` (30 asserts), ya
  incluida en `run_backend_suite.php`.

## Duplicados de UID

La migración **no** pone un `UNIQUE` en `(device_id, rfid)` porque la base actual
ya tiene tarjetas repetidas y fallaría a mitad de camino. Mientras tanto el
servidor resuelve por FEFO y marca `ambiguous`. Para ver si quedan duplicados:

```sql
SELECT device_id, rfid, COUNT(*) AS repetidos
FROM stock_items
WHERE rfid IS NOT NULL AND rfid <> ''
GROUP BY device_id, rfid
HAVING repetidos > 1;
```

Una vez resueltos, conviene forzarlo:

```sql
CREATE UNIQUE INDEX uq_stock_items_device_rfid ON stock_items (device_id, rfid);
```

## Lo que falta del lado del ESP

El servidor está listo y probado, pero el firmware todavía no puede usarlo:
`SystemController::procesarRfid()` llama a `serverClient.sendRfidEvent()`, un
método que **no existe** en el `ServerClient` actual (solo sobrevive en
`Anterior/` y en `tests/TestStorage/`), y `ENABLE_SERVER_COMMUNICATION` está en 1,
así que esa llamada se compila.

Para cerrarlo hace falta, del lado del firmware:

1. Reimplementar `sendRfidEvent` sobre el `ServerClient` actual, metiendo las
   lecturas en el array `rfid_events` del sync firmado.
2. Vaciar en ese array los `PendingRecord` con `kind = 2` que quedaron en EEPROM
   cuando no había red.
3. Sacar la allowlist `isKnownUid()`, que hoy tiene dos UID compilados: con el
   servidor resolviendo contra `stock_items`, agregar una tarjeta deja de exigir
   reflashear.
