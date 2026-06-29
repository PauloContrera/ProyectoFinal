#include <Arduino.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Preferences.h>

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

// Para una prueba rapida usamos 1: HTTPS sin validar CA.
// Para produccion debe ser 0 y hay que pegar el certificado raiz correcto.
#define USE_INSECURE_TLS_FOR_TEST 1

// Pegar aca el certificado raiz que firme tempsegura.orbitar.dev.
// Mientras USE_INSECURE_TLS_FOR_TEST sea 1, este bloque no se usa.
static const char TEMPSEGURA_ROOT_CA[] PROGMEM = R"EOF(
-----BEGIN CERTIFICATE-----
PEGAR_CERTIFICADO_RAIZ_AQUI
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
  return "pkt-" + macWithoutSeparators(deviceMac) + "-" + String(timestamp) + "-" + String(packetSeq);
}

// Canonico segun indicacion:
// cuerpo sin mac/timestamp/signature, con claves ordenadas alfabeticamente.
String buildCanonicalSyncData(const String& packetId, uint32_t packetSeq, float temp, uint32_t readingTime, int rssi) {
  String json = "";
  json += "{\"data\":[{\"temp\":";
  json += String(temp, 2);
  json += ",\"time\":";
  json += String(readingTime);
  json += "}],\"local_alerts\":[],\"optional\":{\"bateria\":100,\"firmware_version\":\"";
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
  body += String(temp, 2);
  body += ",\"time\":";
  body += String(readingTime);
  body += "}],\"local_alerts\":[],\"optional\":{\"rssi\":";
  body += String(rssi);
  body += ",\"bateria\":100,\"firmware_version\":\"";
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

  String secret = extractString(response, "shared_secret");

  if (secret.length() == 0) {
    Serial.println("La respuesta no trajo provisioning.shared_secret.");
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

  Serial.print("Shared secret guardado: ");
  Serial.println(sharedSecret.length() > 0 ? "SI" : "NO");
  Serial.print("Seq actual: ");
  Serial.println(seq);

  if (!connectWifi()) {
    return;
  }

  if (!getServerTime()) {
    Serial.println("Fallo GET /esp/time.");
    return;
  }

  if (FORCE_REGISTER || sharedSecret.length() == 0) {
    if (!registerDevice()) {
      Serial.println("Fallo POST /esp/register.");
      return;
    }
  }

  if (!syncFakeTemperature()) {
    Serial.println("Fallo POST /esp/sync.");
    return;
  }

  Serial.println("Flujo completo OK.");
}

void loop() {
}
