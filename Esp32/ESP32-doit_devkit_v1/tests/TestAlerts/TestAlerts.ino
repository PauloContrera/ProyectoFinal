#include "AlertManager.h"
#include "types.h"

void setup() {
  alertManager.begin();
}

void loop() {
  alertManager.applyState(ESTADO_NORMAL);
  delay(2000);

  alertManager.applyState(ESTADO_PELIGRO);
  delay(2000);
}