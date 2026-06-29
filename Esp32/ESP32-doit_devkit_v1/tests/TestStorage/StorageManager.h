#ifndef STORAGE_MANAGER_H
#define STORAGE_MANAGER_H

#include <Arduino.h>
#include <EEPROM.h>
#include "types.h"

class ServerClient;

class StorageManager {
public:
  void begin();

  int getUmbralInf() const;
  int getUmbralSup() const;
  void saveThresholds(int umbralInf, int umbralSup);

  void saveTemperatureBatch(EstadoTemperatura estado, TemperatureSample* samples, uint8_t count);
  void saveRfidEvent(const RfidEvent& event);

  void flushPending(ServerClient& client);
  void debugPrintPending();
private:
  int umbralInf;
  int umbralSup;

  int recordAddress(uint8_t index);
  bool readRecord(uint8_t index, PendingRecord& record);
  void writeRecord(uint8_t index, const PendingRecord& record);
  void clearRecord(uint8_t index);
  int findFreeSlot();
};

extern StorageManager storageManager;

#endif