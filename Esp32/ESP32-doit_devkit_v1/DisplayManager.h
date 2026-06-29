#pragma once
#include <Arduino.h>
#include <TM1637Display.h>

class DisplayManager {
public:
  void begin();
  void showTemperature(float temperature);

private:
  TM1637Display display;
public:
  DisplayManager();
};

extern DisplayManager displayManager;
