#include "SystemController.h"

#include "TemperatureSensor.h"
#include "RtcClock.h"
#include "DisplayManager.h"
#include "AlertManager.h"
#include "SmsManager.h"
#include "RfidManager.h"
#include "StateMachine.h"
#include "TemperatureLogger.h"
#include "ServerClient.h"
#include "StorageManager.h"
#include <math.h>

SystemController systemController;

void SystemController::begin() {
  Serial.begin(115200);

  temperatureSensor.begin();
  rtcClock.begin();
  displayManager.begin();
  alertManager.begin();
  smsManager.begin();
  storageManager.begin();

  temperaturaActual = 0.0f;
  estadoActual = ESTADO_NORMAL;
  hayTemperaturaPendiente = false;
  sincronizacionInicialPendiente = true;
  ultimoIntentoSyncInicialMs = 0;
  cantidadMuestrasPendientes = 0;

  cargarConfiguracion();

  #if ENABLE_SERVER_COMMUNICATION
  serverClient.begin();
  sincronizacionInicialPendiente = !sincronizarInicioServidor();
  #endif
  rfidManager.begin();

  Serial.println("Sistema iniciado.");
}

void SystemController::cargarConfiguracion() {
  //storageManager.saveThresholds(2, 8);
  int umbralInf = storageManager.getUmbralInf();
  int umbralSup = storageManager.getUmbralSup();

  stateMachine.begin(umbralInf, umbralSup);

  Serial.print("Umbral inferior: ");
  Serial.println(umbralInf);
  Serial.print("Umbral superior: ");
  Serial.println(umbralSup);
}

bool SystemController::sincronizarInicioServidor() {
#if ENABLE_SERVER_COMMUNICATION
  ultimoIntentoSyncInicialMs = millis();
  Serial.println("Sincronizacion inicial con servidor...");

  float lectura = temperatureSensor.readCelsius();
  if (!isnan(lectura)) {
    temperaturaActual = lectura;
  }

  ServerTemperatureSample sample;
  sample.temp = temperaturaActual;
  sample.time = 0;

  ServerConfigUpdate configUpdate;
  if (!serverClient.syncTemperatureBatch(&sample, 1, configUpdate)) {
    Serial.println("Sincronizacion inicial fallida: no se pudo enviar muestra inicial.");
    return false;
  }

  Serial.println("Sincronizacion inicial OK.");
  aplicarConfiguracionServidor(configUpdate);
  return true;
#else
  return true;
#endif
}

void SystemController::aplicarConfiguracionServidor(const ServerConfigUpdate& configUpdate) {
  if (!configUpdate.cambio) {
    Serial.println("Servidor sin cambios de configuracion.");
    return;
  }

  if (configUpdate.tempMin >= configUpdate.tempMax) {
    Serial.println("Configuracion recibida invalida: temp_min >= temp_max.");
    return;
  }

  int umbralInf = (int)roundf(configUpdate.tempMin);
  int umbralSup = (int)roundf(configUpdate.tempMax);

  storageManager.saveThresholds(umbralInf, umbralSup);
  stateMachine.begin(umbralInf, umbralSup);

  Serial.print("Configuracion actualizada desde servidor. Umbral inferior: ");
  Serial.println(umbralInf);
  Serial.print("Umbral superior: ");
  Serial.println(umbralSup);
}

void SystemController::actualizarSensores() {
  float lectura = temperatureSensor.readCelsius();

  if (!isnan(lectura)) {
    temperaturaActual = lectura;
  } else {
    Serial.println("Se conserva la ultima temperatura valida.");
  }

}

void SystemController::evaluarEstado() {
  estadoActual = stateMachine.evaluate(temperaturaActual);
  procesarRegistroTemperatura();
}

void SystemController::actualizarSalidas() {
  displayManager.showTemperature(temperaturaActual);
  alertManager.applyState(estadoActual);
  enviarSmsSiCorresponde();
}

void SystemController::enviarSmsSiCorresponde() {
  if (stateMachine.enteredDangerState()) {
    smsManager.sendTemperatureAlert(temperaturaActual);
  }
}

void SystemController::procesarRegistroTemperatura() {
  if (!temperatureLogger.shouldSample(estadoActual)) {
    hayTemperaturaPendiente = false;
    return;
  }

  char timestamp[20];
  String timestampString = rtcClock.getTimestamp();
  timestampString.toCharArray(timestamp, sizeof(timestamp));

  if (!temperatureLogger.addSample(estadoActual, temperaturaActual, timestamp)) {
    hayTemperaturaPendiente = false;
    return;
  }

  #if DEBUG_TEMPERATURE_LOGGER
  Serial.println("Lote de temperatura completo.");
  #endif

  Serial.println("Lote de temperatura completo.");
  temperatureLogger.popBatch(
    estadoActual,
    muestrasPendientes,
    cantidadMuestrasPendientes
  );

  hayTemperaturaPendiente = true;
}

void SystemController::procesarRfid() {
  RfidEvent event = rfidManager.readEvent();

  if (!event.valid) {
    return;
  }

  #if ENABLE_SERVER_COMMUNICATION
  if (!serverClient.sendRfidEvent(event)) {
    storageManager.saveRfidEvent(event);
  }
  #else
  Serial.println("Servidor desactivado. Guardando evento RFID en EEPROM.");
  storageManager.saveRfidEvent(event);
  #endif
}

void SystemController::procesarTransmision() {
#if ENABLE_SERVER_COMMUNICATION
  if (sincronizacionInicialPendiente && millis() - ultimoIntentoSyncInicialMs >= 60000UL) {
    sincronizacionInicialPendiente = !sincronizarInicioServidor();
  }
#endif

  if (!hayTemperaturaPendiente) {
    return;
  }

#if ENABLE_SERVER_COMMUNICATION
  ServerConfigUpdate configUpdate;
  bool enviado = serverClient.syncTemperatureBatch(
    estadoActual,
    muestrasPendientes,
    cantidadMuestrasPendientes,
    configUpdate
  );

  if (enviado) {
    aplicarConfiguracionServidor(configUpdate);
  }

  if (!enviado) {
    storageManager.saveTemperatureBatch(
      estadoActual,
      muestrasPendientes,
      cantidadMuestrasPendientes
    );
  }
#else
  Serial.println("Servidor desactivado. Guardando lote en EEPROM.");

  storageManager.saveTemperatureBatch(
    estadoActual,
    muestrasPendientes,
    cantidadMuestrasPendientes
  );
#endif

  hayTemperaturaPendiente = false;
  cantidadMuestrasPendientes = 0;
}

void SystemController::reenviarPendientesSiHayConexion() {
  if (serverClient.isConnected()) {
    storageManager.flushPending(serverClient);
  }
}
