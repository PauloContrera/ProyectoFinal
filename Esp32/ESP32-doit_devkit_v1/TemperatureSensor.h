#pragma once
#include <Arduino.h>
#include <OneWire.h>
#include <DallasTemperature.h>

class TemperatureSensor {
public:
  void begin();
  float readCelsius();

private:
  OneWire oneWire;
  DallasTemperature sensors;

public:
  TemperatureSensor();
};

extern TemperatureSensor temperatureSensor;
