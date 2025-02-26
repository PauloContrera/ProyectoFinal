#include <EEPROM.h>

// start reading from the first byte (address 0) of the EEPROM
int al = 50;
int value;
int address = 0;
void setup() {
  // initialize serial and wait for port to open:
  Serial.begin(115200);
  EEPROM.begin(512);

  for(int i=2; i<512;i++){
    EEPROM.write(i, 0);
  }


  if (EEPROM.commit()) {
    Serial.println("EEPROM successfully committed");
  }
  
  Serial.println(EEPROM.read(0));
  Serial.println(EEPROM.read(1));
  Serial.println(EEPROM.read(4));


}

void loop() {
  value = EEPROM.read(address);

  Serial.print(address);
  Serial.print("\t");
  Serial.print(value);
  Serial.print("\t");
  Serial.print(value, DEC);
  Serial.println();

  // advance to the next address of the EEPROM
  address = address + 1;

  // there are only 512 bytes of EEPROM, from 0 to 511, so if we're
  // on address 512, wrap around to address 0
  if (address == 512) { address = 0; }

  delay(500);

}