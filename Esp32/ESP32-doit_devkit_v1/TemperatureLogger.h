#pragma once
#include <Arduino.h>
#include "types.h"
#include "config.h"

class TemperatureLogger {
public:
  TemperatureLogger();

  bool registerSample(EstadoTemperatura estado, float temperature, const char* timestamp);
  void popBatch(EstadoTemperatura estado, TemperatureSample output[MAX_SAMPLES], uint8_t& count);

private:
  TemperatureSample normalSamples[MAX_SAMPLES];
  TemperatureSample alertSamples[MAX_SAMPLES];
  TemperatureSample dangerSamples[MAX_SAMPLES];

  uint8_t normalCount;
  uint8_t alertCount;
  uint8_t dangerCount;

  unsigned long lastNormalMs;
  unsigned long lastAlertMs;
  unsigned long lastDangerMs;

  bool shouldSample(EstadoTemperatura estado);
  void addSample(TemperatureSample* buffer, uint8_t& count, float temperature, const char* timestamp);
};

extern TemperatureLogger temperatureLogger;
