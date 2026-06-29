#include <Arduino.h>
#include <EEPROM.h>
#include "config.h"
#include "types.h"

#define ADDR_UMBRAL_INF 0
#define ADDR_UMBRAL_SUP 4
#define ADDR_RECORDS 16

int recordAddress(uint8_t index) {
  return ADDR_RECORDS + index * sizeof(PendingRecord);
}

void printMenu() {
  Serial.println();
  Serial.println("===== EEPROM ADMIN =====");
  Serial.println("1 - Leer umbrales");
  Serial.println("2 - Cambiar umbrales a 2 y 8");
  Serial.println("3 - Cambiar umbrales manualmente");
  Serial.println("4 - Listar registros pendientes");
  Serial.println("5 - Borrar todos los registros pendientes");
  Serial.println("6 - Borrar TODA la EEPROM");
  Serial.println("7 - Crear registro temperatura de prueba");
  Serial.println("8 - Crear registro RFID de prueba");
  Serial.println("========================");
  Serial.println("Ingrese opcion:");
}

int readIntFromSerial() {
  while (!Serial.available()) {
    delay(10);
  }

  String value = Serial.readStringUntil('\n');
  value.trim();

  return value.toInt();
}

void readThresholds() {
  int inf;
  int sup;

  EEPROM.get(ADDR_UMBRAL_INF, inf);
  EEPROM.get(ADDR_UMBRAL_SUP, sup);

  Serial.print("Umbral inferior: ");
  Serial.println(inf);

  Serial.print("Umbral superior: ");
  Serial.println(sup);
}

void saveThresholds(int inf, int sup) {
  EEPROM.put(ADDR_UMBRAL_INF, inf);
  EEPROM.put(ADDR_UMBRAL_SUP, sup);
  EEPROM.commit();

  Serial.println("Umbrales guardados.");
}

void listRecords() {
  Serial.println("Registros pendientes:");

  bool any = false;

  for (uint8_t i = 0; i < STORAGE_MAX_RECORDS; i++) {
    PendingRecord record;
    EEPROM.get(recordAddress(i), record);

    if (record.used != 1) {
      continue;
    }

    any = true;

    Serial.print("Slot ");
    Serial.print(i);
    Serial.print(" | kind=");
    Serial.print(record.kind);

    if (record.kind == 1) {
      Serial.print(" TEMP");
      Serial.print(" | estado=");
      Serial.print(record.estado);
      Serial.print(" | temp=");
      Serial.print(record.temperature);
      Serial.print(" | timestamp=");
      Serial.println(record.timestamp);
    } else if (record.kind == 2) {
      Serial.print(" RFID");
      Serial.print(" | tipo=");
      Serial.print(record.rfidTipo);
      Serial.print(" | uid=");

      for (byte j = 0; j < 4; j++) {
        if (record.uid[j] < 0x10) {
          Serial.print("0");
        }

        Serial.print(record.uid[j], HEX);
      }

      Serial.println();
    } else {
      Serial.println(" | tipo desconocido");
    }
  }

  if (!any) {
    Serial.println("No hay registros pendientes.");
  }
}

void clearRecords() {
  PendingRecord empty;
  memset(&empty, 0, sizeof(empty));

  for (uint8_t i = 0; i < STORAGE_MAX_RECORDS; i++) {
    EEPROM.put(recordAddress(i), empty);
  }

  EEPROM.commit();

  Serial.println("Registros pendientes borrados.");
}

void clearAllEeprom() {
  for (int i = 0; i < EEPROM_SIZE; i++) {
    EEPROM.write(i, 0);
  }

  EEPROM.commit();

  Serial.println("EEPROM completa borrada.");
}

int findFreeSlot() {
  for (uint8_t i = 0; i < STORAGE_MAX_RECORDS; i++) {
    PendingRecord record;
    EEPROM.get(recordAddress(i), record);

    if (record.used != 1) {
      return i;
    }
  }

  return -1;
}

void createTemperatureRecord() {
  int slot = findFreeSlot();

  if (slot < 0) {
    Serial.println("No hay slots libres.");
    return;
  }

  PendingRecord record;
  memset(&record, 0, sizeof(record));

  record.used = 1;
  record.kind = 1;
  record.estado = ESTADO_PELIGRO;
  record.temperature = 21.62;
  strncpy(record.timestamp, "2026-06-14 18:30:00", sizeof(record.timestamp));
  record.timestamp[sizeof(record.timestamp) - 1] = '\0';

  EEPROM.put(recordAddress(slot), record);
  EEPROM.commit();

  Serial.print("Registro temperatura creado en slot ");
  Serial.println(slot);
}

void createRfidRecord() {
  int slot = findFreeSlot();

  if (slot < 0) {
    Serial.println("No hay slots libres.");
    return;
  }

  PendingRecord record;
  memset(&record, 0, sizeof(record));

  record.used = 1;
  record.kind = 2;
  record.rfidTipo = RFID_CARGA;
  record.uid[0] = 0x90;
  record.uid[1] = 0x0E;
  record.uid[2] = 0xE4;
  record.uid[3] = 0xA4;

  EEPROM.put(recordAddress(slot), record);
  EEPROM.commit();

  Serial.print("Registro RFID creado en slot ");
  Serial.println(slot);
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  EEPROM.begin(EEPROM_SIZE);

  Serial.println("Administrador EEPROM iniciado.");
  printMenu();
}

void loop() {
  if (!Serial.available()) {
    return;
  }

  String input = Serial.readStringUntil('\n');
  input.trim();

  if (input.length() == 0) {
    return;
  }

  int option = input.toInt();

  switch (option) {
    case 1:
      readThresholds();
      break;

    case 2:
      saveThresholds(2, 8);
      break;

    case 3: {
      Serial.println("Ingrese umbral inferior:");
      int inf = readIntFromSerial();

      Serial.println("Ingrese umbral superior:");
      int sup = readIntFromSerial();

      saveThresholds(inf, sup);
      break;
    }

    case 4:
      listRecords();
      break;

    case 5:
      clearRecords();
      break;

    case 6:
      clearAllEeprom();
      break;

    case 7:
      createTemperatureRecord();
      break;

    case 8:
      createRfidRecord();
      break;

    default:
      Serial.println("Opcion invalida.");
      break;
  }

  printMenu();
}