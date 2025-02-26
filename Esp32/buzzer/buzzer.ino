const int BUZZZER_PIN = D8;  // buzzer activo en pin D8

void setup() {
  pinMode(BUZZER_ACTIVO, OUTPUT);  // pin 8 como salida
}

void loop() {

  for (int i = 0; i < 5; i++) {         // bucle repite 5 veces
    digitalWrite(BUZZER_ACTIVO, HIGH);  // activa buzzer
    delay(500);                         // demora de medio segundo
    digitalWrite(BUZZER_ACTIVO, LOW);   // apaga buzzer
    delay(500);                         // demora de medio segundo
  }