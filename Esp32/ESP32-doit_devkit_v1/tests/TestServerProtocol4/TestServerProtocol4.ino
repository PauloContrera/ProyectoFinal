#include <Arduino.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <math.h>
#include <time.h>

#include "mbedtls/md.h"

// =========================
// CONFIGURACION DE PRUEBA
// =========================

#define WIFI_SSID "Moran"
#define WIFI_PASSWORD "02830193"

#define BASE_URL "https://tempsegura.orbitar.dev/api"
#define ACTIVATION_KEYWORD "e7d106732a19df38"

#define FIRMWARE_VERSION "1.0.0"
#define PROTOCOL_VERSION "2.0"

// Si vale 1, fuerza registrar de nuevo y pisa el shared_secret guardado.
#define FORCE_REGISTER 0

// Usar solo si el backend no devuelve provisioning.shared_secret y el encargado
// te pasa el secreto manualmente para esta MAC.
#define MANUAL_SHARED_SECRET "b45f063b9605742450c78649b78e18aca47e9b4fc23571e60e046fa3853f0462"

// Para una prueba rapida usamos 1: HTTPS sin validar CA.
// Para produccion debe ser 0 y hay que pegar el certificado raiz correcto.
#define USE_INSECURE_TLS_FOR_TEST 0

// Pegar aca el certificado raiz que firme tempsegura.orbitar.dev.
// Mientras USE_INSECURE_TLS_FOR_TEST sea 1, este bloque no se usa.
static const char TEMPSEGURA_ROOT_CA[] PROGMEM = R"EOF(
-----BEGIN CERTIFICATE-----
MIIDtTCCAzygAwIBAgISBjloucZPAdkwZXudPLSOXRWJMAoGCCqGSM49BAMDMDMx
CzAJBgNVBAYTAlVTMRYwFAYDVQQKEw1MZXQncyBFbmNyeXB0MQwwCgYDVQQDEwNZ
RTEwHhcNMjYwNjA4MTg0NTUwWhcNMjYwOTA2MTg0NTQ5WjAhMR8wHQYDVQQDExZ0
ZW1wc2VndXJhLm9yYml0YXIuZGV2MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE
e27Y8r7GA4yWahl0sruyevEo2spvRjYch2A6/2vMhwW+gKZ+QW7V6BviQcEnNsUW
RsVrRooXbQG8eC0qhNgTGKOCAkAwggI8MA4GA1UdDwEB/wQEAwIHgDATBgNVHSUE
DDAKBggrBgEFBQcDATAMBgNVHRMBAf8EAjAAMB0GA1UdDgQWBBSZdh3Egr5u3Csp
zFxEKaVk1GbTQTAfBgNVHSMEGDAWgBS7IMpHC/7X5Zz5jwkqo4w3RbG82DAzBggr
BgEFBQcBAQQnMCUwIwYIKwYBBQUHMAKGF2h0dHA6Ly95ZTEuaS5sZW5jci5vcmcv
MD0GA1UdEQQ2MDSCFnRlbXBzZWd1cmEub3JiaXRhci5kZXaCGnd3dy50ZW1wc2Vn
dXJhLm9yYml0YXIuZGV2MBMGA1UdIAQMMAowCAYGZ4EMAQIBMC4GA1UdHwQnMCUw
I6AhoB+GHWh0dHA6Ly95ZTEuYy5sZW5jci5vcmcvMTAuY3JsMIIBDAYKKwYBBAHW
eQIEAgSB/QSB+gD4AHYA1219ENGn9XfCx+lf1wC/+YLJM1pl4dCzAXMXwMjFaXcA
AAGeqMO7SAAABAMARzBFAiEAsdUEAik2wFb8fSbE15KbRd0KQDhVyGtxupnX/E2M
s20CIH/41ZpGBzxH82e5eg1kij0wtvvEc7InYw7ZuOfypr8DAH4AbP5QGUOoXqkW
vFLRM+TcyR7xQRx9JYQg0XOAnhgY6zoAAAGeqMO+0gAIAAAFAA94qbIEAwBHMEUC
IQDA5ujOUZ5W8qcnTBY392ZUrEJ7FdTWnGzEDATfNHNy2wIgNGUzcNXVrOXek5xd
TqGog0+B9oGS1LgD1HKs0+W+4eEwCgYIKoZIzj0EAwMDZwAwZAIwRea6zId2njIl
z7TqB9oipjsuJgrCubPJ+JTyH8PxG+elvwvetCY6H6IyxtVA6SEjAjA3YzyliQmQ
85PU90Qh66hJroa2INQjh6qrt+rxeC60kMWnrFa6yurBBepw2g+Zv1o=
-----END CERTIFICATE-----
-----BEGIN CERTIFICATE-----
MIICizCCAhGgAwIBAgIQXd1w3TH4AchcGGp6BLgK/jAKBggqhkjOPQQDAzAuMQsw
CQYDVQQGEwJVUzENMAsGA1UEChMESVNSRzEQMA4GA1UEAxMHUm9vdCBZRTAeFw0y
NTA5MDMwMDAwMDBaFw0yODA5MDIyMzU5NTlaMDMxCzAJBgNVBAYTAlVTMRYwFAYD
VQQKEw1MZXQncyBFbmNyeXB0MQwwCgYDVQQDEwNZRTEwdjAQBgcqhkjOPQIBBgUr
gQQAIgNiAAQHZVB1/mimla2hfSurylScjPMZaOJXLz/NnAc2sylm8WDyhU9Ccp+z
ASQi5vSwGGJjSGklkD9fdPR8GpyDIOIjCEfrnbt/v+ZSEPLLEGbaM6EccDbN7p9x
teIm2Avf+ryjge4wgeswDgYDVR0PAQH/BAQDAgGGMBMGA1UdJQQMMAoGCCsGAQUF
BwMBMBIGA1UdEwEB/wQIMAYBAf8CAQAwHQYDVR0OBBYEFLsgykcL/tflnPmPCSqj
jDdFsbzYMB8GA1UdIwQYMBaAFKPIJlqOoUzQNWP8myPIOq5W809WMDIGCCsGAQUF
BwEBBCYwJDAiBggrBgEFBQcwAoYWaHR0cDovL3llLmkubGVuY3Iub3JnLzATBgNV
HSAEDDAKMAgGBmeBDAECATAnBgNVHR8EIDAeMBygGqAYhhZodHRwOi8veWUuYy5s
ZW5jci5vcmcvMAoGCCqGSM49BAMDA2gAMGUCMQDgjUEahFT/h3DRakqiPZpLvPgf
Zwkt6K2EOMmh1nvEzl83eMLYcod4GCl3b0J1Nn0CMBNYmEQJb4CEG5WoOe7aRn/L
VKu6saHmHEynI7ysIPd8zQsK1HdmhlHKlw9Z5GpGvA==
-----END CERTIFICATE-----
-----BEGIN CERTIFICATE-----
MIICpjCCAiugAwIBAgIRAIchZfw0tuX7qK3Vs3BftTowCgYIKoZIzj0EAwMwTzEL
MAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2VhcmNo
IEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDIwHhcNMjYwNTEzMDAwMDAwWhcN
MzIwOTAyMjM1OTU5WjAuMQswCQYDVQQGEwJVUzENMAsGA1UEChMESVNSRzEQMA4G
A1UEAxMHUm9vdCBZRTB2MBAGByqGSM49AgEGBSuBBAAiA2IABDwS/6vhrcVqcbBo
+wgdI3fwn9x7DNJJOY/lTOti0vkwuRN87RhEhTH17E7XyFjWsPYhIPt/wzOqxTd2
b+4ZJNy9ID04YywF9U5zasDVyGSNErVNtz8uSGh5izW87j77GaOB6zCB6DAOBgNV
HQ8BAf8EBAMCAQYwEwYDVR0lBAwwCgYIKwYBBQUHAwEwDwYDVR0TAQH/BAUwAwEB
/zAdBgNVHQ4EFgQUo8gmWo6hTNA1Y/ybI8g6rlbzT1YwHwYDVR0jBBgwFoAUfEKW
rt5LSDv6kviejM9ti6lyN5UwMgYIKwYBBQUHAQEEJjAkMCIGCCsGAQUFBzAChhZo
dHRwOi8veDIuaS5sZW5jci5vcmcvMBMGA1UdIAQMMAowCAYGZ4EMAQIBMCcGA1Ud
HwQgMB4wHKAaoBiGFmh0dHA6Ly94Mi5jLmxlbmNyLm9yZy8wCgYIKoZIzj0EAwMD
aQAwZgIxAMU19WCtmxVND8UHBZRoma49Z7jPs64Dma0eTu1OChVbB/2J7GV3nvYK
Ax54uk1G9QIxAO0miLVJu8PLNiXXXkiE/gsK3CTRTF/aeo4bMX42Zw40csRU6AC2
6hSW1/IWaas6dg==
-----END CERTIFICATE-----
-----BEGIN CERTIFICATE-----
MIICGzCCAaGgAwIBAgIQQdKd0XLq7qeAwSxs6S+HUjAKBggqhkjOPQQDAzBPMQsw
CQYDVQQGEwJVUzEpMCcGA1UEChMgSW50ZXJuZXQgU2VjdXJpdHkgUmVzZWFyY2gg
R3JvdXAxFTATBgNVBAMTDElTUkcgUm9vdCBYMjAeFw0yMDA5MDQwMDAwMDBaFw00
MDA5MTcxNjAwMDBaME8xCzAJBgNVBAYTAlVTMSkwJwYDVQQKEyBJbnRlcm5ldCBT
ZWN1cml0eSBSZXNlYXJjaCBHcm91cDEVMBMGA1UEAxMMSVNSRyBSb290IFgyMHYw
EAYHKoZIzj0CAQYFK4EEACIDYgAEzZvVn4CDCuwJSvMWSj5cz3es3mcFDR0HttwW
+1qLFNvicWDEukWVEYmO6gbf9yoWHKS5xcUy4APgHoIYOIvXRdgKam7mAHf7AlF9
ItgKbppbd9/w+kHsOdx1ymgHDB/qo0IwQDAOBgNVHQ8BAf8EBAMCAQYwDwYDVR0T
AQH/BAUwAwEB/zAdBgNVHQ4EFgQUfEKWrt5LSDv6kviejM9ti6lyN5UwCgYIKoZI
zj0EAwMDaAAwZQIwe3lORlCEwkSHRhtFcP9Ymd70/aTSVaYgLXTWNLxBo1BfASdW
tL4ndQavEi51mI38AjEAi/V3bNTIZargCyzuFJ0nN6T5U6VR5CmD1/iQMVtCnwr1
/q4AaOeMSQ+2b1tbFfLn
-----END CERTIFICATE-----
)EOF";

