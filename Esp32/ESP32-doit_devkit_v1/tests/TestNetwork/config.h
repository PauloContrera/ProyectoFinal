#pragma once

#define WIFI_SSID "REPLACE_WITH_YOUR_SSID"
#define WIFI_PASSWORD "REPLACE_WITH_YOUR_PASSWORD"

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

#define TEMP_SENTINEL 128.8f
#define MAX_SAMPLES 6

#define DEFAULT_UMBRAL_SUP 8
#define DEFAULT_UMBRAL_INF 2

#define EEPROM_SIZE 4096
#define STORAGE_MAX_RECORDS 24
