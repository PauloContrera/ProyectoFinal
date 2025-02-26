#include <SoftwareSerial.h>

SoftwareSerial SerialComSim800(7, 8); //RX -> 11, TX -> 12

void setup() {
  // Iniciamos comunicacion Serial
  Serial.begin(9600);
  SerialComSim800.begin(9600);
  delay (2000);
  SerialComSim800.println("AT+IPR=9600"); // Configura velocidad a 9600 bps
  delay(1000);
  SerialComSim800.println("AT"); // Comprobación básica
}

void loop() {
  while (Serial.available()){
    byte dato = Serial.read(); 
    SerialComSim800.write(dato);
  }
  while (SerialComSim800.available()){
    byte dato = SerialComSim800.read();
    Serial.write(dato);
  }

}