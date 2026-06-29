#include "ServerClient.h"
#include "config.h"

#ifdef ESP32
  #include <WiFi.h>
  #include <HTTPClient.h>
#else
  #include <ESP8266WiFi.h>
  #include <ESP8266HTTPClient.h>
#endif

ServerClient serverClient;

void ServerClient::begin() {
  connectWifi();
}

void ServerClient::connectWifi() {
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  Serial.print("Conectando WiFi");

  unsigned long start = millis();

  while (WiFi.status() != WL_CONNECTED && millis() - start < 15000UL) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("WiFi conectado. IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("No se pudo conectar a WiFi.");
  }
}

bool ServerClient::isConnected() {
  return WiFi.status() == WL_CONNECTED;
}

bool ServerClient::fetchThresholds(int& umbralInf, int& umbralSup) {
  if (!isConnected()) {
    return false;
  }

  HTTPClient http;
  String url = String(CONFIG_URL) + "?user=" + USER_ID + "&pass=" + USER_PASS;

  http.begin(url);
  int code = http.GET();

  if (code != 200) {
    http.end();
    return false;
  }

  String body = http.getString();
  http.end();

  int infIndex = body.indexOf("inf=");
  int supIndex = body.indexOf("sup=");

  if (infIndex < 0 || supIndex < 0) {
    return false;
  }

  umbralInf = body.substring(infIndex + 4).toInt();
  umbralSup = body.substring(supIndex + 4).toInt();

  return true;
}

bool ServerClient::sendTemperatureBatch(EstadoTemperatura estado, TemperatureSample* samples, uint8_t count) {
  for (uint8_t i = 0; i < count; i++) {
    String payload = "user=" + String(USER_ID) +
                     "&pass=" + String(USER_PASS) +
                     "&tipo=temperatura" +
                     "&estado=" + String((int)estado) +
                     "&temperatura=" + String(samples[i].value, 2) +
                     "&timestamp=" + String(samples[i].timestamp);

    if (!post(payload)) {
      return false;
    }
  }

  return true;
}

bool ServerClient::sendRfidEvent(const RfidEvent& event) {
  String payload = "user=" + String(USER_ID) +
                   "&pass=" + String(USER_PASS) +
                   "&tipo=rfid" +
                   "&movimiento=" + String((int)event.tipo) +
                   "&uid=" + uidToString(event.uid);

  return post(payload);
}

bool ServerClient::sendPendingRecord(const PendingRecord& record) {
  if (record.kind == 1) {
    TemperatureSample sample;
    sample.value = record.temperature;
    strncpy(sample.timestamp, record.timestamp, sizeof(sample.timestamp));

    return sendTemperatureBatch((EstadoTemperatura)record.estado, &sample, 1);
  }

  if (record.kind == 2) {
    RfidEvent event;
    event.valid = true;
    event.tipo = (TipoRfid)record.rfidTipo;
    memcpy(event.uid, record.uid, 4);

    return sendRfidEvent(event);
  }

  return false;
}

bool ServerClient::post(const String& payload) {
  if (!isConnected()) {
    return false;
  }

  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  int code = http.POST(payload);

  if (code > 0) {
    Serial.print("HTTP POST: ");
    Serial.println(code);

    if (code == 200) {
      Serial.println(http.getString());
      http.end();
      return true;
    }
  } else {
    Serial.print("Error POST: ");
    Serial.println(code);
  }

  http.end();
  return false;
}

String ServerClient::uidToString(const byte uid[4]) {
  char buffer[12];

  snprintf(
    buffer,
    sizeof(buffer),
    "%02X%02X%02X%02X",
    uid[0],
    uid[1],
    uid[2],
    uid[3]
  );

  return String(buffer);
}
