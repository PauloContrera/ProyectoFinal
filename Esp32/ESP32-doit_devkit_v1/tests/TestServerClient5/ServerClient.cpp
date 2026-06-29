#include "ServerClient.h"
#include "config.h"

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <math.h>
#include <time.h>

#include "mbedtls/md.h"

static const char TEMPSEGURA_ROOT_CA[] PROGMEM = TEMPSEGURA_ROOT_CA_PEM;

static Preferences prefs;
static WiFiClientSecure secureClient;

ServerClient serverClient;

void ServerClient::begin() {
  prefs.begin("tempsegura", false);

  sharedSecret = prefs.getString("secret", "");
  seq = prefs.getUInt("seq", 1);

  if (sharedSecret.length() == 0 && String(MANUAL_SHARED_SECRET).length() > 0) {
    sharedSecret = MANUAL_SHARED_SECRET;
    prefs.putString("secret", sharedSecret);
    prefs.putBool("registered", true);
    Serial.println("MANUAL_SHARED_SECRET guardado en Preferences.");
  }

  connectWifi();

#if !USE_INSECURE_TLS_FOR_TEST
  if (!syncSystemClockWithNtp()) {
    Serial.println("Fallo NTP. Usando TLS_FALLBACK_EPOCH para validar TLS.");

    struct timeval tv;
    tv.tv_sec = TLS_FALLBACK_EPOCH;
    tv.tv_usec = 0;
    settimeofday(&tv, nullptr);
  }
#endif
}

bool ServerClient::isConnected() {
  return WiFi.status() == WL_CONNECTED;
}

String ServerClient::getMac() const {
  return deviceMac;
}

uint32_t ServerClient::getSeq() const {
  return seq;
}

bool ServerClient::connectWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    deviceMac = WiFi.macAddress();
    return true;
  }

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

  deviceMac = WiFi.macAddress();

  Serial.print("WiFi conectado. IP: ");
  Serial.println(WiFi.localIP());
  Serial.print("MAC: ");
  Serial.println(deviceMac);

  return true;
}

void ServerClient::configureTls() {
#if USE_INSECURE_TLS_FOR_TEST
  secureClient.setInsecure();
#else
  secureClient.setCACert(TEMPSEGURA_ROOT_CA);
#endif
}

bool ServerClient::syncSystemClockWithNtp() {
  configTime(0, 0, "pool.ntp.org", "time.google.com", "time.cloudflare.com");

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
  return false;
}

