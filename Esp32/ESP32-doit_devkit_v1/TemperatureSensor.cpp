#include "TemperatureSensor.h"
#include "config.h"

TemperatureSensor temperatureSensor;

TemperatureSensor::TemperatureSensor()
  : oneWire(ONE_WIRE_BUS), sensors(&oneWire) {}

void TemperatureSensor::begin() {
  sensors.begin();
}

float TemperatureSensor::readCelsius() {
  sensors.requestTemperatures();
  float temp = sensors.getTempCByIndex(0);

  if (temp == DEVICE_DISCONNECTED_C || temp < -50.0f || temp > 80.0f) {
    Serial.println("Lectura invalida del DS18B20");
    return NAN;
  }

  return temp;
}