#include <SPI.h>
#include <MFRC522.h>

#define SS_PIN 4
#define RST_PIN 27

MFRC522 rfid(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  SPI.begin();
  Serial.println("ini");
  rfid.PCD_Init();

  Serial.print("Version MFRC522: 0x");
  Serial.println(rfid.PCD_ReadRegister(MFRC522::VersionReg), HEX);

  Serial.println("Acerque una tarjeta...");
}

void loop() {
  if (!rfid.PICC_IsNewCardPresent()) {
    return;
  }

  if (!rfid.PICC_ReadCardSerial()) {
    return;
  }

  Serial.print("UID:");

  for (byte i = 0; i < rfid.uid.size; i++) {
    Serial.print(" ");
    if (rfid.uid.uidByte[i] < 0x10) {
      Serial.print("0");
    }
    Serial.print(rfid.uid.uidByte[i], HEX);
  }

  Serial.println();

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
}