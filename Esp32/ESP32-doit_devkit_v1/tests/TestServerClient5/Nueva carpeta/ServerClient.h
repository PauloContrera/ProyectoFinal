#ifndef SERVER_CLIENT_H
#define SERVER_CLIENT_H

#include <Arduino.h>

struct ServerTemperatureSample {
  float temp;
  uint32_t time;
};

struct ServerConfigUpdate {
  bool cambio;
  float tempMin;
  float tempMax;
  uint32_t tiempoEspera;
  uint16_t maxBatchSize;
  uint32_t retryBaseSeconds;
};

class ServerClient {
public:
  void begin();

  bool isConnected();
  bool getServerTime(uint32_t& serverTimeOut);
  bool ensureProvisioned(uint32_t timestamp);
  bool syncTemperatureBatch(const ServerTemperatureSample* samples, uint8_t count, ServerConfigUpdate& configUpdate);

  String getMac() const;
  uint32_t getSeq() const;

private:
  String deviceMac;
  String sharedSecret;
  uint32_t seq;
  uint32_t lastServerTime;

  bool connectWifi();
  void configureTls();
  bool syncSystemClockWithNtp();

  String httpGet(const String& url, int& statusCode);
  String httpPostJson(const String& url, const String& body, int& statusCode);

  bool registerDevice(uint32_t timestamp);

  String buildPacketId(uint32_t timestamp, uint32_t packetSeq);
  String buildCanonicalSyncData(const String& packetId, uint32_t packetSeq, const ServerTemperatureSample* samples, uint8_t count, int rssi);
  String buildSyncBody(uint32_t timestamp, const String& packetId, uint32_t packetSeq, const String& signature, const ServerTemperatureSample* samples, uint8_t count, int rssi);

  String hmacSha256Hex(const String& message, const String& secret);
  String formatJsonNumber(float value);

  uint32_t extractUInt(const String& json, const String& key);
  float extractFloat(const String& json, const String& key, float fallback);
  String extractString(const String& json, const String& key);
  bool extractBool(const String& json, const String& key, bool fallback);
  void parseConfigUpdate(const String& response, ServerConfigUpdate& configUpdate);
};

extern ServerClient serverClient;

#endif
