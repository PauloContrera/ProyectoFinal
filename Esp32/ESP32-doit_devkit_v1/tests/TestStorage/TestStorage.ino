#include <Arduino.h>
#include "StorageManager.h"
#include "types.h"

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("Iniciando prueba EEPROM...");

  storageManager.begin();

  Serial.print("Umbral inferior actual: ");
  Serial.println(storageManager.getUmbralInf());

  Serial.print("Umbral superior actual: ");
  Serial.println(storageManager.getUmbralSup());

  storageManager.saveThresholds(2, 8);

  Serial.println("Umbrales guardados.");

  Serial.print("Nuevo umbral inferior: ");
  Serial.println(storageManager.getUmbralInf());

  Serial.print("Nuevo umbral superior: ");
  Serial.println(storageManager.getUmbralSup());

  TemperatureSample sample;
  sample.value = 10.50;
  strcpy(sample.timestamp, "2026-06-12 15:30:00");

  storageManager.saveTemperatureBatch(ESTADO_PELIGRO, &sample, 1);

  Serial.println("Muestra de temperatura guardada en EEPROM.");
  Serial.println("Prueba finalizada.");

  storageManager.debugPrintPending();
}

void loop() {
}