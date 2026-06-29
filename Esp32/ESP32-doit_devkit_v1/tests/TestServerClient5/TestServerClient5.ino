#include <Arduino.h>
#include "ServerClient.h"

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println();
  Serial.println("Test ServerClient");

  serverClient.begin();

  ServerTemperatureSample samples[2];

  uint32_t now;
  if (!serverClient.getServerTime(now)) {
    Serial.println("No se pudo obtener server_time.");
    return;
  }

  samples[0].temp = 4.5;
  samples[0].time = now;

  samples[1].temp = 5.1;
  samples[1].time = now - 60;

  ServerConfigUpdate configUpdate;

  bool ok = serverClient.syncTemperatureBatch(samples, 2, configUpdate);

  if (!ok) {
    Serial.println("Sync ERROR.");
    return;
  }

  Serial.println("Sync OK.");

  if (configUpdate.cambio) {
    Serial.println("Cambio de configuracion recibido:");

    Serial.print("temp_min: ");
    Serial.println(configUpdate.tempMin);

    Serial.print("temp_max: ");
    Serial.println(configUpdate.tempMax);

    Serial.print("tiempo_espera: ");
    Serial.println(configUpdate.tiempoEspera);

    Serial.print("max_batch_size: ");
    Serial.println(configUpdate.maxBatchSize);

    Serial.print("retry_base_seconds: ");
    Serial.println(configUpdate.retryBaseSeconds);
  } else {
    Serial.println("Sin cambios de configuracion.");
  }
}

void loop() {
}