Preferences prefs;

String deviceMac;
String sharedSecret;
uint32_t serverTime = 0;
uint32_t seq = 1;

WiFiClientSecure secureClient;

// =========================
// UTILIDADES
// =========================

void configureTls() {
#if USE_INSECURE_TLS_FOR_TEST
  secureClient.setInsecure();
#else
  secureClient.setCACert(TEMPSEGURA_ROOT_CA);
#endif
}

bool connectWifi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  Serial.print("Conectando WiFi");

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000UL) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("No se pudo conectar a WiFi.");
    return false;
  }

  Serial.print("WiFi conectado. IP: ");
  Serial.println(WiFi.localIP());

  deviceMac = WiFi.macAddress();
  Serial.print("MAC: ");
  Serial.println(deviceMac);

  return true;
}

bool syncSystemClockWithNtp() {
  configTime(
    0,
    0,
    "pool.ntp.org",
    "time.google.com",
    "time.cloudflare.com"
  );

  Serial.print("Sincronizando reloj interno por NTP");

  struct tm timeinfo;
  for (int i = 0; i < 20; i++) {
    if (getLocalTime(&timeinfo, 1000)) {
      Serial.println();
      Serial.print("Hora interna ESP32 UTC: ");
      Serial.println(&timeinfo, "%Y-%m-%d %H:%M:%S");
      return true;
    }

    Serial.print(".");
  }

  Serial.println();
  Serial.println("No se pudo sincronizar NTP.");
  return false;
}

