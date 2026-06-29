#include "RfidManager.h"
#include "config.h"

RfidManager rfidManager;

RfidManager::RfidManager()
  : readerCarga(RFID_SS1_CARGA, RFID1_RST),
    readerDescarga(RFID_SS2_DESCARGA, RFID2_RST) {}

void RfidManager::begin() {
  SPI.begin();

  readerCarga.PCD_Init();
  readerDescarga.PCD_Init();
}

RfidEvent RfidManager::readEvent() {
  RfidEvent event;
  event.valid = false;
  event.tipo = RFID_NINGUNO;
  memset(event.uid, 0, sizeof(event.uid));

  if (readFrom(readerCarga, RFID_CARGA, event)) {
    return event;
  }

  if (readFrom(readerDescarga, RFID_DESCARGA, event)) {
    return event;
  }

  return event;
}

bool RfidManager::readFrom(MFRC522& reader, TipoRfid tipo, RfidEvent& event) {
  if (!reader.PICC_IsNewCardPresent() || !reader.PICC_ReadCardSerial()) {
    return false;
  }

  byte uid[4] = {0, 0, 0, 0};

  for (byte i = 0; i < 4 && i < reader.uid.size; i++) {
    uid[i] = reader.uid.uidByte[i];
  }

  Serial.print("UID: ");
  printUid(uid);
  Serial.print(" - ");

  if (isKnownUid(uid)) {
    Serial.println(tipo == RFID_CARGA ? "Carga valida" : "Descarga valida");

    event.valid = true;
    event.tipo = tipo;
    memcpy(event.uid, uid, 4);
  } else {
    Serial.println("No valido");
  }

  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();

  return event.valid;
}

bool RfidManager::isKnownUid(byte uid[4]) {
  return compareUid(uid, vacuna1) || compareUid(uid, vacuna2);
}

bool RfidManager::compareUid(byte a[4], byte b[4]) {
  for (byte i = 0; i < 4; i++) {
    if (a[i] != b[i]) {
      return false;
    }
  }

  return true;
}

void RfidManager::printUid(byte uid[4]) {
  for (byte i = 0; i < 4; i++) {
    if (uid[i] < 0x10) {
      Serial.print("0");
    }

    Serial.print(uid[i], HEX);

    if (i < 3) {
      Serial.print(" ");
    }
  }
}
