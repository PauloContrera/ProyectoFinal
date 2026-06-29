#include "DisplayManager.h"
#include "config.h"

DisplayManager displayManager;

DisplayManager::DisplayManager()
  : display(DISPLAY_CLK, DISPLAY_DIO) {}

void DisplayManager::begin() {
  display.setBrightness(0x0a);
}

void DisplayManager::showTemperature(float temperature) {
  int value = (int)(temperature * 100);
  display.showNumberDecEx(value, 0x40, false, 4);
}
