  import "./temperatura-individual.css";
  import Grafico from "./Grafico/grafico.tsx";
  import Indicadores from "./Indicadores/indicadores.tsx";
  import Controladores from "../Controladores/controladores.tsx";
  import temperaturasHeladera1 from "../../../data/TemperutaH1.ts";
  import { useEffect, useMemo, useState } from "react";
  import { Refrigerator} from "../../../interfaces/Temperatura.ts";
  import { api } from "../../../services/api.ts";

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
          </div>
        </div>
      </>
    );
  }

  export default TemperaturaIndividual;