String httpGet(const String& url, int& statusCode) {
  HTTPClient http;

  statusCode = -1;

  configureTls();

  if (!http.begin(secureClient, url)) {
    Serial.println("No se pudo iniciar GET.");
    return "";
  }

  http.addHeader("Accept", "application/json");

  statusCode = http.GET();
  String response = http.getString();

  if (statusCode < 0) {
    Serial.print("Detalle error GET: ");
    Serial.println(http.errorToString(statusCode));
  }

  http.end();

  return response;
}

String httpPostJson(const String& url, const String& body, int& statusCode) {
  HTTPClient http;

  statusCode = -1;

  configureTls();

  if (!http.begin(secureClient, url)) {
    Serial.println("No se pudo iniciar POST.");
    return "";
  }

  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");

  statusCode = http.POST(body);
  String response = http.getString();

  if (statusCode < 0) {
    Serial.print("Detalle error POST: ");
    Serial.println(http.errorToString(statusCode));
  }

  http.end();

  return response;
}

uint32_t extractUInt(const String& json, const String& key) {
  String token = "\"" + key + "\"";
  int keyIndex = json.indexOf(token);
  if (keyIndex < 0) {
    return 0;
  }

  int colonIndex = json.indexOf(':', keyIndex + token.length());
  if (colonIndex < 0) {
    return 0;
  }

  int start = colonIndex + 1;
  while (start < json.length() && isspace((unsigned char)json[start])) {
    start++;
  }

  int end = start;
  while (end < json.length() && isdigit((unsigned char)json[end])) {
    end++;
  }

  return json.substring(start, end).toInt();
}

