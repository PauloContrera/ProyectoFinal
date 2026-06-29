/*
  Temp Segura - referencia ESP32 protocolo HTTP robusto

  Librerias Arduino:
  - WiFi
  - HTTPClient
  - ArduinoJson

  Para produccion usar HTTPS con certificado CA en ROOT_CA.
  Para desarrollo local se permite HTTP contra http://localhost:8000/api desde la misma red.
*/

#include <Arduino.h>
#include <WiFi.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Preferences.h>
#include <mbedtls/md.h>
#include <time.h>
#include <sys/time.h>

const char* WIFI_SSID = "REPLACE_WITH_WIFI";
const char* WIFI_PASS = "REPLACE_WITH_PASSWORD";

String API_PRIMARY = "http://192.168.0.100:8000/api";
String API_BACKUP = "";

const char* DEVICE_MAC = "A1:B2:C3:D4:E5:01";
const char* ACTIVATION_KEYWORD = "clavesecreta4321";
const char* DEVICE_MODEL = "ESP32-GSM-V3";
const char* FIRMWARE_VERSION = "2.0.0";
const char* PROTOCOL_VERSION = "2.0";

const char* ROOT_CA = R"EOF(
-----BEGIN CERTIFICATE-----
REPLACE_WITH_SERVER_CA_CERTIFICATE_FOR_HTTPS
-----END CERTIFICATE-----
)EOF";

uint32_t sequenceNumber = 0;
uint32_t sendIntervalSeconds = 900;
float tempMin = 2.0;
float tempMax = 8.0;
String deviceSecret = "local-dev-esp-secret";
Preferences preferences;

String normalizeMac(const String& mac) {
  String compact = "";
  for (size_t i = 0; i < mac.length(); i++) {
    char c = mac[i];
    if (isxdigit(c)) compact += (char)toupper(c);
  }

  String out = "";
  for (size_t i = 0; i < compact.length() && i < 12; i += 2) {
    if (i > 0) out += ":";
    out += compact.substring(i, i + 2);
  }
  return out;
}

String hmacSha256Hex(const String& message, const String& secret) {
  byte hmac[32];
  mbedtls_md_context_t ctx;
  const mbedtls_md_info_t* info = mbedtls_md_info_from_type(MBEDTLS_MD_SHA256);

  mbedtls_md_init(&ctx);
  mbedtls_md_setup(&ctx, info, 1);
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

bool waitForWifi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long startedAt = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startedAt < 20000) {
    delay(500);
  }

  return WiFi.status() == WL_CONNECTED;
}

bool syncClockFromNtp() {
  configTime(0, 0, "pool.ntp.org", "time.google.com", "time.cloudflare.com");

  time_t now = time(nullptr);
  unsigned long startedAt = millis();
  while (now < 1700000000 && millis() - startedAt < 15000) {
    delay(500);
    now = time(nullptr);
  }

  return now >= 1700000000;
}

bool syncClockFromApi() {
  HTTPClient http;
  String url = API_PRIMARY + "/esp/time";

  WiFiClient client;
  if (!http.begin(client, url)) return false;

  int code = http.GET();
  String response = http.getString();
  http.end();

  if (code != 200) return false;

  StaticJsonDocument<256> doc;
  if (deserializeJson(doc, response)) return false;

  time_t serverTime = doc["server_time"] | 0;
  if (serverTime < 1700000000) return false;

  timeval tv = { serverTime, 0 };
  settimeofday(&tv, nullptr);
  return true;
}

String packetId(const String& type) {
  sequenceNumber++;
  return normalizeMac(DEVICE_MAC) + ":" + type + ":" + String((uint32_t)time(nullptr)) + ":" + String(sequenceNumber);
}

String signCanonicalJson(const String& canonicalJson, time_t timestamp) {
  String message = normalizeMac(DEVICE_MAC) + String((uint32_t)timestamp) + canonicalJson;
  return hmacSha256Hex(message, deviceSecret);
}

bool postJsonToUrl(const String& baseUrl, const String& endpoint, const String& payload, int& statusCode, String& response) {
  HTTPClient http;
  String url = baseUrl + endpoint;

  WiFiClient plainClient;
  WiFiClientSecure secureClient;
  bool https = url.startsWith("https://");

  if (https) {
    secureClient.setCACert(ROOT_CA);
    if (!http.begin(secureClient, url)) return false;
  } else {
    if (!http.begin(plainClient, url)) return false;
  }

  http.setTimeout(10000);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("Connection", "close");

  statusCode = http.POST(payload);
  response = http.getString();
  http.end();

  return statusCode > 0;
}

