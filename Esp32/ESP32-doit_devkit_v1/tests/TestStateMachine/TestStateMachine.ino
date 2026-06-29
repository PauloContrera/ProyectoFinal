#include "StateMachine.h"

void setup() {
  Serial.begin(115200);
  stateMachine.begin(2, 8);
}

void loop() {
  probar(5.0);   // normal
  probar(9.0);   // peligro
  probar(5.0);   // normal
  probar(7.5);   // alerta si el salto supera 2 grados
  delay(5000);
}

void probar(float temp) {
  EstadoTemperatura estado = stateMachine.evaluate(temp);

  Serial.print("Temp: ");
  Serial.print(temp);
  Serial.print(" Estado: ");
  Serial.print(estado);
  Serial.print(" Entro peligro: ");
  Serial.println(stateMachine.enteredDangerState() ? "SI" : "NO");

  delay(1000);
}