//INTERNET
#ifdef ESP32
  #include <WiFi.h>
  #include <HTTPClient.h>
#else
  #include <Arduino.h>
  #include <ESP8266WiFiMulti.h>
  #include <Hash.h>
  #include <ESP8266HTTPClient.h>
#endif
// Replace with your network credentials
const char* ssid = "REPLACE_WITH_YOUR_SSID";
const char* password = "REPLACE_WITH_YOUR_PASSWORD";
const char* host = "tempsegura.net";  //Add the host without "www" Example: electronoobs.com
String user = "99999";                     //This is the ID you have on your database, I've used 99999 becuase there is a maximum of 5 characters
String pass = "12345";               //Add the password from the database, also maximum 5 characters and only numerical values
String location_url = "/TX.php?id=";            //location of your PHP file on the server. In this case the TX.php is directly on the first folder of the server
                                                //If you have the files in a different folder, add thas as well, Example: "/ESP/TX.php?id="     Where the folder is ESP

//EEPROM(FLASH)
#include <EEPROM.h>
int mem1;
int mem2;


//SMS
#include <SoftwareSerial.h>
SoftwareSerial SerialComSim800(11, 12); //RX -> 11, TX -> 12
char mensaje[70] = "Atencion! Heladera fuera del nivel de temperatura. Actual: ";
char numTemp[7];


//SENSOR TEMP
#include <OneWire.h>
#include <DallasTemperature.h>
// GPIO where the DS18B20 is connected to
const byte oneWireBus = 4;
// Setup a oneWire instance to communicate with any OneWire devices
OneWire oneWire(oneWireBus);
// Pass our oneWire reference to Dallas Temperature sensor
DallasTemperature sensors(&oneWire);

#define BUZZER_ACTIVO 3
#define LED_ROJO 4

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
int ESTADO = 1;
int ESTADOANTERIOR = 1;
int ENVIO = 0;
int tempAnterior = 0;
int umbralSup = 8;
int umbralInf = 2;
float arrayTempNor[6] = {128.8,128.8,128.8,128.8,128.8,128.8};
float arrayTempAle[6] = {128.8,128.8,128.8,128.8,128.8,128.8};
float arrayTempPel[6]= {128.8,128.8,128.8,128.8,128.8,128.8};
byte Nor=0;
byte Ale=0;
byte Pel=0;
int horaNor[6];
int minutNor[6];
int segNor[6];
int horaAle[6];
int minutAle[6];
int segAle[6];
int horaPel[6];
int minutPel[6];
int segPel[6];

//TIEMPO---------------------------
unsigned long tiempo_actual;
unsigned long tiempo_anterior = 0;
unsigned long delta_tiempo;
int contador = 0;

