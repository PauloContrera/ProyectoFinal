#include "RtcClock.h"
#include "config.h"
#include <Wire.h>

RtcClock rtcClock;

void RtcClock::begin() {
  Wire.begin(RTC_SDA, RTC_SCL);

  if (!rtc.begin()) {
    Serial.println("No se encontro el RTC DS1307");
    while (true) {
      delay(1000);
    }
  }

  #if FORCE_RTC_ADJUST
  rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  Serial.println("RTC ajustado con fecha de compilacion.");
  #endif
  
}

uint32_t RtcClock::getEpoch() {
  DateTime now = rtc.now();
  return now.unixtime();
}

String RtcClock::getTimestamp() {
  DateTime now = rtc.now();

  char buffer[20];
  snprintf(
    buffer,
    sizeof(buffer),
    "%04d-%02d-%02d %02d:%02d:%02d",
    now.year(),
    now.month(),
    now.day(),
    now.hour(),
    now.minute(),
    now.second()
  );

  return String(buffer);
}
