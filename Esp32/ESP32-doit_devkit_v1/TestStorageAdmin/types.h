#ifndef TYPES_H
#define TYPES_H

#include <Arduino.h>

enum EstadoTemperatura : uint8_t {
  ESTADO_NORMAL = 1,
  ESTADO_ALERTA = 2,
  ESTADO_PELIGRO = 3
};

enum TipoRfid : uint8_t {
  RFID_NINGUNO = 0,
  RFID_CARGA = 1,
  RFID_DESCARGA = 2
};

struct TemperatureSample {
  float value;
  char timestamp[20];
};

struct RfidEvent {
  bool valid;
  TipoRfid tipo;
  byte uid[4];
};

struct PendingRecord {
  uint8_t used;
  uint8_t kind;   // 1 temp, 2 rfid
  uint8_t estado;
  uint8_t rfidTipo;
  float temperature;
  char timestamp[20];
  byte uid[4];
};

#endif