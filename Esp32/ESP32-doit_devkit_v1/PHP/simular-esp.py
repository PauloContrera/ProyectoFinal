#!/usr/bin/env python3
"""
Simulador ESP — Temp Segura (Python 3, sin instalar nada / solo librería estándar)

Calcula el HMAC y manda una lectura firmada al servidor, igual que el firmware.

USO:
  python simular-esp.py            -> te pregunta la temperatura y la manda
  python simular-esp.py 12.3       -> manda temp 12.3
  python simular-esp.py 25 nosend  -> solo muestra hash + body + curl (NO envía)

Editá SECRET con el valor COMPLETO (64 hex) del dispositivo en la DB.
"""

import hashlib
import hmac
import json
import os
import sys
import time
import urllib.request
import urllib.error

# ===== CONFIG =====
BASE   = os.environ.get("ESP_BASE")   or "https://tempsegura.orbitar.dev/api"
MAC    = os.environ.get("ESP_MAC")    or "4C:11:AE:70:26:70"
SECRET = os.environ.get("ESP_SECRET") or "b45f063b9605742450c78649b78e18aca47e9b4fc23571e60e046fa3853f0462"
# ==================


def main():
    # Temperatura: por argumento o preguntando
    if len(sys.argv) > 1:
        temp = float(sys.argv[1])
    else:
        temp = float(input("Temperatura a enviar (°C): ").strip().replace(",", "."))
    nosend = len(sys.argv) > 2 and sys.argv[2] == "nosend"

    # PHP serializa los float enteros sin ".0" (8.0 -> "8"); Python los deja "8.0".
    # Para que la firma coincida, mandamos los enteros como int.
    if temp.is_integer():
        temp = int(temp)

    ts = int(time.time())

    # Cuerpo que se firma: TODO menos mac, timestamp y signature.
    data = {
        "packet_id": "pkt-%d" % ts,
        "seq": 1,
        "data": [{"temp": temp, "time": ts}],
        "local_alerts": [],
        "optional": {"firmware_version": "1.0.0", "rssi": -60},
    }

    # Canonicalización idéntica al backend: claves ordenadas (recursivo), sin espacios, sin escapar.
    json_data = json.dumps(data, sort_keys=True, separators=(",", ":"), ensure_ascii=False)
    message = (MAC + str(ts) + json_data).encode("utf-8")
    signature = hmac.new(SECRET.encode("utf-8"), message, hashlib.sha256).hexdigest()

    body = {"mac": MAC, "timestamp": ts, "signature": signature}
    body.update(data)
    body_str = json.dumps(body, ensure_ascii=False)

    print("timestamp : %d" % ts)
    print("signature : %s" % signature)
    print("body      : %s\n" % body_str)

    if nosend:
        print("curl listo:")
        print("curl -X POST \"%s/esp/sync\" -H \"Content-Type: application/json\" -d '%s'" % (BASE, body_str))
        return

    req = urllib.request.Request(
        BASE + "/esp/sync",
        data=body_str.encode("utf-8"),
        headers={"Content-Type": "application/json", "Accept": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=25) as r:
            print("===> HTTP %d" % r.status)
            print(r.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        print("===> HTTP %d" % e.code)
        print(e.read().decode("utf-8"))
    except Exception as e:
        print("Error de conexión: %s" % e)


if __name__ == "__main__":
    main()
