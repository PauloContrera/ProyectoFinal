#include "TemperatureSensor.h"

void setup() {
  Serial.begin(115200);
  temperatureSensor.begin();
}

void loop() {
  float temp = temperatureSensor.readCelsius();

  Serial.print("Temperatura: ");
  Serial.print(temp);
  Serial.println(" C");

  delay(1000);
}