String extractString(const String& json, const String& key) {
  String token = "\"" + key + "\"";
  int keyIndex = json.indexOf(token);
  if (keyIndex < 0) {
    return "";
  }

  int colonIndex = json.indexOf(':', keyIndex + token.length());
  if (colonIndex < 0) {
    return "";
  }

  int firstQuote = json.indexOf('"', colonIndex + 1);
  if (firstQuote < 0) {
    return "";
  }

  int secondQuote = json.indexOf('"', firstQuote + 1);
  if (secondQuote < 0) {
    return "";
  }

  return json.substring(firstQuote + 1, secondQuote);
}

String hmacSha256Hex(const String& message, const String& secret) {
  byte hmac[32];

  const mbedtls_md_info_t* mdInfo = mbedtls_md_info_from_type(MBEDTLS_MD_SHA256);

  mbedtls_md_context_t ctx;
  mbedtls_md_init(&ctx);
  mbedtls_md_setup(&ctx, mdInfo, 1);
  mbedtls_md_hmac_starts(&ctx, (const unsigned char*)secret.c_str(), secret.length());
  mbedtls_md_hmac_update(&ctx, (const unsigned char*)message.c_str(), message.length());
  mbedtls_md_hmac_finish(&ctx, hmac);
  mbedtls_md_free(&ctx);

  char hex[65];
  for (int i = 0; i < 32; i++) {
    sprintf(hex + (i * 2), "%02x", hmac[i]);
  }
  hex[64] = '\0';

  return String(hex);
}

String macWithoutSeparators(const String& mac) {
  String clean = mac;
  clean.replace(":", "");
  return clean;
}

String buildPacketId(uint32_t timestamp, uint32_t packetSeq) {
  (void)packetSeq;
  return "pkt-" + String(timestamp);
}

