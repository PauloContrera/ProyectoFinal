#pragma once

#include <Arduino.h>
#include "types.h"
#include "config.h"

struct ServerConfigUpdate;

class SystemController {
public:
  void begin();

  void actualizarSensores();
  void evaluarEstado();
  void actualizarSalidas();
  void procesarRfid();
  void procesarTransmision();

private:
  float temperaturaActual;
  EstadoTemperatura estadoActual;

  bool hayTemperaturaPendiente;
  bool sincronizacionInicialPendiente;
  unsigned long ultimoIntentoSyncInicialMs;
  TemperatureSample muestrasPendientes[MAX_SAMPLES];
  uint8_t cantidadMuestrasPendientes;

  void cargarConfiguracion();
  bool sincronizarInicioServidor();
  void aplicarConfiguracionServidor(const ServerConfigUpdate& configUpdate);
  void procesarRegistroTemperatura();
  void enviarSmsSiCorresponde();
  void reenviarPendientesSiHayConexion();
};

extern SystemController systemController;
