#ifndef CONFIG_H
#define CONFIG_H

#define WIFI_SSID "TU_WIFI"
#define WIFI_PASSWORD "TU_PASSWORD"

#define SERVER_BASE_URL "https://tempsegura.orbitar.dev/api"
#define ACTIVATION_KEYWORD "clavesecreta4321"

#define FIRMWARE_VERSION "1.0.0"
#define PROTOCOL_VERSION "2.0"

// En uso normal debe quedar en 0. Solo poner 1 si queres forzar /esp/register.
#define FORCE_REGISTER 0

// Si el backend no devuelve shared_secret en /esp/register, dejar cargado el
// secreto provisionado para la MAC del ESP. Si ya quedo guardado en Preferences,
// puede quedar vacio.
#define MANUAL_SHARED_SECRET "PEGAR_SHARED_SECRET_DE_LA_MAC"

// Prueba: 1 = HTTPS sin validar CA.
// Produccion: 0 = HTTPS validando la cadena de certificados.
#define USE_INSECURE_TLS_FOR_TEST 0

// Epoch aproximado por si NTP falla antes de validar TLS.
#define TLS_FALLBACK_EPOCH 1782699725UL

// Pegar la cadena PEM completa que te funciono: certificado del sitio +
// intermedios/raices, cada bloque con BEGIN/END CERTIFICATE.
#define TEMPSEGURA_ROOT_CA_PEM R"EOF(
-----BEGIN CERTIFICATE-----
PEGAR_CADENA_PEM_COMPLETA_AQUI
-----END CERTIFICATE-----
)EOF"

#endif
