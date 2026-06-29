#pragma once
#include <Arduino.h>
#include <SPI.h>
#include <MFRC522.h>
#include "types.h"

class RfidManager {
public:
  RfidManager();

  void begin();
  RfidEvent readEvent();

private:
  MFRC522 readerCarga;
  MFRC522 readerDescarga;

  byte vacuna1[4] = {0x3A, 0x5C, 0x33, 0x02};
  byte vacuna2[4] = {0x33, 0x94, 0xBC, 0xD9};

  bool readFrom(MFRC522& reader, TipoRfid tipo, RfidEvent& event);
  bool isKnownUid(byte uid[4]);
  bool compareUid(byte a[4], byte b[4]);
  void printUid(byte uid[4]);
};

extern RfidManager rfidManager;
