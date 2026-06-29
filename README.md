# ProyectoFinal

## Protocolo HTTP/SMS ESP

La implementacion del protocolo de comunicacion entre ESP y backend esta documentada en:

- [docs/protocolo-http-sms.md](docs/protocolo-http-sms.md)

Para preparar DB, backend y frontend:

```bat
iniciar-temp-segura.bat
```

Para correr la prueba HTTP/DB del protocolo con el backend activo:

```bat
php Backend\tests\protocol_http_sms_test.php
```
