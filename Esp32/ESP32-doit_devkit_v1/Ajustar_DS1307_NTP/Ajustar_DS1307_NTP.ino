#include <WiFi.h>
#include <time.h>
#include <Wire.h>
#include <RTClib.h>

#define WIFI_SSID "Moran"
#define WIFI_PASSWORD "02830193"

#define RTC_SDA 32
#define RTC_SCL 33

// Recomendado para servidor: guardar UTC en el RTC.
#define GMT_OFFSET_SECONDS -10800
#define DAYLIGHT_OFFSET_SECONDS 0

RTC_DS1307 rtc;

void printDateTime(const DateTime& dt) {
  char buffer[25];

  snprintf(
    buffer,
    sizeof(buffer),
    "%04d-%02d-%02d %02d:%02d:%02d",
    dt.year(),
    dt.month(),
    dt.day(),
    dt.hour(),
    dt.minute(),
    dt.second()
  );

  Serial.println(buffer);
}

bool connectWifi() {
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  Serial.print("Conectando WiFi");

  unsigned long start = millis();

  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000UL) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("WiFi conectado. IP: ");
    Serial.println(WiFi.localIP());
    return true;
  }

  Serial.println("No se pudo conectar a WiFi.");
  return false;
}

bool getNtpTime(struct tm& timeinfo) {
  configTime(
    GMT_OFFSET_SECONDS,
    DAYLIGHT_OFFSET_SECONDS,
    "pool.ntp.org",
    "time.google.com",
    "time.cloudflare.com"
  );

  Serial.println("Esperando hora NTP...");

  for (int i = 0; i < 20; i++) {
    if (getLocalTime(&timeinfo, 1000)) {
      return true;
    }

    Serial.print(".");
  }

  Serial.println();
  return false;
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println();
  Serial.println("Ajustador RTC DS1307 por NTP");

  Wire.begin(RTC_SDA, RTC_SCL);

  if (!rtc.begin()) {
    Serial.println("No se encontro el RTC DS1307.");
    while (true) {
      delay(1000);
    }
  }

  if (!connectWifi()) {
    Serial.println("No se ajusto el RTC.");
    return;
  }

  struct tm timeinfo;

  if (!getNtpTime(timeinfo)) {
    Serial.println("No se pudo obtener hora NTP.");
    return;
  }

  DateTime ntpTime(
    timeinfo.tm_year + 1900,
    timeinfo.tm_mon + 1,
    timeinfo.tm_mday,
    timeinfo.tm_hour,
    timeinfo.tm_min,
    timeinfo.tm_sec
  );

  rtc.adjust(ntpTime);

  Serial.print("RTC ajustado a: ");
  printDateTime(ntpTime);

  DateTime current = rtc.now();

  Serial.print("RTC lee ahora: ");
  printDateTime(current);

  Serial.print("Epoch UTC: ");
  Serial.println(current.unixtime());

  WiFi.disconnect(true);
  WiFi.mode(WIFI_OFF);

  Serial.println("Listo. Ya podes cargar el programa principal.");
}

void loop() {
}