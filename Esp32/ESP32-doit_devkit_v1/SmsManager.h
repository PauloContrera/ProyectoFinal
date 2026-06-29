#pragma once
#include <Arduino.h>

class SmsManager {
public:
  void begin();
  void sendTemperatureAlert(float temperature);

private:
  void serialCheck();
};

extern SmsManager smsManager;
