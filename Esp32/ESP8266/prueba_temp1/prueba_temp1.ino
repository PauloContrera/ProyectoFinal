//SENSOR TEMP
#include <OneWire.h>
#include <DallasTemperature.h>
// GPIO where the DS18B20 is connected to
const byte oneWireBus = 4;
// Setup a oneWire instance to communicate with any OneWire devices
OneWire oneWire(oneWireBus);
// Pass our oneWire reference to Dallas Temperature sensor
DallasTemperature sensors(&oneWire);

//RFID MFRC522
#include <SPI.h>
#include <MFRC522.h> 
#define SS_PIN1 10
#define SS_PIN2 8
#define RST_PIN1 9
#define RST_PIN2 7
MFRC522 mfrc522_1(SS_PIN1, RST_PIN1);   // Create MFRC522 instance. enviando pines de slave select y reset
MFRC522 mfrc522_2(SS_PIN2, RST_PIN2);  // Para eliminar de stock.

byte LecturaUID[4];                              // crea array para almacenar el UID leido
byte vacuna1[4] = {0x90, 0x0E, 0xE4, 0xA4} ;    // UID de tarjeta leido en programa 1
byte vacuna2[4] = {0x06, 0x76, 0x25, 0xD9} ;    // UID de llavero leido en programa 1
byte carga[4]; //Se cargara aquí la existente para ser enviada
byte descarga[4]; //Se descargara aquí la existente para ser enviada

//DISPLAY
#include <TM1637Display.h>
const int CLK = D6;  //Set the CLK pin connection to the display
const int DIO = D5;  //Set the DIO pin connection to the display
TM1637Display display(CLK, DIO);

//OTROS--------------------------
const int ESTADO = 1;
const int ENVIO = 0;
int tempAnterior = 0;
int umbralSup = 8;
int umbralInf = 2;
float arrayTemp[6];
int hora[6];
int min[6];
int seg[6];

//TIEMPO---------------------------
unsigned long tiempo_actual;
unsigned long tiempo_anterior = 0;
unsigned long delta_tiempo;
int contador = 0;

void setup() {
  // Start the Serial Monitor
  Serial.begin(9600);
  // Start the DS18B20 sensor
  sensors.begin();

  //RFID
  SPI.begin();      // Initiate  SPI bus
  mfrc522_1.PCD_Init();   // Initiate MFRC522_1
  mfrc522_2.PCD_Init();   // Initiate MFRC522_2

  //Display
  display.setBrightness(0x0a);  //set the diplay to maximum brightness
}

