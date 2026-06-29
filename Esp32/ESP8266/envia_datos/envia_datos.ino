#include <ESP8266WiFi.h>

const char* ssid = "Morán "; 
const char* password = "02830193";

const char* host = "192.168.0.106"; 

void setup() { 
  Serial.begin(115200); 
  delay(10);

  // Nos conectamos a nuestro wifi 

  Serial.println(); 
  Serial.println(); 
  Serial.print("Connecting to "); 
  Serial.println(ssid); 

  WiFi.begin(ssid, password); 

  while (WiFi.status() != WL_CONNECTED) {
    delay(500); 
    Serial.print("."); 
  } 

  Serial.println(""); 
  Serial.println("WiFi connected"); 
  Serial.println("IP address: "); 
  Serial.println(WiFi.localIP()); 
} 

int value = 0; 
void loop() { 
  delay(2000); 
  ++value;

  Serial.print("connecting to "); 
  Serial.println(host); 

  // Creamos una instancia de WIFICLIENT 
  WiFiClient client; 
  const int httpPort = 80; 
  if (!client.connect(host, httpPort)) { 
    Serial.println("connection failed"); 
    return; 
  }
  

  // Primera solicitud POST (login)
  // Creamos la dirección para luego usarla en el String del POST que tendremos que enviar 
  String url1 = "/Proyecto-Final/Backend/auth/login.php";
  //creo un string con los datos que enviaré por POST lo creo de antemano para luego poder calcular el tamaño del 
  String data1 = "emailOrUsername=Cliente&password=Cliente";

  Serial.print("Requesting URL: "); 
  Serial.println(url1);

  client.print(String("POST ") + url1 + " HTTP/1.1\r\n" + 
              "Host: " + host + "\r\n" + 
              "Accept: */*\r\n" + 
              "Content-Length: " + String(data1.length()) + "\r\n" + 
              "Content-Type: application/x-www-form-urlencoded\r\n" + 
              "Connection: close\r\n\r\n" + 
              data1); 

  delay(10);

  Serial.println("Response:");
  while(client.available()) { 
    String line = client.readStringUntil('\r'); 
    Serial.print(line); 
  }

  Serial.println(); 
  client.stop(); // Cierra la conexión antes de la segunda solicitud
  delay(500);

  // Segunda solicitud POST (registrar datos)
  if (!client.connect(host, httpPort)) { 
    Serial.println("Connection failed on second request"); 
    return; 
  }

  // Creamos la dirección para luego usarla en el String del POST que tendremos que enviar 
  String url2 = "http://192.168.0.106/Proyecto-Final/Backend/controllers/Registrar.php"; 
  //creo un string con los datos que enviaré por POST lo creo de antemano para luego poder calcular el tamaño del 
  String data2 = "{\"fridge_id\":6,\"temperature\":33}";

  //imprimo la url a donde enviaremos la solicitud, solo para debug 
  Serial.print("Requesting URL: "); 
  Serial.println(url2);

  //Esta es la solicitud del tipoPOST que enviaremos al servidor 
  client.print(String("POST ") + url2 + " HTTP/1.1\r\n" + 
              "Host: " + host + "\r\n" + 
              "Accept: */*\r\n" + 
              "Content-Length:" + String(data2.length()) + "\r\n" + 
              "Content-Type: application/json\r\n" +
              "Connection: close\r\n\r\n" + 
              data2); 
  delay(10);

  //Leemos todas las lineas que nos responde el servidor y las imprimimos por pantalla, esto no es necesario pero 
  Serial.println("Respond:"); 
  while(client.available()){ 
    String line = client.readStringUntil('\r'); 
    Serial.print(line); 
  }

  Serial.println(); 

  // se cierra la conexión
  client.stop(); // Cierra la conexión después de la segunda solicitud
  Serial.println("closing connection");
}