String formatJsonNumber(float value) {
  if (fabs(value - round(value)) < 0.0001f) {
    return String((int)round(value));
  }

  String text = String(value, 2);

  while (text.endsWith("0")) {
    text.remove(text.length() - 1);
  }

  if (text.endsWith(".")) {
    text.remove(text.length() - 1);
  }

  return text;
}

// Canonico segun indicacion:
// cuerpo sin mac/timestamp/signature, con claves ordenadas alfabeticamente.
String buildCanonicalSyncData(const String& packetId, uint32_t packetSeq, float temp, uint32_t readingTime, int rssi) {
  String tempJson = formatJsonNumber(temp);
  String json = "";
  json += "{\"data\":[{\"temp\":";
  json += tempJson;
  json += ",\"time\":";
  json += String(readingTime);
  json += "}],\"local_alerts\":[],\"optional\":{\"firmware_version\":\"";
  json += FIRMWARE_VERSION;
  json += "\",\"rssi\":";
  json += String(rssi);
  json += "},\"packet_id\":\"";
  json += packetId;
  json += "\",\"seq\":";
  json += String(packetSeq);
  json += "}";

  return json;
}

String buildSyncBody(const String& packetId, uint32_t packetSeq, const String& signature, float temp, uint32_t readingTime, int rssi) {
  String tempJson = formatJsonNumber(temp);
  String body = "";
  body += "{\"mac\":\"";
  body += deviceMac;
  body += "\",\"timestamp\":";
  body += String(serverTime);
  body += ",\"packet_id\":\"";
  body += packetId;
  body += "\",\"seq\":";
  body += String(packetSeq);
  body += ",\"signature\":\"";
  body += signature;
  body += "\",\"data\":[{\"temp\":";
  body += tempJson;
  body += ",\"time\":";
  body += String(readingTime);
  body += "}],\"local_alerts\":[],\"optional\":{\"rssi\":";
  body += String(rssi);
  body += ",\"firmware_version\":\"";
  body += FIRMWARE_VERSION;
  body += "\"}}";

  return body;
}

// =========================
// PROTOCOLO TEMPSEGURA
// =========================

bool getServerTime() {
  int statusCode;
  String response = httpGet(String(BASE_URL) + "/esp/time", statusCode);

  Serial.print("GET /esp/time HTTP ");
  Serial.println(statusCode);
  Serial.println(response);

  if (statusCode != 200) {
    return false;
  }

  serverTime = extractUInt(response, "server_time");

  if (serverTime == 0) {
    Serial.println("No se pudo extraer server_time.");
    return false;
  }

  Serial.print("server_time: ");
  Serial.println(serverTime);

  return true;
}

bool registerDevice() {
  String body = "";
  body += "{\"accion\":\"registro\",\"mac\":\"";
  body += deviceMac;
  body += "\",\"timestamp\":";
  body += String(serverTime);
  body += ",\"palabra_clave\":\"";
  body += ACTIVATION_KEYWORD;
  body += "\",\"protocol_version\":\"";
  body += PROTOCOL_VERSION;
  body += "\",\"firmware_version\":\"";
  body += FIRMWARE_VERSION;
  body += "\"}";

  Serial.println("POST /esp/register body:");
  Serial.println(body);

  int statusCode;
  String response = httpPostJson(String(BASE_URL) + "/esp/register", body, statusCode);

  Serial.print("POST /esp/register HTTP ");
  Serial.println(statusCode);
  Serial.println(response);

  if (statusCode < 200 || statusCode >= 300) {
    return false;
  }

  uint32_t responseServerTime = extractUInt(response, "server_time");
  if (responseServerTime > 0) {
    serverTime = responseServerTime;
    Serial.print("server_time actualizado desde register: ");
    Serial.println(serverTime);
  }

  String secret = extractString(response, "shared_secret");

  if (secret.length() == 0) {
    Serial.println("La respuesta no trajo shared_secret.");
    Serial.println("El backend acepto el registro, pero no devolvio el secreto para firmar.");
    Serial.println("Pedir al encargado provisioning.shared_secret o que lo incluya en /esp/register.");
    return false;
  }

  sharedSecret = secret;

  prefs.putString("secret", sharedSecret);
  prefs.putBool("registered", true);

  Serial.println("shared_secret guardado en Preferences.");

  return true;
}

