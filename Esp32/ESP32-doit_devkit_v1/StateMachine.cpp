#include "StateMachine.h"
#include <math.h>

StateMachine stateMachine;

void StateMachine::begin(int umbralInf, int umbralSup) {
  lowerThreshold = umbralInf;
  upperThreshold = umbralSup;

  previousTemperature = 0.0f;
  hasPreviousTemperature = false;

  currentState = ESTADO_NORMAL;
  previousState = ESTADO_NORMAL;
}

EstadoTemperatura StateMachine::evaluate(float temperature) {
  previousState = currentState;

  if (temperature > upperThreshold || temperature < lowerThreshold) {
    currentState = ESTADO_PELIGRO;
  } else if (hasPreviousTemperature && fabs(temperature - previousTemperature) > 2.0f) {
    currentState = ESTADO_ALERTA;
  } else {
    currentState = ESTADO_NORMAL;
  }

  previousTemperature = temperature;
  hasPreviousTemperature = true;

  Serial.print("Temperatura: ");
  Serial.print(temperature);
  Serial.print(" C - Estado: ");
  Serial.println(currentState);

  return currentState;
}

bool StateMachine::enteredDangerState() const {
  return currentState == ESTADO_PELIGRO && previousState != ESTADO_PELIGRO;
}
