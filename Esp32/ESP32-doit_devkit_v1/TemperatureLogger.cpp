#include "TemperatureLogger.h"

TemperatureLogger temperatureLogger;

TemperatureLogger::TemperatureLogger()
  : normalCount(0),
    alertCount(0),
    dangerCount(0),
    lastNormalMs(0),
    lastAlertMs(0),
    lastDangerMs(0) {}

bool TemperatureLogger::registerSample(EstadoTemperatura estado, float temperature, const char* timestamp) {
  if (!shouldSample(estado)) {
    return false;
  }

  switch (estado) {
    case ESTADO_NORMAL:
      addSample(normalSamples, normalCount, temperature, timestamp);
      return normalCount >= MAX_SAMPLES;

    case ESTADO_ALERTA:
      addSample(alertSamples, alertCount, temperature, timestamp);
      return alertCount >= MAX_SAMPLES;

    case ESTADO_PELIGRO:
      addSample(dangerSamples, dangerCount, temperature, timestamp);
      return dangerCount >= MAX_SAMPLES;
  }

  return false;
}

void TemperatureLogger::popBatch(EstadoTemperatura estado, TemperatureSample output[MAX_SAMPLES], uint8_t& count) {
  TemperatureSample* source = nullptr;
  uint8_t* sourceCount = nullptr;

  if (estado == ESTADO_NORMAL) {
    source = normalSamples;
    sourceCount = &normalCount;
  } else if (estado == ESTADO_ALERTA) {
    source = alertSamples;
    sourceCount = &alertCount;
  } else {
    source = dangerSamples;
    sourceCount = &dangerCount;
  }

  count = *sourceCount;

  for (uint8_t i = 0; i < count; i++) {
    output[i] = source[i];
  }

  *sourceCount = 0;
}

bool TemperatureLogger::shouldSample(EstadoTemperatura estado) {
  unsigned long now = millis();

  if (estado == ESTADO_NORMAL && now - lastNormalMs >= 600000UL) {
    lastNormalMs = now;
    return true;
  }

  if (estado == ESTADO_ALERTA && now - lastAlertMs >= 60000UL) {
    lastAlertMs = now;
    return true;
  }

  if (estado == ESTADO_PELIGRO && now - lastDangerMs >= 10000UL) {
    lastDangerMs = now;
    return true;
  }

  return false;
}

void TemperatureLogger::addSample(TemperatureSample* buffer, uint8_t& count, float temperature, const char* timestamp) {
  if (count >= MAX_SAMPLES) {
    return;
  }

  #if DEBUG_TEMPERATURE_LOGGER
  Serial.print("Muestra agregada. Cantidad actual: ");
  Serial.println(count + 1);
  #endif
  
  buffer[count].value = temperature;
  strncpy(buffer[count].timestamp, timestamp, sizeof(buffer[count].timestamp));
  buffer[count].timestamp[sizeof(buffer[count].timestamp) - 1] = '\0';

  count++;
}
