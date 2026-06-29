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


  String url2 = "http://192.168.0.106/Proyecto-Final/Backend/controllers/Registrar.php";  
    
  // Lista de temperaturas a enviar
  float temperatures[] = {3.2, 4.1, 5.0, 4.5, 3.8};
  int numTemps = sizeof(temperatures) / sizeof(temperatures[0]);
  String timestamp[] = {"2023-12-12 11:42:37", "2023-12-12 12:42:37", "2023-12-12 13:42:37", "2023-12-12 14:42:37", "2023-12-12 15:42:37"};

  // Enviar las temperaturas
  enviarVariasTemperaturas(client, url2, 6, temperatures, numTemps, timestamp);

  client.stop();
  Serial.println("Conexión cerrada.");
  
  delay(5000); // Esperar antes de la próxima lectura
}

void enviarVariasTemperaturas(WiFiClient &client, String url, int fridge_id, float temps[], int numTemps, String timestamps[]) {
  Serial.print("Requesting URL: ");
  Serial.println(url);

  // Construir el JSON con varias temperaturas
  String json = "{ \"fridge_id\": " + String(fridge_id) + ", \"data\": [";

  for (int i = 0; i < numTemps; i++) {
    json += "{ \"temperature\": " + String(temps[i]) + ", \"timestamps\": \"" + timestamps[i] + "\" }";

    if (i < numTemps - 1) json += ", ";  // Separar valores con comas
  }
  json += "] }";

  Serial.println("Enviando datos:");
  Serial.println(json);

  client.print(String("POST ") + url + " HTTP/1.1\r\n" +
               "Host: " + host + "\r\n" +
               "Accept: application/json\r\n" +
               "Content-Type: application/json\r\n" +
               "Content-Length: " + String(json.length()) + "\r\n" +
               "Connection: keep-alive\r\n\r\n" +
               json);

  // **Esperar un momento para asegurarse de que el servidor responda**
  delay(100); 
  // Leer respuesta
  Serial.println("Respond:");

  while (client.available()) {
    String line = client.readStringUntil('\n');
    Serial.println(line);
  }

  Serial.println("Fin de la respuesta");
}