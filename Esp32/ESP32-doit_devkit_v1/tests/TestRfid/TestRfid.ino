#include "RfidManager.h"

void setup() {
  Serial.begin(115200);
  rfidManager.begin();
}

void loop() {
  RfidEvent event = rfidManager.readEvent();

  if (event.valid) {
    Serial.print("Evento valido. Tipo: ");
    Serial.println(event.tipo == RFID_CARGA ? "CARGA" : "DESCARGA");
  }

  delay(200);
}