String ServerClient::httpGet(const String& url, int& statusCode) {
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

String ServerClient::httpPostJson(const String& url, const String& body, int& statusCode) {
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

bool ServerClient::getServerTime(uint32_t& serverTimeOut) {
  if (!isConnected() && !connectWifi()) {
    return false;
  }

  int statusCode;
  String response = httpGet(String(SERVER_BASE_URL) + "/esp/time", statusCode);

  Serial.print("GET /esp/time HTTP ");
  Serial.println(statusCode);

  if (statusCode != 200) {
    Serial.println(response);
    return false;
  }

  uint32_t parsedTime = extractUInt(response, "server_time");
  if (parsedTime == 0) {
    Serial.println("No se pudo extraer server_time.");
    return false;
  }

  lastServerTime = parsedTime;
  serverTimeOut = parsedTime;
  return true;
}

bool ServerClient::ensureProvisioned(uint32_t timestamp) {
  if (FORCE_REGISTER || sharedSecret.length() == 0) {
    return registerDevice(timestamp);
  }

  return true;
}

bool ServerClient::registerDevice(uint32_t timestamp) {
  if (!isConnected() && !connectWifi()) {
    return false;
  }

  String body = "";
  body += "{\"accion\":\"registro\",\"mac\":\"";
  body += deviceMac;
  body += "\",\"timestamp\":";
  body += String(timestamp);
  body += ",\"palabra_clave\":\"";
  body += ACTIVATION_KEYWORD;
  body += "\",\"protocol_version\":\"";
  body += PROTOCOL_VERSION;
  body += "\",\"firmware_version\":\"";
  body += FIRMWARE_VERSION;
  body += "\"}";

  int statusCode;
  String response = httpPostJson(String(SERVER_BASE_URL) + "/esp/register", body, statusCode);

  Serial.print("POST /esp/register HTTP ");
  Serial.println(statusCode);

  if (statusCode < 200 || statusCode >= 300) {
    Serial.println(response);
    return false;
  }

  uint32_t responseServerTime = extractUInt(response, "server_time");
  if (responseServerTime > 0) {
    lastServerTime = responseServerTime;
  }

  String secret = extractString(response, "shared_secret");
  if (secret.length() == 0) {
    Serial.println("Registro OK, pero la respuesta no trajo shared_secret.");
    return sharedSecret.length() > 0;
  }

  sharedSecret = secret;
  prefs.putString("secret", sharedSecret);
  prefs.putBool("registered", true);

  return true;
}

bool ServerClient::syncTemperatureBatch(const ServerTemperatureSample* samples, uint8_t count, ServerConfigUpdate& configUpdate) {
  configUpdate = {};

  if (count == 0 || count > 120) {
    Serial.println("Cantidad de muestras invalida para /esp/sync.");
    return false;
  }

  if (!isConnected() && !connectWifi()) {
    return false;
  }

  uint32_t timestamp;
  if (!getServerTime(timestamp)) {
    return false;
  }

  if (!ensureProvisioned(timestamp)) {
    return false;
  }

  if (sharedSecret.length() == 0) {
    Serial.println("No hay shared_secret. No se puede firmar.");
    return false;
  }

  int rssi = WiFi.RSSI();
  uint32_t packetSeq = seq;
  String packetId = buildPacketId(timestamp, packetSeq);

  String canonicalData = buildCanonicalSyncData(packetId, packetSeq, samples, count, rssi);
  String stringToSign = deviceMac + String(timestamp) + canonicalData;
  String signature = hmacSha256Hex(stringToSign, sharedSecret);
  String body = buildSyncBody(timestamp, packetId, packetSeq, signature, samples, count, rssi);

  int statusCode;
  String response = httpPostJson(String(SERVER_BASE_URL) + "/esp/sync", body, statusCode);

  Serial.print("POST /esp/sync HTTP ");
  Serial.println(statusCode);

  if (statusCode == 403) {
    Serial.println("Cuenta deshabilitada. Encolar y reintentar luego.");
    return false;
  }

  if (statusCode < 200 || statusCode >= 300) {
    Serial.println(response);
    return false;
  }

  parseConfigUpdate(response, configUpdate);

  seq++;
  prefs.putUInt("seq", seq);

  return true;
}

String ServerClient::buildPacketId(uint32_t timestamp, uint32_t packetSeq) {
  (void)packetSeq;
  return "pkt-" + String(timestamp);
}

String ServerClient::formatJsonNumber(float value) {
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

String ServerClient::buildCanonicalSyncData(const String& packetId, uint32_t packetSeq, const ServerTemperatureSample* samples, uint8_t count, int rssi) {
  String json = "";
  json += "{\"data\":[";

  for (uint8_t i = 0; i < count; i++) {
    if (i > 0) {
      json += ",";
    }

    json += "{\"temp\":";
    json += formatJsonNumber(samples[i].temp);
    json += ",\"time\":";
    json += String(samples[i].time);
    json += "}";
  }

  json += "],\"local_alerts\":[],\"optional\":{\"firmware_version\":\"";
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

String ServerClient::buildSyncBody(uint32_t timestamp, const String& packetId, uint32_t packetSeq, const String& signature, const ServerTemperatureSample* samples, uint8_t count, int rssi) {
  String body = "";
  body += "{\"mac\":\"";
  body += deviceMac;
  body += "\",\"timestamp\":";
  body += String(timestamp);
  body += ",\"packet_id\":\"";
  body += packetId;
  body += "\",\"seq\":";
  body += String(packetSeq);
  body += ",\"signature\":\"";
  body += signature;
  body += "\",\"data\":[";

  for (uint8_t i = 0; i < count; i++) {
    if (i > 0) {
      body += ",";
    }

    body += "{\"temp\":";
    body += formatJsonNumber(samples[i].temp);
    body += ",\"time\":";
    body += String(samples[i].time);
    body += "}";
  }

  body += "],\"local_alerts\":[],\"optional\":{\"rssi\":";
  body += String(rssi);
  body += ",\"firmware_version\":\"";
  body += FIRMWARE_VERSION;
  body += "\"}}";

  return body;
}

String ServerClient::hmacSha256Hex(const String& message, const String& secret) {
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

uint32_t ServerClient::extractUInt(const String& json, const String& key) {
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

float ServerClient::extractFloat(const String& json, const String& key, float fallback) {
  String token = "\"" + key + "\"";
  int keyIndex = json.indexOf(token);
  if (keyIndex < 0) {
    return fallback;
  }

  int colonIndex = json.indexOf(':', keyIndex + token.length());
  if (colonIndex < 0) {
    return fallback;
  }

  int start = colonIndex + 1;
  while (start < json.length() && isspace((unsigned char)json[start])) {
    start++;
  }

  int end = start;
  while (end < json.length() && (isdigit((unsigned char)json[end]) || json[end] == '.' || json[end] == '-')) {
    end++;
  }

  return json.substring(start, end).toFloat();
}

String ServerClient::extractString(const String& json, const String& key) {
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

bool ServerClient::extractBool(const String& json, const String& key, bool fallback) {
  String token = "\"" + key + "\"";
  int keyIndex = json.indexOf(token);
  if (keyIndex < 0) {
    return fallback;
  }

  int colonIndex = json.indexOf(':', keyIndex + token.length());
  if (colonIndex < 0) {
    return fallback;
  }

  int start = colonIndex + 1;
  while (start < json.length() && isspace((unsigned char)json[start])) {
    start++;
  }

  if (json.startsWith("true", start)) {
    return true;
  }

  if (json.startsWith("false", start)) {
    return false;
  }

  return fallback;
}

void ServerClient::parseConfigUpdate(const String& response, ServerConfigUpdate& configUpdate) {
  configUpdate.cambio = extractBool(response, "cambio", false);

  if (!configUpdate.cambio) {
    return;
  }

  configUpdate.tempMin = extractFloat(response, "temp_min", 0.0f);
  configUpdate.tempMax = extractFloat(response, "temp_max", 0.0f);
  configUpdate.tiempoEspera = extractUInt(response, "tiempo_espera");
  configUpdate.maxBatchSize = (uint16_t)extractUInt(response, "max_batch_size");
  configUpdate.retryBaseSeconds = extractUInt(response, "retry_base_seconds");
}