bool syncFakeTemperature() {
  if (sharedSecret.length() == 0) {
    Serial.println("No hay shared_secret. No se puede firmar.");
    return false;
  }

  float temp = 4.50;
  uint32_t readingTime = serverTime;
  int rssi = WiFi.RSSI();
  uint32_t packetSeq = seq;
  String packetId = buildPacketId(serverTime, packetSeq);

  String canonicalData = buildCanonicalSyncData(packetId, packetSeq, temp, readingTime, rssi);
  String stringToSign = deviceMac + String(serverTime) + canonicalData;
  String signature = hmacSha256Hex(stringToSign, sharedSecret);
  String body = buildSyncBody(packetId, packetSeq, signature, temp, readingTime, rssi);

  Serial.println("json_data canonico:");
  Serial.println(canonicalData);

  Serial.println("String a firmar:");
  Serial.println(stringToSign);

  Serial.print("Signature: ");
  Serial.println(signature);

  Serial.println("POST /esp/sync body:");
  Serial.println(body);

  int statusCode;
  String response = httpPostJson(String(BASE_URL) + "/esp/sync", body, statusCode);

  Serial.print("POST /esp/sync HTTP ");
  Serial.println(statusCode);
  Serial.println(response);

  if (statusCode >= 200 && statusCode < 300) {
    seq++;
    prefs.putUInt("seq", seq);
    return true;
  }

  return false;
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println();
  Serial.println("Test protocolo TempSegura ESP32");

  prefs.begin("tempsegura", false);
  sharedSecret = prefs.getString("secret", "");
  seq = prefs.getUInt("seq", 1);

  if (sharedSecret.length() == 0 && String(MANUAL_SHARED_SECRET).length() > 0) {
    sharedSecret = MANUAL_SHARED_SECRET;
    prefs.putString("secret", sharedSecret);
    prefs.putBool("registered", true);
    Serial.println("MANUAL_SHARED_SECRET cargado y guardado en Preferences.");
  }

  Serial.print("Shared secret guardado: ");
  Serial.println(sharedSecret.length() > 0 ? "SI" : "NO");
  Serial.print("Seq actual: ");
  Serial.println(seq);

  if (!connectWifi()) {
    return;
  }

#if !USE_INSECURE_TLS_FOR_TEST
  if (!syncSystemClockWithNtp()) {
    Serial.println("Fallo NTP. No se puede validar TLS de forma confiable.");
    return;
  }
#endif

  if (!getServerTime()) {
    Serial.println("Fallo GET /esp/time.");
    return;
  }

  Serial.println("Paso de hora OK. Evaluando registro...");
  Serial.print("FORCE_REGISTER: ");
  Serial.println(FORCE_REGISTER ? "SI" : "NO");
  Serial.print("Shared secret disponible: ");
  Serial.println(sharedSecret.length() > 0 ? "SI" : "NO");
  Serial.print("Longitud shared_secret: ");
  Serial.println(sharedSecret.length());

  if (FORCE_REGISTER || sharedSecret.length() == 0) {
    Serial.println("Se va a ejecutar POST /esp/register.");

    if (!registerDevice()) {
      Serial.println("Fallo POST /esp/register.");
      return;
    }
  } else {
    Serial.println("Registro omitido: ya hay shared_secret guardado en Preferences.");
  }

  Serial.println("Se va a ejecutar POST /esp/sync.");

  if (!syncFakeTemperature()) {
    Serial.println("Fallo POST /esp/sync.");
    return;
  }

  Serial.println("Flujo completo OK.");
}

void loop() {
}
