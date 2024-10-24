import "./TemperaturaGruposItem.css";
import { Refrigerator } from "../../../../interfaces/Temperatura";
import Controladores from "../../../Temperatura/Controladores/controladores";
import { useState } from "react";

interface TemperaturaGruposItemProps {
  refrigerator: Refrigerator;
  onVerHistorialClick: (refrigeratorId: number) => void;
}

export default function TemperaturaGruposItem({ refrigerator, onVerHistorialClick }: TemperaturaGruposItemProps) {
  const redondearTemperatura = (temperatura: number) => {
    return temperatura.toFixed(1); // toFixed ya puede aplicarse a números directamente
  };
  const [variableMinima, setVariableMinima] = useState<number>(refrigerator.min_temp);
  const [variableMaxima, setVariableMaxima] = useState<number>(refrigerator.max_temp);
  
  

  const manejarCambioMinimo = (nuevoValorMinimo: number) => {
    setVariableMinima(nuevoValorMinimo);
  };

  const manejarCambioMaximo = (nuevoValorMaximo: number) => {
    setVariableMaxima(nuevoValorMaximo);
  };
  const manejarCambioMaximo2 = () => {
  };


  const temperaturaActual = refrigerator.last_temperature.temperature;
  const estaEnRango = temperaturaActual >= variableMinima && temperaturaActual <= variableMaxima;
  const temperaturaClass = estaEnRango ? "TempItemsValorValorEnRango" : "TempItemsValorValorFueraDeRango";

  return (
    <div className="TempItemsTotal">
      <div className="TempItemsTextoTotal">
        <div className="TempItemsTexto">
          <h3 className="TempItemsTextoTitulo">{refrigerator.name}</h3>
          <p className="TempItemsTextoUbi">{refrigerator.location}</p>
        </div>

      </div>
      <div className="TempItemsValorTotal">
        <p className="TempItemsValorTexto">Última valor:</p>
        <p className={`TempItemsValorValor ${temperaturaClass}`}>
          {redondearTemperatura(refrigerator.last_temperature.temperature)}°C
        </p>
      </div>
      <Controladores
        ValorMinimo={variableMinima}
        CambiarMinimo={manejarCambioMinimo}
        ValorMaximo={variableMaxima}
        CambiarMaximo={manejarCambioMaximo}
        onToggle={manejarCambioMaximo2}
      />
      <div className="TempItemsHistorial">
        <button className="TempItemsHistorialBoton" onClick={() => onVerHistorialClick(refrigerator.id)}>
          Ver Historial
        </button>
      </div>
    </div>
  );
}
