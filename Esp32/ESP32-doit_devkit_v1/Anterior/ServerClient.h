#pragma once
#include <Arduino.h>
#include "types.h"

class ServerClient {
public:
  void begin();
  bool isConnected();

  bool fetchThresholds(int& umbralInf, int& umbralSup);
  bool sendTemperatureBatch(EstadoTemperatura estado, TemperatureSample* samples, uint8_t count);
  bool sendRfidEvent(const RfidEvent& event);
  bool sendPendingRecord(const PendingRecord& record);

private:
  void connectWifi();
  bool post(const String& payload);
  String uidToString(const byte uid[4]);
};

extern ServerClient serverClient;
