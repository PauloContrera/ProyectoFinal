#include "SystemController.h"

void setup() {
  systemController.begin();
}

void loop() {
  systemController.actualizarSensores();
  systemController.evaluarEstado();
  systemController.actualizarSalidas();
  systemController.procesarRfid();
  systemController.procesarTransmision();

  delay(200);
}