  import "./temperatura-individual.css";
  import Grafico from "./Grafico/grafico.tsx";
  import Indicadores from "./Indicadores/indicadores.tsx";
  import Controladores from "../Controladores/controladores.tsx";
  import temperaturasHeladera1 from "../../../data/TemperutaH1.ts";
  import { useEffect, useMemo, useState } from "react";
  import { Refrigerator} from "../../../interfaces/Temperatura.ts";
  import { api } from "../../../services/api.ts";
  import { DeviceAlert } from "../../../types/index.ts";

  // recorded_at viene como datetime UTC del backend ("YYYY-MM-DD HH:MM:SS").
  const formatearFecha = (valor: string): string => {
    const fecha = new Date(valor.includes("T") ? valor : valor.replace(" ", "T") + "Z");
    return Number.isNaN(fecha.getTime()) ? valor : fecha.toLocaleString();
  };

  interface TemperaturaIndividual {
    refrigerator: Refrigerator;
    useDemoData: boolean;
    onSaveRange?: (refrigeratorId: number, minTemp: number, maxTemp: number) => Promise<void>;
    readOnly?: boolean;
    onVolver: () => void;
  }

  function TemperaturaIndividual({
    refrigerator,
    useDemoData,
    onSaveRange,
    readOnly = false,
    onVolver,
  }: TemperaturaIndividual) {
    // console.log("ID del refrigerador:", refrigerator.id);

    const [VariableMinima, setVariableMinima] = useState(refrigerator.min_temp);
    const manejarCambioAmarillo = (nuevoValorAmarillo: number) => {
      setVariableMinima(nuevoValorAmarillo);
    };
    const [VariableMaxima, setVariableMaxima] = useState(refrigerator.max_temp);
    const manejarCambioRojo = (nuevoValorRojo: number) => {
      setVariableMaxima(nuevoValorRojo);
    };
    const [historialReal, setHistorialReal] = useState<Array<{ id: number; temperature: string; recorded_at: string }>>([]);
    const [historialCargando, setHistorialCargando] = useState(false);
    const [alertas, setAlertas] = useState<DeviceAlert[]>([]);
    const [resolviendoId, setResolviendoId] = useState<number | null>(null);

    useEffect(() => {
      if (useDemoData) {
        setHistorialReal([]);
        return;
      }

      let isMounted = true;

      const cargarHistorial = async () => {
        setHistorialCargando(true);
        try {
          const response = await api.get<Array<{ id: number; temperature: number; recorded_at: string }>>(
            `/devices/${refrigerator.id}/temperatures?limit=48`
          );

          if (!isMounted) return;

          const historial = Array.isArray(response.data)
            ? response.data.map((item) => ({
                id: item.id,
                temperature: Number(item.temperature).toFixed(1),
                recorded_at: item.recorded_at,
              }))
            : [];

          setHistorialReal(historial);
        } catch {
          if (isMounted) {
            setHistorialReal([]);
          }
        } finally {
          if (isMounted) {
            setHistorialCargando(false);
          }
        }
      };

      cargarHistorial();

      return () => {
        isMounted = false;
      };
    }, [refrigerator.id, useDemoData]);

    useEffect(() => {
      if (useDemoData) {
        setAlertas([]);
        return;
      }

      let isMounted = true;

      const cargarAlertas = async () => {
        try {
          const response = await api.get<DeviceAlert[]>(
            `/devices/${refrigerator.id}/alerts?limit=50`
          );
          if (isMounted) {
            setAlertas(Array.isArray(response.data) ? response.data : []);
          }
        } catch {
          if (isMounted) setAlertas([]);
        }
      };

      cargarAlertas();

      return () => {
        isMounted = false;
      };
    }, [refrigerator.id, useDemoData]);

    const alertasSinResolver = useMemo(
      () => alertas.filter((a) => Number(a.resolved) === 0),
      [alertas]
    );

    // Maquina de estados del backend: Activa -> Reconocida -> Resuelta.
    const handleAccion = async (alertId: number, accion: "acknowledge" | "resolve") => {
      setResolviendoId(alertId);
      try {
        await api.put(`/alerts/${alertId}/${accion}`, {});
        const ahora = new Date().toISOString();
        setAlertas((current) =>
          current.map((a) => {
            if (a.id !== alertId) return a;
            return accion === "resolve"
              ? { ...a, resolved: 1, resolved_at: ahora }
              : { ...a, acknowledged: 1, acknowledged_at: ahora };
          })
        );
      } catch {
        // Silencioso: si falla, la alerta queda como estaba.
      } finally {
        setResolviendoId(null);
      }
    };


    const datosTemperatura = useMemo(() => {
      if (useDemoData) {
        return temperaturasHeladera1;
      }

      if (historialReal.length > 0) {
        return historialReal;
      }

      return [{
        id: refrigerator.last_temperature.id,
        temperature: refrigerator.last_temperature.temperature.toFixed(1),
        recorded_at: refrigerator.last_temperature.recorded_at,
      }];
    }, [historialReal, refrigerator.last_temperature.id, refrigerator.last_temperature.recorded_at, refrigerator.last_temperature.temperature, useDemoData]);

    const ultimoValor =
      datosTemperatura[datosTemperatura.length - 1].temperature;
    const maximoValor = Math.max(
      ...datosTemperatura.map((t) => parseFloat(t.temperature))
    ).toFixed(1);
    const minimoValor = Math.min(
      ...datosTemperatura.map((t) => parseFloat(t.temperature))
    ).toFixed(1);


    const [controladoresActivos, setControladoresActivos] = useState<boolean>(false);
    const manejarToggleControladores = (estado: boolean) => {
      setControladoresActivos(estado);
    };


    return (
      <>
        <div className="TemperaturaTotal">
          <div className="TemperaturaIndividualTituloTotal">
            <button
              className="TemperaturaIndividualTituloBoton"
              onClick={onVolver}
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="TemperaturaIndividualSVG"
                data-id="30"
              >
                <path d="m12 19-7-7 7-7"></path>
                <path d="M19 12H5"></path>
              </svg>
            </button>
            <h2 className="TemperaturaIndividualTituloTitulo">
              {refrigerator.name} - Historial
            </h2>
          </div>
          <div className="TemperaturaIndividualContenido">
          {historialCargando && <p className="TemperaturaIndividualEstado">Cargando historial real...</p>}
          <div className="GraficoTotal">
            <div className="GraficoSolo">
              <Grafico
                datos={datosTemperatura}
                alertaMinima={VariableMinima}
                alertaMaxima={VariableMaxima}
                mostrarAlertas={controladoresActivos}
              />
            </div>
          </div>

          <div className="DetalleLateral">
          <div className="ControladoresIndicadoresTotal">
            <div className="ControladoresIndicadoresSolos">
              <Indicadores
                ultimoValor={ultimoValor}
                ubicacion={refrigerator.location}
                minValor={minimoValor}
                maxValor={maximoValor}
              />
            </div>

            <div className="Controladoressolos">
              <Controladores
                ValorMinimo={VariableMinima}
                CambiarMinimo={manejarCambioAmarillo}
                ValorMaximo={VariableMaxima}
                CambiarMaximo={manejarCambioRojo}
                onToggle={manejarToggleControladores}
                onSave={() => onSaveRange?.(refrigerator.id, VariableMinima, VariableMaxima)}
                readOnly={readOnly}

              />
            </div>
          </div>

          {!useDemoData && (
            <div className="AlertasPanel">
              <div className="AlertasPanelCabecera">
                <h3 className="AlertasPanelTitulo">Alertas de temperatura</h3>
                <span className={`AlertasBadge ${alertasSinResolver.length > 0 ? "activa" : "ok"}`}>
                  {alertasSinResolver.length > 0
                    ? `${alertasSinResolver.length} sin resolver`
                    : "Sin alertas activas"}
                </span>
              </div>

              {alertas.length === 0 ? (
                <p className="AlertasVacio">No hay alertas registradas para esta heladera.</p>
              ) : (
                <ul className="AlertasLista">
                  {alertas.map((alerta) => {
                    const resuelta = Number(alerta.resolved) === 1;
                    const reconocida = !resuelta && Number(alerta.acknowledged) === 1;
                    const estado = resuelta ? "resuelta" : reconocida ? "reconocida" : "activa";
                    const esAlta = alerta.type === "TEMP_HIGH";
                    const enCurso = resolviendoId === alerta.id;
                    return (
                      <li key={alerta.id} className={`AlertaItem ${estado}`}>
                        <span className={`AlertaTipo ${esAlta ? "alta" : "baja"}`}>
                          {esAlta ? "Temp. alta" : "Temp. baja"}
                        </span>
                        <span className="AlertaTemp">
                          {alerta.temperature !== null ? `${Number(alerta.temperature).toFixed(1)}°C` : "—"}
                        </span>
                        <span className="AlertaFecha">{formatearFecha(alerta.recorded_at)}</span>
                        <span className="AlertaEstado">
                          {resuelta ? "Resuelta" : reconocida ? "Reconocida" : "Activa"}
                        </span>
                        {!resuelta && !readOnly && (
                          <span className="AlertaAcciones">
                            {!reconocida && (
                              <button
                                className="AlertaReconocer"
                                onClick={() => handleAccion(alerta.id, "acknowledge")}
                                disabled={enCurso}
                                type="button"
                              >
                                {enCurso ? "..." : "Reconocer"}
                              </button>
                            )}
                            <button
                              className="AlertaResolver"
                              onClick={() => handleAccion(alerta.id, "resolve")}
                              disabled={enCurso}
                              type="button"
                            >
                              {enCurso ? "..." : "Resolver"}
                            </button>
                          </span>
                        )}
                      </li>
                    );
                  })}
                </ul>
              )}
            </div>
          )}
          </div>
          </div>
        </div>
      </>
    );
  }

  export default TemperaturaIndividual;
