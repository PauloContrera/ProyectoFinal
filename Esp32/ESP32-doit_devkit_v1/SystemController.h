#pragma once

#include <Arduino.h>
#include "types.h"
#include "config.h"

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
  char timestampActual[20];
  EstadoTemperatura estadoActual;

  bool hayTemperaturaPendiente;
  TemperatureSample muestrasPendientes[MAX_SAMPLES];
  uint8_t cantidadMuestrasPendientes;

  void cargarConfiguracion();
  void procesarRegistroTemperatura();
  void enviarSmsSiCorresponde();
  void reenviarPendientesSiHayConexion();
};

extern SystemController systemController;