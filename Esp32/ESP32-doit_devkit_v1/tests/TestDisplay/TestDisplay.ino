#include "DisplayManager.h"

float temp = 2.00;

void setup() {
  displayManager.begin();
}

void loop() {
  displayManager.showTemperature(temp);
  temp += 0.25;

  if (temp > 9.99) {
    temp = 2.00;
  }

  delay(500);
}