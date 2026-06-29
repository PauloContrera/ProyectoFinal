#include "SmsManager.h"
#include "config.h"

#ifdef ESP32
  HardwareSerial sim800(2);
#else
  #include <SoftwareSerial.h>
  SoftwareSerial sim800(SIM800_RX, SIM800_TX);
#endif

SmsManager smsManager;

void SmsManager::begin() {
#ifdef ESP32
  sim800.begin(9600, SERIAL_8N1, SIM800_RX, SIM800_TX);
#else
  sim800.begin(9600);
#endif
}

void SmsManager::sendTemperatureAlert(float temperature) {
  char message[100];

  snprintf(
    message,
    sizeof(message),
    "Atencion! Heladera fuera del nivel de temperatura. Actual: %.2f C",
    temperature
  );

  sim800.println("AT+CMGF=1");
  serialCheck();

  sim800.print("AT+CMGS=\"");
  sim800.print(PHONE_NUMBER);
  sim800.println("\"");
  serialCheck();

  sim800.print(message);
  serialCheck();

  sim800.write(26);
  serialCheck();
}

void SmsManager::serialCheck() {
  unsigned long start = millis();

  while (millis() - start < 300) {
    while (Serial.available()) {
      sim800.write(Serial.read());
    }

    while (sim800.available()) {
      Serial.write(sim800.read());
    }
  }
}
