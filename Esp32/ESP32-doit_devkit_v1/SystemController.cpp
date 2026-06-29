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

SystemController systemController;

void SystemController::begin() {
  Serial.begin(115200);

  temperatureSensor.begin();
  rtcClock.begin();
  displayManager.begin();
  alertManager.begin();
  smsManager.begin();
  storageManager.begin();

  cargarConfiguracion();

  #if ENABLE_SERVER_COMMUNICATION
  serverClient.begin();
  #endif
  rfidManager.begin();

  temperaturaActual = 0.0f;
  estadoActual = ESTADO_NORMAL;
  hayTemperaturaPendiente = false;
  cantidadMuestrasPendientes = 0;

  Serial.println("Sistema iniciado.");
}

void SystemController::cargarConfiguracion() {
  //storageManager.saveThresholds(2, 8);
  int umbralInf = storageManager.getUmbralInf();
  int umbralSup = storageManager.getUmbralSup();

  stateMachine.begin(umbralInf, umbralSup);

#if ENABLE_SERVER_COMMUNICATION
  if (serverClient.fetchThresholds(umbralInf, umbralSup)) {
    storageManager.saveThresholds(umbralInf, umbralSup);
    stateMachine.begin(umbralInf, umbralSup);
  }
#endif

  Serial.print("Umbral inferior: ");
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

  String timestamp = rtcClock.getTimestamp();
  timestamp.toCharArray(timestampActual, sizeof(timestampActual));
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
  if (!temperatureLogger.registerSample(estadoActual, temperaturaActual, timestampActual)) {
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
  if (!hayTemperaturaPendiente) {
    return;
  }

#if ENABLE_SERVER_COMMUNICATION
  bool enviado = serverClient.syncTemperatureBatch(
    estadoActual,
    muestrasPendientes,
    cantidadMuestrasPendientes
  );

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