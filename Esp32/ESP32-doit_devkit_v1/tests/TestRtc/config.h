#pragma once
/*
#define WIFI_SSID "REPLACE_WITH_YOUR_SSID"
#define WIFI_PASSWORD "REPLACE_WITH_YOUR_PASSWORD"
*/


//DEBUG CONFIG
#define ENABLE_SERVER_COMMUNICATION 0 // 0: Desactivar, 1: Activar
#define FORCE_RTC_ADJUST 0 // 0: No ajustar, 1: Ajustar con fecha de compilacion
#define DEBUG_TEMPERATURE_LOGGER 0
//EN SystemController::cargarConfiguracion descomentar una vez para el guardado del nuevo umbral
#define DEBUG_EEPROM 0
#define DEBUG_RFID 0
#define DEBUG_SMS 0

#define WIFI_SSID "Moran"
#define WIFI_PASSWORD "0283019"

#define SERVER_URL "https://tempsegura.net/ProyectoFinal/Backend/controllers/Registrar.php"
#define CONFIG_URL "https://tempsegura.net/ProyectoFinal/Backend/controllers/Config.php"

#define USER_ID "99999"
#define USER_PASS "12345"

#define PHONE_NUMBER "+542613638639"

// Ajustar pines segun tu placa ESP32.
#define ONE_WIRE_BUS 26

#define BUZZER_PIN 25
#define LED_ROJO_PIN 25

#define DISPLAY_CLK 22
#define DISPLAY_DIO 21

#define SIM800_RX 17
#define SIM800_TX 16

#define RFID_SS1_CARGA 5
#define RFID_SS2_DESCARGA 4
#define RFID1_RST 14
#define RFID2_RST 27

#define RTC_SDA 32
#define RTC_SCL 33

#define TEMP_SENTINEL 128.8f
#define MAX_SAMPLES 6

#define DEFAULT_UMBRAL_SUP 8
#define DEFAULT_UMBRAL_INF 2

#define EEPROM_SIZE 4096
#define STORAGE_MAX_RECORDS 24
