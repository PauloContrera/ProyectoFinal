#include "AlertManager.h"
#include "config.h"

AlertManager alertManager;

void AlertManager::begin() {
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(LED_ROJO_PIN, OUTPUT);

  digitalWrite(BUZZER_PIN, LOW);
  digitalWrite(LED_ROJO_PIN, LOW);
}

void AlertManager::applyState(EstadoTemperatura estado) {
  bool active = estado == ESTADO_PELIGRO;

  digitalWrite(BUZZER_PIN, active ? HIGH : LOW);
  digitalWrite(LED_ROJO_PIN, active ? HIGH : LOW);
}
