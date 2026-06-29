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
  return sensors.getTempCByIndex(0);
}
