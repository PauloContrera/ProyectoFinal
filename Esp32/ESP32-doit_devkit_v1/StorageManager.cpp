#include "StorageManager.h"
#include "ServerClient.h"
#include "config.h"

#define ADDR_UMBRAL_INF 0
#define ADDR_UMBRAL_SUP 4
#define ADDR_RECORDS 16

StorageManager storageManager;

void StorageManager::begin() {
  EEPROM.begin(EEPROM_SIZE);

  EEPROM.get(ADDR_UMBRAL_INF, umbralInf);
  EEPROM.get(ADDR_UMBRAL_SUP, umbralSup);

  if (umbralInf < -40 || umbralInf > 100) {
    umbralInf = DEFAULT_UMBRAL_INF;
  }

  if (umbralSup < -40 || umbralSup > 100) {
    umbralSup = DEFAULT_UMBRAL_SUP;
  }
}

int StorageManager::getUmbralInf() const {
  return umbralInf;
}

int StorageManager::getUmbralSup() const {
  return umbralSup;
}

void StorageManager::saveThresholds(int newUmbralInf, int newUmbralSup) {
  umbralInf = newUmbralInf;
  umbralSup = newUmbralSup;

  EEPROM.put(ADDR_UMBRAL_INF, umbralInf);
  EEPROM.put(ADDR_UMBRAL_SUP, umbralSup);
  EEPROM.commit();
}

void StorageManager::saveTemperatureBatch(EstadoTemperatura estado, TemperatureSample* samples, uint8_t count) {
  for (uint8_t i = 0; i < count; i++) {
    int slot = findFreeSlot();

    if (slot < 0) {
      Serial.println("EEPROM llena. No se pudo guardar temperatura.");
      return;
    }

    PendingRecord record;
    memset(&record, 0, sizeof(record));

    record.used = 1;
    record.kind = 1;
    record.estado = estado;
    record.temperature = samples[i].value;

    strncpy(record.timestamp, samples[i].timestamp, sizeof(record.timestamp));
    record.timestamp[sizeof(record.timestamp) - 1] = '\0';

    writeRecord(slot, record);
  }

  EEPROM.commit();
}

void StorageManager::saveRfidEvent(const RfidEvent& event) {
  int slot = findFreeSlot();

  if (slot < 0) {
    Serial.println("EEPROM llena. No se pudo guardar RFID.");
    return;
  }

  PendingRecord record;
  memset(&record, 0, sizeof(record));

  record.used = 1;
  record.kind = 2;
  record.rfidTipo = event.tipo;
  memcpy(record.uid, event.uid, 4);

  writeRecord(slot, record);
  EEPROM.commit();
}

void StorageManager::flushPending(ServerClient& client) {
  for (uint8_t i = 0; i < STORAGE_MAX_RECORDS; i++) {
    PendingRecord record;

    if (!readRecord(i, record) || record.used != 1) {
      continue;
    }

    if (client.sendPendingRecord(record)) {
      clearRecord(i);
      EEPROM.commit();
    } else {
      return;
    }
  }
}

int StorageManager::recordAddress(uint8_t index) {
  return ADDR_RECORDS + index * sizeof(PendingRecord);
}

bool StorageManager::readRecord(uint8_t index, PendingRecord& record) {
  if (index >= STORAGE_MAX_RECORDS) {
    return false;
  }

  EEPROM.get(recordAddress(index), record);
  return true;
}

void StorageManager::writeRecord(uint8_t index, const PendingRecord& record) {
  if (index >= STORAGE_MAX_RECORDS) {
    return;
  }

  EEPROM.put(recordAddress(index), record);
}

void StorageManager::clearRecord(uint8_t index) {
  PendingRecord empty;
  memset(&empty, 0, sizeof(empty));

  writeRecord(index, empty);
}

int StorageManager::findFreeSlot() {
  for (uint8_t i = 0; i < STORAGE_MAX_RECORDS; i++) {
    PendingRecord record;
    readRecord(i, record);

    if (record.used != 1) {
      return i;
    }
  }

  return -1;
}

/*
void StorageManager::debugPrintPending() {
  Serial.println("Registros pendientes en EEPROM:");

  for (uint8_t i = 0; i < STORAGE_MAX_RECORDS; i++) {
    PendingRecord record;

    if (!readRecord(i, record) || record.used != 1) {
      continue;
    }

    Serial.print("Slot ");
    Serial.print(i);
    Serial.print(" | kind: ");
    Serial.print(record.kind);

    if (record.kind == 1) {
      Serial.print(" | estado: ");
      Serial.print(record.estado);
      Serial.print(" | temp: ");
      Serial.print(record.temperature);
      Serial.print(" | timestamp: ");
      Serial.println(record.timestamp);
    } else if (record.kind == 2) {
      Serial.print(" | RFID tipo: ");
      Serial.print(record.rfidTipo);
      Serial.print(" | UID: ");

      for (byte j = 0; j < 4; j++) {
        if (record.uid[j] < 0x10) {
          Serial.print("0");
        }

        Serial.print(record.uid[j], HEX);
      }

      Serial.println();
    }
  }
} 
*/