void setup() {
  // Start the Serial Monitor
  Serial.begin(115200);
  // Start the DS18B20 sensor
  sensors.begin();

  pinMode(BUZZER_ACTIVO, OUTPUT);
  pinMode(LED_ROJO, OUTPUT);
  //sms
  SerialComSim800.begin(115200);


  //wifi
  WiFi.begin(ssid, password);

  Serial.print("Conectando...");
  while (WiFi.status() != WL_CONNECTED){ //Check for the connection
    delay(500);
    Serial.print(".");
  }

  Serial.print("Conectado con éxito, mi IP es: ");
  Serial.println(WiFi.localIP());

  //EEPROM
  EEPROM.begin(512);
  //SERVIDOR COTAS-----------------------------TERMINAR--------------------------
  if(WiFi.status()== WL_CONNECTED){   //Check WiFi connection status

  HTTPClient http1;
  http1.begin("linkdelaapi?user=test&pass=test");        //Indicamos el destino
  http1.addHeader("Content-Type", "plain-text"); //Preparamos el header text/plain si solo vamos a enviar texto plano sin un paradigma llave:valor.

  int codigo_respuesta = http1.GET();   //Enviamos el post pasándole, los datos que queremos enviar. (esta función nos devuelve un código que guardamos en un int)

  if(codigo_respuesta>0){
    Serial.println("Código HTTP ► " + String(codigo_respuesta));   //Print return code

    if(codigo_respuesta == 200){
      String cuerpo_respuesta = http1.getString();
      Serial.println("El servidor respondió ▼ ");
      Serial.println(cuerpo_respuesta);
    }

  }else{
    Serial.print("Error enviando GET, código: ");
    Serial.println(codigo_respuesta);
  }

  http1.end();  //libero recursos

  }else{
    Serial.println("Error en la conexión WIFI");

  }

  umbralSup = EEPROM.read(0);
  umbralInf = EEPROM.read(1);

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
      if((contador % 600) == 0){ //60min
        for (int i=0;i<6;i++){
          if(arrayTempNor[i] == 1.288000e+02){
            arrayTempNor[i] == temperatureC;
            i = 6;
          }
        }
        if (arrayTempNor[5] != 1.288000e+02){
         ENVIO = 1;
         Nor = 1;
       }
      }
      digitalWrite(BUZZER_ACTIVO, LOW);
      digitalWrite(LED_ROJO, LOW);
    break;
    case 2:
      if((contador % 60) == 0){ //1min
        for (int i=0;i<6;i++){
          if(arrayTempAle[i] == 1.288000e+02){
            arrayTempAle[i] == temperatureC;
            i = 6;
          }
        }
        if (arrayTempAle[5] != 1.288000e+02){
          ENVIO = 1;
          Ale = 1;
        }
      }
      digitalWrite(BUZZER_ACTIVO, LOW);
      digitalWrite(LED_ROJO, LOW);
    break;
    case 3:
      if((contador % 10) == 0){ //10seg
        for (int i=0;i<6;i++){
          if(arrayTempPel[i] == 1.288000e+02){
            arrayTempPel[i] == temperatureC;
            i = 6;
          }
        }
        if (arrayTempPel[5] != 1.288000e+02){
          ENVIO = 1;
          Pel = 1;
        }
      }
      
      digitalWrite(BUZZER_ACTIVO, HIGH);
      digitalWrite(LED_ROJO, HIGH);

      if(ESTADOANTERIOR != ESTADO){
        //ENVIO POR SMS------------------------------------------------------------------------
        SerialComSim800.println("AT+CMGF=1"); //Formato SMS texto
        serialCheck();

        SerialComSim800.println("AT+CMGS=\"+542613638639\""); // Envía el sms al número especificado
        serialCheck();

        dtostrf(temperatureC,6,2,numTemp); // Convertir el número flotante a texto con 2 decimales
        strcat(mensaje, numTemp);// Concatenar el número convertido a la cadena base
        
        SerialComSim800.print(mensaje); // Imprime la cadena resultante
        serialCheck();

        SerialComSim800.write(26); // Convencion ^Z -> 'Ctrl+z' -> 26DEC (1AHEX) ASCII -> SUB.
      }

    break;
    default:
      Serial.print("Error en valor ESTADO: ");
      Serial.println(ESTADO);
  }
  ESTADOANTERIOR = ESTADO;
  tempAnterior = temperatureC;

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
                    
      if(comparaUID(LecturaUID, vacuna1,1)){		// llama a funcion comparaUID con Usuario1
        Serial.println("Carga vacuna 1");	// si retorna verdadero muestra texto bienvenida
        memcpy(carga, vacuna1, sizeof(vacuna1)); // Copiar los datos de 'vacuna1' a 'carga': carga = vacuna1;
        ENVIO = 1;
      }
      else if(comparaUID(LecturaUID, vacuna2,1)){	// llama a funcion comparaUID con Usuario2
        Serial.println("Carga vacuna 2");	// si retorna verdadero muestra texto bienvenida
        memcpy(carga, vacuna2, sizeof(vacuna2)); // Copiar los datos de 'vacuna2' a 'carga': carga = vacuna2;
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
                    
      if(comparaUID(LecturaUID, vacuna1,2)){		// llama a funcion comparaUID con Usuario1
        Serial.println("Carga vacuna 1");	// si retorna verdadero muestra texto bienvenida
        memcpy(descarga, vacuna1, sizeof(vacuna1)); // Copiar los datos de 'vacuna1' a 'descarga': descarga = vacuna1;
        ENVIO = 1;
      }
      else if(comparaUID(LecturaUID, vacuna2,2)){	// llama a funcion comparaUID con Usuario2
        Serial.println("Carga vacuna 2");	// si retorna verdadero muestra texto bienvenida
        memcpy(descarga, vacuna2, sizeof(vacuna2)); // Copiar los datos de 'vacuna2' a 'descarga': descarga = vacuna2;
        ENVIO = 1;
      }
      else						// si retorna falso
        Serial.println("No valido"); 		// muestra texto equivalente a acceso denegado          
                  
      mfrc522_2.PICC_HaltA();  		// detiene comunicacion con tarjeta                
  }


  //TRANSMISION----------------------------------------------------------------------
  //PASAMOS PRIMERO LO GUARDADO EN EEPROM----------HACER-----------------------------
  transmisionPost(1, 0);
  if (ENVIO == 1){

    transmisionPost(0, ESTADO);

    
  }

  ENVIO = 0;
}



//FUNCIONES