bool postWithRetry(const String& endpoint, const String& payload, String& response) {
  const uint8_t maxAttempts = 3;
  uint32_t delayMs = 1000;

  for (uint8_t attempt = 1; attempt <= maxAttempts; attempt++) {
    int statusCode = 0;
    if (postJsonToUrl(API_PRIMARY, endpoint, payload, statusCode, response) && statusCode >= 200 && statusCode < 300) {
      return true;
    }

    delay(delayMs + random(0, 500));
    delayMs = min<uint32_t>(delayMs * 2, 30000);
  }

  if (API_BACKUP.length() > 0) {
    int statusCode = 0;
    return postJsonToUrl(API_BACKUP, endpoint, payload, statusCode, response) && statusCode >= 200 && statusCode < 300;
  }

  return false;
}

void applyServerResponse(const String& response) {
  StaticJsonDocument<1536> doc;
  if (deserializeJson(doc, response)) return;

  if (doc["server_time"].is<uint32_t>()) {
    time_t serverTime = doc["server_time"];
    if (abs((long)(serverTime - time(nullptr))) > 60) {
      timeval tv = { serverTime, 0 };
      settimeofday(&tv, nullptr);
    }
  }

  if (doc["provisioning"]["shared_secret"].is<const char*>()) {
    deviceSecret = String((const char*)doc["provisioning"]["shared_secret"]);
    preferences.putString("secret", deviceSecret);
  }

  if (!doc["config"].is<JsonObject>()) return;

  JsonObject config = doc["config"];
  tempMin = config["temp_min"] | tempMin;
  tempMax = config["temp_max"] | tempMax;
  sendIntervalSeconds = config["tiempo_espera"] | sendIntervalSeconds;
  API_BACKUP = String((const char*)(config["url_backup"] | ""));
}

String buildRegisterPayload() {
  time_t now = time(nullptr);

  StaticJsonDocument<512> doc;
  doc["accion"] = "registro";
  doc["mac"] = normalizeMac(DEVICE_MAC);
  doc["modelo"] = DEVICE_MODEL;
  doc["firmware_version"] = FIRMWARE_VERSION;
  doc["protocol_version"] = PROTOCOL_VERSION;
  doc["timestamp"] = (uint32_t)now;
  doc["palabra_clave"] = ACTIVATION_KEYWORD;

  String payload;
  serializeJson(doc, payload);
  return payload;
}

String buildSyncPayload(float temperature) {
  time_t now = time(nullptr);
  String id = packetId("sync");

  // JSON canonico que se firma: claves ordenadas alfabeticamente.
  StaticJsonDocument<1024> signedPart;
  JsonArray data = signedPart.createNestedArray("data");
  JsonObject reading = data.createNestedObject();
  reading["temp"] = serialized(String(temperature, 1));
  reading["time"] = (uint32_t)now;

  JsonArray alerts = signedPart.createNestedArray("local_alerts");
  if (temperature < tempMin || temperature > tempMax) {
    JsonObject alert = alerts.createNestedObject();
    alert["temp"] = serialized(String(temperature, 1));
    alert["time"] = (uint32_t)now;
    alert["type"] = temperature > tempMax ? "temp_high" : "temp_low";
  }

  JsonObject optional = signedPart.createNestedObject("optional");
  optional["battery_level"] = 100;
  optional["signal_strength"] = WiFi.RSSI();
  optional["uptime"] = (uint32_t)(millis() / 1000);
  signedPart["packet_id"] = id;
  signedPart["seq"] = sequenceNumber;

  String canonicalJson;
  serializeJson(signedPart, canonicalJson);

  StaticJsonDocument<1280> full;
  full["mac"] = normalizeMac(DEVICE_MAC);
  full["timestamp"] = (uint32_t)now;
  full["packet_id"] = id;
  full["seq"] = sequenceNumber;
  full["data"] = signedPart["data"];
  full["local_alerts"] = signedPart["local_alerts"];
  full["optional"] = signedPart["optional"];
  full["signature"] = signCanonicalJson(canonicalJson, now);

  String payload;
  serializeJson(full, payload);
  return payload;
}

void setup() {
  Serial.begin(115200);
  delay(500);
  preferences.begin("tempsegura", false);
  deviceSecret = preferences.getString("secret", deviceSecret);

  if (!waitForWifi()) {
    Serial.println("WiFi no disponible. Guardar lecturas localmente y reintentar.");
    return;
  }

  if (!syncClockFromNtp()) {
    syncClockFromApi();
  }

  String response;
  if (postWithRetry("/esp/register", buildRegisterPayload(), response)) {
    applyServerResponse(response);
  }
}

void loop() {
  static unsigned long lastSend = 0;

  if (millis() - lastSend < sendIntervalSeconds * 1000UL) {
    delay(250);
    return;
  }

  lastSend = millis();

  float temperature = 4.8; // Reemplazar por lectura DS18B20 real.
  String payload = buildSyncPayload(temperature);
  String response;

  if (postWithRetry("/esp/sync", payload, response)) {
    applyServerResponse(response);
    Serial.println("Sync OK:");
    Serial.println(response);
  } else {
    Serial.println("Sync fallo. Persistir payload en cola local para reintento.");
  }
}
