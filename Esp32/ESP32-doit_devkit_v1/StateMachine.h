#pragma once
#include <Arduino.h>
#include "types.h"

class StateMachine {
public:
  void begin(int umbralInf, int umbralSup);
  EstadoTemperatura evaluate(float temperature);
  bool enteredDangerState() const;

private:
  int lowerThreshold;
  int upperThreshold;

  float previousTemperature;
  bool hasPreviousTemperature;

  EstadoTemperatura currentState;
  EstadoTemperatura previousState;
};

extern StateMachine stateMachine;
