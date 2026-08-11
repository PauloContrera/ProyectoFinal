# 🚀 Deploy de Temp Segura en Hostinger

Guía paso a paso para publicar la app (frontend React + API PHP + MySQL) en un hosting compartido de Hostinger.

---

## 📦 Qué hay en este paquete (`deploy/`)

```
deploy/
├── public_html/            ← TODO esto va al public_html de tu hosting
│   ├── index.html, assets/, vite.svg   (frontend React ya compilado)
│   ├── .htaccess                        (routing del SPA + API)
│   └── api/                             (la API en PHP)
│       ├── index.php, .htaccess
│       ├── .env                         (⚠️ EDITAR antes de usar)
│       ├── config/ controllers/ models/ helpers/ middleware/ routes/ MailTemplates/
│       ├── vendor/                      (dependencias PHP ya incluidas)
│       └── logs/                        (debe quedar con permiso de escritura)
├── temp_segura.sql         ← la base de datos para importar en phpMyAdmin
├── DEPLOY_HOSTINGER.md      ← esta guía
└── CREDENCIALES_TEMP.txt    ← contraseña del admin + secretos del ESP (NO subir al host)
```

> El frontend ya está compilado y configurado para llamar a la API en `/api` (mismo dominio), así que **no hay que recompilar nada**.

---

## ✅ Requisitos en Hostinger
- PHP **8.0 o superior** (ideal 8.1/8.2). Se configura en hPanel → *Avanzado → Configuración PHP*.
- Extensiones PHP: `pdo_mysql`, `mbstring`, `openssl`, `curl` (vienen por defecto).
- Base de datos **MySQL/MariaDB** (hPanel → *Bases de datos → MySQL*).
- Apache con `mod_rewrite` (viene por defecto en Hostinger).

---

## 1️⃣ Crear la base de datos e importarla

1. En hPanel → **Bases de datos → MySQL**: creá una base nueva y un usuario. Anotá:
   - **Nombre de la base** (suele tener un prefijo, ej. `u123456_tempsegura`)
   - **Usuario** (ej. `u123456_admin`)
   - **Contraseña**
2. Entrá a **phpMyAdmin** (botón al lado de la base), seleccioná tu base a la izquierda.
3. Pestaña **Importar** → elegí el archivo **`temp_segura.sql`** → **Continuar**.
4. Debería importar todas las tablas y vistas sin errores.

---

## 2️⃣ Configurar el `.env` de la API

Editá `public_html/api/.env` (con el editor de hPanel o antes de subir) y completá lo marcado con `<<< >>>`:

```env
DB_NAME=u123456_tempsegura        # el nombre real de tu base
DB_USER=u123456_admin             # tu usuario MySQL
DB_PASS=tu_password_mysql         # tu contraseña MySQL

APP_URL=https://tudominio.com     # tu dominio real (con https)
ALLOWED_ORIGINS=https://tudominio.com
```

- `JWT_SECRET` y los secretos del ESP ya vienen generados (fuertes). No hace falta tocarlos.
- **Email:** si querés que se envíen correos reales (verificación de cuenta, reset de contraseña, alertas), creá un buzón en hPanel → *Correos*, completá `MAIL_USER`/`MAIL_PASS`/`MAIL_FROM_EMAIL` y poné `MAIL_DEV_MODE=false`. Mientras esté en `true`, los correos NO se envían: se guardan en `api/logs/mail_dev.log`.

---

## 3️⃣ Subir los archivos por FTP

- Subí **todo el contenido de `public_html/`** (incluido `api/` y los `.htaccess`) a la carpeta **`public_html`** de tu hosting.
- Asegurate de subir también los archivos ocultos (`.htaccess`, `.env`). En FileZilla: *Servidor → Forzar mostrar archivos ocultos*.
- ⚠️ **No subas** `temp_segura.sql`, `DEPLOY_HOSTINGER.md` ni `CREDENCIALES_TEMP.txt` (esos son locales).
- Después de subir, dale permisos de escritura a la carpeta de logs: `public_html/api/logs` → permisos `755` (o `775`).

---

## 4️⃣ Verificar que funciona

1. Abrí `https://tudominio.com` → debería cargar la landing de Temp Segura.
2. Abrí `https://tudominio.com/api/esp/time` → debería devolver un JSON `{"success":true,...}`. Si ves eso, la API y la base están OK.
3. Iniciá sesión con la cuenta de administrador (ver abajo).

---

## 🔐 Cuenta de administrador inicial

El dump incluye una cuenta superadmin para que puedas entrar:

- **Usuario:** `PauloSuperAdmin`
- **Contraseña:** la que figura en `CREDENCIALES_TEMP.txt` (campo `PROD_SUPERADMIN_PASSWORD`).

> ⚠️ **Cambiala apenas entres** (icono de usuario → Configuración) y creá tus propios usuarios. La base también trae usuarios de demo (`PauloAdmin`, `PauloCliente`, `PauloVisitante`, `devtest`) con datos de ejemplo: borralos o cambiales la contraseña si no los necesitás.

---

## 📡 Notas para el ESP32

- La API del protocolo queda en `https://tudominio.com/api/esp/...` (registro, sync, command-response, time).
- En el firmware usá **HTTPS** (el protocolo lo exige en producción) y los secretos de `CREDENCIALES_TEMP.txt`:
  - `ESP_ACTIVATION_KEYWORD` → palabra clave de activación (alta del dispositivo).
  - `ESP_DEFAULT_SECRET` → solo para devices de bootstrap; cada ESP recibe su `shared_secret` propio al registrarse.
- Estos valores deben **coincidir** entre el `.env` del servidor y el firmware.

---

## 🔒 Recomendaciones de seguridad post-deploy
1. Cambiar la contraseña del superadmin y borrar/asegurar las cuentas demo.
2. Activar **SSL** (hPanel → *Seguridad → SSL*, gratis con Let's Encrypt) y descomentar el bloque "Forzar HTTPS" en `public_html/.htaccess`.
3. Configurar SMTP real y poner `MAIL_DEV_MODE=false`.
4. Borrar `CREDENCIALES_TEMP.txt` de tu máquina una vez guardadas las credenciales en un lugar seguro.

---

## 🆘 Problemas comunes

| Síntoma | Causa / solución |
|---|---|
| La landing carga pero el login da error de red | Revisá `https://tudominio.com/api/esp/time`. Si da 404 → falta `api/.htaccess` o `mod_rewrite`. Si da 500 → revisá las credenciales de DB en `api/.env`. |
| `/api/esp/time` da 500 | DB mal configurada en `.env` (nombre/usuario/contraseña), o falta importar el `.sql`. |
| Rutas como `/verify-email` dan 404 | Falta el `.htaccess` raíz en `public_html` (SPA fallback). |
| No llegan los correos | `MAIL_DEV_MODE=true` (no envía) o SMTP mal configurado. Revisá `api/logs/mail_dev.log`. |
| Error 403 al entrar a `/api/config` etc. | Es correcto: esas carpetas están bloqueadas a propósito. |
```
