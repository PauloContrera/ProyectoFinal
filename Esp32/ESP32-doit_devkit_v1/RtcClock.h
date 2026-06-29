#pragma once
#include <Arduino.h>
#include <RTClib.h>

class RtcClock {
public:
  void begin();
  String getTimestamp();
  uint32_t getEpoch();

private:
  RTC_DS1307 rtc;
};

extern RtcClock rtcClock;