//Func POST
void transmisionPost(int opcion,int estado){
  if(WiFi.status()== WL_CONNECTED){   //Check WiFi connection status

    HTTPClient http;
    String datos_a_enviar = "user=" + user + "&pass=" + pass;

    http.begin(host);        //Indicamos el destino
    http.addHeader("Content-Type", "application/x-www-form-urlencoded"); //Preparamos el header text/plain si solo vamos a enviar texto plano sin un paradigma llave:valor.

    int codigo_respuesta = http.POST(datos_a_enviar);   //Enviamos el post pasándole, los datos que queremos enviar. (esta función nos devuelve un código que guardamos en un int)

    if(codigo_respuesta>0){
      Serial.println("Código HTTP ► " + String(codigo_respuesta));   //Print return code

      if(codigo_respuesta == 200){
        String cuerpo_respuesta = http.getString();
        Serial.println("El servidor respondió ▼ ");
        Serial.println(cuerpo_respuesta);

        if (opcion == 1){

          //----------------------ELIMINAR de EEPROM
        }
      }

    }else{

    Serial.print("Error enviando POST, código: ");
    Serial.println(codigo_respuesta);

    }

    http.end();  //libero recursos

  }else{

    Serial.println("Error en la conexión WIFI");
    if(opcion == 0){
      //GUARDO EN EEPROM------------------------------HACER-----------------------------------------------------
      switch (estado) {
        case 1:
          if(EEPROM.read(2) == 0){
            for(int i=2;i<=7;i++){
              EEPROM.write(i,arrayTempNor[i-2]);
              EEPROM.write(i+39,horaNor[i-2]);
              EEPROM.write(i+39+6,minutNor[i-2]);
              EEPROM.write(i+39+12,segNor[i-2]);
              arrayTempNor[i-2]=1.288000e+02;
            }
          }
          else{
            arrayTempNor[2]=1.288000e+02;
            for(int i=3;i<=7;i++){
              EEPROM.write(i,arrayTempNor[i-2]);
              EEPROM.write(i+39,horaNor[i-2]);
              EEPROM.write(i+39+6,minutNor[i-2]);
              EEPROM.write(i+39+12,segNor[i-2]);
              arrayTempNor[i-2]=1.288000e+02;
            }
          }

        break;
        case 2:
          if(EEPROM.read(8) == 0){
            for(int i=8;i<=13;i++){
              EEPROM.write(i,arrayTempAle[i-8]);
              EEPROM.write(i+50,horaAle[i-2]);
              EEPROM.write(i+50+6,minutAle[i-2]);
              EEPROM.write(i+50+12,segAle[i-2]);
              arrayTempAle[i-2]=1.288000e+02;
            }
          }
          else{
            arrayTempAle[8]=1.288000e+02;
            for(int i=9;i<=13;i++){
              EEPROM.write(i,arrayTempAle[i-8]);
              EEPROM.write(i+50,horaAle[i-2]);
              EEPROM.write(i+50+6,minutAle[i-2]);
              EEPROM.write(i+50+12,segAle[i-2]);
              arrayTempAle[i-2]=1.288000e+02;
            }
          }
        break;
        case 3:
          if(EEPROM.read(14) == 0){
            for(int i=14;i<=19;i++){
              EEPROM.write(i,arrayTempPel[i-14]);
              EEPROM.write(i+63,horaPel[i-2]);
              EEPROM.write(i+63+6,minutPel[i-2]);
              EEPROM.write(i+63+12,segPel[i-2]);
              arrayTempPel[i-2]=1.288000e+02;
            }
          }
          else{
            arrayTempPel[14]=1.288000e+02;
            for(int i=15;i<=19;i++){
              EEPROM.write(i,arrayTempPel[i-14]);
              EEPROM.write(i+63,horaPel[i-2]);
              EEPROM.write(i+63+6,minutPel[i-2]);
              EEPROM.write(i+63+12,segPel[i-2]);
              arrayTempPel[i-2]=1.288000e+02;
            }
          }
        break;
      }
    }
    
  }
}

//Func de sms
void serialCheck(){
  while (Serial.available()){
    SerialComSim800.write(Serial.read());
  }
  while (SerialComSim800.available()){
    Serial.write (SerialComSim800.read());
  }
  delay(200);
}


//Func de RFID
boolean comparaUID(byte lectura[],byte usuario[],byte tipo)	// funcion comparaUID
{
  if(tipo==1){
    for (byte i=0; i < mfrc522_1.uid.size; i++){		// bucle recorre de a un byte por vez el UID
      if(lectura[i] != usuario[i])				// si byte de UID leido es distinto a usuario
       return(false);					// retorna falso
      }
    return(true);						// si los 4 bytes coinciden retorna verdadero
  }
  else{
    for (byte i=0; i < mfrc522_2.uid.size; i++){		// bucle recorre de a un byte por vez el UID
      if(lectura[i] != usuario[i])				// si byte de UID leido es distinto a usuario
       return(false);					// retorna falso
      }
    return(true);						// si los 4 bytes coinciden retorna verdadero
  }
  
}



