import "./temperatura-individual.css";
import Grafico from "./Grafico/grafico.tsx";
import Indicadores from "./Indicadores/indicadores.tsx";
import Controladores from "../Controladores/controladores.tsx";
import temperaturasHeladera1 from "../../../data/TemperutaH1.ts";
import { useState } from "react";
import { Refrigerator } from "../../../interfaces/Temperatura.ts";

interface TemperaturaIndividual {
  refrigerator: Refrigerator;
  onVolver: any;
}

function TemperaturaIndividual({
  refrigerator,
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

  const ultimoValor =
    temperaturasHeladera1[temperaturasHeladera1.length - 1].temperature;
  const maximoValor = Math.max(
    ...temperaturasHeladera1.map((t) => parseFloat(t.temperature))
  ).toFixed(1);
  const minimoValor = Math.min(
    ...temperaturasHeladera1.map((t) => parseFloat(t.temperature))
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
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
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
        <div className="GraficoTotal">
          <div className="GraficoSolo">
            <Grafico
              datos={temperaturasHeladera1}
              mostrarAlertas={true}
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
      
            />
          </div>
        </div>
        </div>
      </div>
    </>
  );
}

export default TemperaturaIndividual;
