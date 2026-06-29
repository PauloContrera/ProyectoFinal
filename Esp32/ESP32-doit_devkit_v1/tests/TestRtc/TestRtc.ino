#include "RtcClock.h"

void setup() {
  Serial.begin(115200);
  rtcClock.begin();
}

void loop() {
  Serial.print("Timestamp texto: ");
  Serial.println(rtcClock.getTimestamp());

  Serial.print("Epoch: ");
  Serial.println(rtcClock.getEpoch());

  delay(1000);
}