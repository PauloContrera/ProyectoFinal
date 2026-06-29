#pragma once
#include <Arduino.h>
#include "types.h"

class AlertManager {
public:
  void begin();
  void applyState(EstadoTemperatura estado);
};

extern AlertManager alertManager;
