# Base de datos — Temp Segura

MySQL / MariaDB, charset `utf8mb4` con collation `utf8mb4_unicode_ci`.

## Levantar la base desde cero

Ejecutar **en este orden**. Todas las migraciones son idempotentes, así que se
pueden volver a correr sobre una base ya migrada sin romper nada.

```sql
-- 1. Esquema base (dump del 2026-05-28: tablas, vistas y datos de ejemplo)
SOURCE schema.sql;

-- 2. Migraciones posteriores, en orden numérico
SOURCE migrations/001_protocol_http_sms.sql;
SOURCE migrations/002_security_audit.sql;
SOURCE migrations/003_audit_observability.sql;
SOURCE migrations/004_alert_acknowledge.sql;
SOURCE migrations/005_stock_rfid.sql;
SOURCE migrations/006_account_lock.sql;
```

Desde la línea de comandos:

```bash
mysql -u root -p temp_segura < schema.sql
for f in migrations/*.sql; do mysql -u root -p temp_segura < "$f"; done
```

En phpMyAdmin: importar `schema.sql` primero y después cada archivo de
`migrations/` en orden numérico.

Para datos de prueba (usuarios, heladeras y lecturas de ejemplo):

```sql
SOURCE seed_devtest.sql;
```

## Qué aporta cada migración

| Archivo | Qué agrega | Ya incluido en `schema.sql` |
|---|---|---|
| `001_protocol_http_sms.sql` | Columnas del protocolo ESP en `devices` (`mac_address`, `shared_secret`, `config_version`, …) y tablas `esp_sync_batches`, `esp_local_alerts`, `esp_diagnostics`, `esp_command_responses` | Sí |
| `002_security_audit.sql` | `stock_item_change_log` y `rate_limit_events` | Sí |
| `003_audit_observability.sql` | `api_request_logs` y `audit_events` (observabilidad de la API) | **No** |
| `004_alert_acknowledge.sql` | `alerts.acknowledged` + `acknowledged_at` (estado "reconocida") | **No** |
| `005_stock_rfid.sql` | `stock_items.rfid` + índice | **No** |
| `006_account_lock.sql` | `users.locked_until` + índice (bloqueo temporal de cuenta) | **No** |

`schema.sql` es un dump tomado el 2026-05-28, por eso ya contiene lo que hacen
001 y 002. Se dejan igual en la cadena porque son idempotentes y hacen falta
para actualizar bases más viejas.

## Estructura

```
Database/
├── README.md            este archivo
├── schema.sql           esquema base (dump)
├── seed_devtest.sql     datos de prueba para desarrollo
├── Script.sql           script original del anteproyecto (histórico)
└── migrations/          migraciones incrementales, en orden numérico
```

## Volcados históricos

Los archivos `sistemas_heladeras*.sql` y `heladeras_proyectofinal*.sql` son
volcados viejos guardados a mano durante el desarrollo. **No forman parte del
esquema**: están excluidos del repositorio por `.gitignore` y se pueden borrar.
Varios además tienen los acentos corruptos (se exportaron con el charset
equivocado), así que no sirven para reimportar.

## Vistas disponibles

`schema.sql` define tres vistas que hoy no expone ningún endpoint:
`top_fridges_by_alerts`, `users_with_most_alerts` e `inactive_fridges`.
Son la base natural para un futuro `GET /api/dashboard/summary`.
