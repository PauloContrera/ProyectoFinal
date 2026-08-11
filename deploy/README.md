# Paquete de despliegue

`public_html/` es exactamente lo que va a la raíz web del servidor (Hostinger).
Se genera desde el proyecto; **no se edita a mano**.

```
public_html/
├── .htaccess          routing SPA + cache de assets
├── index.html         build del frontend
├── assets/            JS/CSS/imágenes con hash
├── vite.svg
└── api/
    ├── .htaccess      front controller de la API + protección de archivos
    ├── .env           credenciales de PRODUCCIÓN (no está en git)
    ├── index.php      front controller (define $basePath = '')
    ├── bootstrap.php  inicialización compartida con desarrollo
    ├── config/ controllers/ helpers/ middleware/ models/ routes/ MailTemplates/
    ├── vendor/        dependencias de Composer
    └── logs/          debe existir y ser escribible por el servidor web
```

## Regenerar el paquete

```bash
cd ProyectoFinal/Frontend && npm run build
```

Después copiar:

- `Frontend/dist/*` → `deploy/public_html/`  (borrando antes `assets/`, porque los
  nombres llevan hash y los viejos quedan huérfanos)
- `Backend/*` → `deploy/public_html/api/`, **excluyendo** `public/`, `tests/`,
  `.env`, `.env.example`, `.env.local`, `.gitignore` y `logs/*.log`

Nunca pisar en el servidor: `api/.env`, `api/.htaccess`, `api/index.php` y el
`.htaccess` de la raíz. Son propios del despliegue.

## Qué subir en cada deploy

| Cambió | Subir |
|---|---|
| Frontend | `index.html`, `assets/` completo |
| Backend | la carpeta `api/` sin `.env` ni `.htaccess` ni `index.php` |
| Dependencias PHP | además `api/vendor/` y `api/composer.*` |

## Migraciones de base de datos

Antes de subir código que las necesite, correr en la base de producción, en orden
numérico, los archivos de `Database/migrations/` que falten. Son idempotentes: se
pueden reejecutar sin romper nada. Ver `Database/README.md`.

## Pendientes de infraestructura

- **Forzar HTTPS**: el bloque está comentado al final del `.htaccess` de la raíz.
  Descomentarlo cuando el certificado SSL esté activo. El protocolo del ESP exige
  HTTPS en producción y `APP_URL` ya apunta a `https://`.
- **`ESP_ALLOW_SELF_REGISTRATION`**: conviene ponerlo en `false` en producción una
  vez que todas las heladeras estén preprovisionadas desde administración, para que
  una MAC desconocida no pueda darse de alta sola.
- **`APP_ENV=production`** en `api/.env`: además de endurecer la validación del
  `JWT_SECRET`, desactiva el `JSON_PRETTY_PRINT` de todas las respuestas y restringe
  CORS a la lista de `ALLOWED_ORIGINS`.