void loop() {
  //SENSOR TEMP---------------------------------------------------------
  sensors.requestTemperatures();
  float temperatureC = sensors.getTempCByIndex(0);
  //Prueba temp{
  Serial.print(temperatureC);
  Serial.println("ºC");
  //Fin Prueba temp}
  if (tempAnterior == 0){
    tempAnterior = temperatureC;
  }

  //TIEMPO----------------------------------------------------------------
  tiempo_actual = millis();
  delta_tiempo = tiempo_actual - tiempo_anterior;

  if(delta_tiempo == 1000){
    contador = contador + 1;
    tiempo_anterior = tiempo_actual;
  }

  //DISPLAY--------------------------------------------------------------
  int tempC = temperatureC * 100;
  display.showNumberDecEx(tempC, 0x40, false, 4);  //Display the tempC value;

  //ESTADO-----------------------------------------------------------------
  if(temperatureC > umbralSup || temperatureC < umbralInf){
    ESTADO = 3;
  }
  else if (abs(temperatureC-tempAnterior) > 2){
    ESTADO = 2;
  }
  else{
    ESTADO = 1;
  }
  switch (ESTADO) {
    case 1:
      if((contador % 600) == 0 || arrayTemp[0] == 0){
        for (int i=0;i<6;i++){
          if(arrayTemp[i] == 0){
            arrayTemp[i] == temperatureC;
            i = 6;
          }
        }
        if (arrayTemp[5] != 0){
         ENVIO = 1;
       }
      }
      
    break;
    case 2:
      if((contador % 60) == 0 || arrayTemp[0] == 0){
        for (int i=0;i<6;i++){
          if(arrayTemp[i] == 0){
            arrayTemp[i] == temperatureC;
            i = 6;
          }
        }
        if (arrayTemp[5] != 0){
          ENVIO = 1;
        }
      }
      
    break;
    case 3:
      if((contador % 10) == 0 || arrayTemp[0] == 0){
        for (int i=0;i<6;i++){
          if(arrayTemp[i] == 0){
            arrayTemp[i] == temperatureC;
            i = 6;
          }
        }
        if (arrayTemp[5] != 0){
          ENVIO = 1;
        }
      }
      
      /*ENVIO POR SMS------------------------------------------------------------------------
      */
    break;
    default:
      serial.print("Error en valor ESTADO: ");
      serial.println(ESTADO);
  }

  //Codigo RFID--------------------------------------------------------------------
  // Look for new cards
  if (mfrc522_1.PICC_IsNewCardPresent() && mfrc522_1.PICC_ReadCardSerial()){
    Serial.print("UID:");				// muestra texto UID:
    for (byte i = 0; i < mfrc522_1.uid.size; i++) {	// bucle recorre de a un byte por vez el UID
      if (mfrc522_1.uid.uidByte[i] < 0x10){		// si el byte leido es menor a 0x10
        Serial.print(" 0");				// imprime espacio en blanco y numero cero
      }
      else{						// sino
        Serial.print(" ");				// imprime un espacio en blanco
        }
        Serial.print(mfrc522_1.uid.uidByte[i], HEX);   	// imprime el byte del UID leido en hexadecimal
        LecturaUID[i]=mfrc522_1.uid.uidByte[i];   	// almacena en array el byte del UID leido      
      }
          
      Serial.print("\t");   			// imprime un espacio de tabulacion             
                    
      if(comparaUID(LecturaUID, vacuna1)){		// llama a funcion comparaUID con Usuario1
        Serial.println("Carga vacuna 1");	// si retorna verdadero muestra texto bienvenida
        carga = vacuna1;
        ENVIO = 1;
      }
      else if(comparaUID(LecturaUID, vacuna2)){	// llama a funcion comparaUID con Usuario2
        Serial.println("Carga vacuna 2");	// si retorna verdadero muestra texto bienvenida
        carga = vacuna2;
        ENVIO = 1;
      }
      else						// si retorna falso
        Serial.println("No valido"); 		// muestra texto equivalente a acceso denegado          
                  
      mfrc522_1.PICC_HaltA();  		// detiene comunicacion con tarjeta                
  }
  //Descarga
  if (mfrc522_2.PICC_IsNewCardPresent() && mfrc522_2.PICC_ReadCardSerial()){
    Serial.print("UID:");				// muestra texto UID:
    for (byte i = 0; i < mfrc522_2.uid.size; i++) {	// bucle recorre de a un byte por vez el UID
      if (mfrc522_2.uid.uidByte[i] < 0x10){		// si el byte leido es menor a 0x10
        Serial.print(" 0");				// imprime espacio en blanco y numero cero
      }
      else{						// sino
        Serial.print(" ");				// imprime un espacio en blanco
        }
        Serial.print(mfrc522_2.uid.uidByte[i], HEX);   	// imprime el byte del UID leido en hexadecimal
        LecturaUID[i]=mfrc522_2.uid.uidByte[i];   	// almacena en array el byte del UID leido      
      }
          
      Serial.print("\t");   			// imprime un espacio de tabulacion             
                    
      if(comparaUID(LecturaUID, vacuna1)){		// llama a funcion comparaUID con Usuario1
        Serial.println("Carga vacuna 1");	// si retorna verdadero muestra texto bienvenida
        descarga = vacuna1;
        ENVIO = 1;
      }
      else if(comparaUID(LecturaUID, vacuna2)){	// llama a funcion comparaUID con Usuario2
        Serial.println("Carga vacuna 2");	// si retorna verdadero muestra texto bienvenida
        descarga = vacuna2;
        ENVIO = 1;
      }
      else						// si retorna falso
        Serial.println("No valido"); 		// muestra texto equivalente a acceso denegado          
                  
      mfrc522_2.PICC_HaltA();  		// detiene comunicacion con tarjeta                
  }


  //TRANSMISION----------------------------------------------------------------------
  if (ENVIO == 1){

  }

  tempAnterior = temperatureC;
}



//FUNCIONES

//Func de RFID
boolean comparaUID(byte lectura[],byte usuario[])	// funcion comparaUID
{
  for (byte i=0; i < mfrc522.uid.size; i++){		// bucle recorre de a un byte por vez el UID
  if(lectura[i] != usuario[i])				// si byte de UID leido es distinto a usuario
    return(false);					// retorna falso
  }
  return(true);						// si los 4 bytes coinciden retorna verdadero
}
