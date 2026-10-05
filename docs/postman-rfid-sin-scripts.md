# Postman RFID sin scripts — guía

Acompaña a `postman-esp32-rfid-sin-scripts.postman_collection.json`.

Esa colección **no tiene scripts**: ni `pre-request`, ni `tests`, ni `event`. El
JSON se ve y se edita tal cual lo enviaría el ESP32, y la firma se pega a mano.
Si preferís que se calcule sola, usá `postman-rfid-stock.postman_collection.json`.

## Por qué hace falta pegar la firma

El backend valida `HMAC_SHA256(mac + timestamp + json_canónico, shared_secret)`.
Postman no puede calcular un HMAC sin script, así que el valor se genera aparte y
se pega.

Para eso está el firmador del repo:

```bash
php Backend/tests/firmar_payload.php --ejemplo=sync-rfid <shared_secret>
```

Imprime cuatro cosas: el **JSON canónico** (lo que realmente se firma), el
**mensaje HMAC** completo, la **firma**, y el **body listo para pegar**.

## Flujo recomendado (el más rápido)

1. **`00 - Hora del servidor`** → anotá `server_time`.
2. **`01 - Registro`** → poné ese `server_time` en la variable `esp_timestamp` y
   enviá. Copiá `provisioning.shared_secret` a `esp_shared_secret`.
   *Se entrega una sola vez.*
3. Para cada petición firmada:
   ```bash
   php Backend/tests/firmar_payload.php --ejemplo=sync-rfid <shared_secret>
   ```
4. Copiá el bloque **BODY COMPLETO** y pegalo entero en el body de la petición,
   reemplazando lo que había.
5. Enviá **dentro de los 15 minutos**: pasada la tolerancia da `ERR_TIMESTAMP`.

Pegar el body completo evita el error más común, que es firmar una cosa y enviar
otra.

## Flujo con variables (si querés editar el JSON a mano)

Si preferís dejar el JSON visible y solo cambiar la firma:

1. Fijá `esp_timestamp` y `scan_time` a valores concretos.
2. Armá un archivo con el payload **sin** `timestamp` ni `signature`:

   ```json
   {
     "mac": "A1:B2:C3:D4:E5:01",
     "packet_id": "manual-rfid-001",
     "seq": 1,
     "data": [],
     "local_alerts": [],
     "rfid_events": [
       { "movimiento": "descarga", "time": 1786497830, "uid": "3A:5C:33:02" }
     ]
   }
   ```

3. Firmalo con ese mismo `esp_timestamp`:

   ```bash
   php Backend/tests/firmar_payload.php cuerpo.json <shared_secret> 1786497860
   ```

4. Pegá la firma en la variable correspondiente (`firma_sync_descarga`, etc.).

## Las tres trampas del canónico

**1. Las claves van ordenadas alfabéticamente, también dentro de cada objeto
anidado.** En un evento RFID el orden es `cantidad`, `movimiento`, `time`, `uid`.
En el nivel superior: `data`, `local_alerts`, `optional`, `packet_id`,
`rfid_events`, `seq`. Los bodies de la colección ya están escritos en ese orden
para que puedas comparar de un vistazo.

**2. Los arrays conservan su orden.** No se ordenan.

**3. `mac`, `timestamp` y `signature` NO entran en el canónico**, pero `mac` y
`timestamp` sí van en el mensaje que se firma, adelante y concatenados.

Si la firma no valida, comparar el canónico que imprime el firmador contra el body
que estás mandando: casi siempre es una clave fuera de orden o un número
serializado distinto (`4.30` en vez de `4.3`).

## Qué esperar de cada petición

| # | Petición | Resultado esperado |
|---|---|---|
| 00 | Hora | `server_time` y `timestamp_tolerance_seconds` |
| 01 | Registro | `provisioning.shared_secret` la primera vez |
| 02 | Descarga | `applied: true`, `quantity_after = quantity_before - 1` |
| 03 | Reenvío del mismo escaneo | `duplicate: true`, cantidad **sin cambios** |
| 04 | Carga de 5 | `quantity_after = quantity_before + 5` |
| 05 | `movimiento: 2` | `direction: "out"` |
| 06 | UID con espacios | `uid: "3A:5C:33:02"` normalizado |
| 07 | Tarjeta desconocida | 200 con `status: "unmatched"` |
| 08 | Sync completo | `ack.inserted = 1` y `ack.rfid.applied = 1` |
| 09 | Command response | `success: true` |
| 10 | Firma inválida | **401** `ERR_FIRMA` |
| 11–14 | Consultas | Stock, búsqueda por tarjeta e historial |

## Dos detalles que confunden

**El `packet_id` no se repite.** Reenviar el mismo con contenido distinto da
`409 ERR_REPLAY`. Cambialo en cada lote nuevo.

**El `time` de la lectura RFID identifica el escaneo físico.** Reutilizarlo es lo
que hace que el servidor reconozca un reenvío y no descuente dos veces (eso es lo
que prueba la petición 03). Al revés: si querés simular *dos escaneos distintos*
de la misma tarjeta, tenés que cambiarle el `time`.

## Para apuntar a producción

Cambiar `base_url` a `https://tempsegura.orbitar.dev/api` y usar una MAC propia.
El `activation_keyword` tiene que coincidir con el del `.env` del servidor.

Tener en cuenta que la heladera que se da de alta desde el ESP queda **sin
dueño**: para las consultas 12–14 hace falta una cuenta admin, o asignarla antes
desde el panel.
