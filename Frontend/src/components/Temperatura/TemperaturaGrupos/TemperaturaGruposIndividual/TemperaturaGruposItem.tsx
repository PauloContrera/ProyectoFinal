import "./TemperaturaGruposItem.css";
import { Info } from "lucide-react";
import { Refrigerator } from "../../../../interfaces/Temperatura";
import Controladores from "../../../Temperatura/Controladores/controladores";
import { useState } from "react";

interface TemperaturaGruposItemProps {
  refrigerator: Refrigerator;
  onVerHistorialClick: (refrigeratorId: number) => void;
}

export default function TemperaturaGruposItem({ refrigerator, onVerHistorialClick }: TemperaturaGruposItemProps) {
  const redondearTemperatura = (temperatura: string) => {
    return parseFloat(temperatura).toFixed(1);
  };
  const [variableMinima, setVariableMinima] = useState<number>(parseFloat(refrigerator.min_temp));
  const [variableMaxima, setVariableMaxima] = useState<number>(parseFloat(refrigerator.max_temp));

  const manejarCambioMinimo = (nuevoValorMinimo: number) => {
    setVariableMinima(nuevoValorMinimo);
  };

  const manejarCambioMaximo = (nuevoValorMaximo: number) => {
    setVariableMaxima(nuevoValorMaximo);
  };

  return (
    <div className="TempItemsTotal">
      <div className="TempItemsTextoTotal">
        <div className="TempItemsTexto">
          <h3 className="TempItemsTextoTitulo">{refrigerator.name}</h3>
          <p className="TempItemsTextoUbi">{refrigerator.location}</p>
        </div>
        <button className="TempItemsTextoInfo">
          <Info className="Info" />
        </button>
      </div>
      <div className="TempItemsValorTotal">
        <p className="TempItemsValorTexto">Última valor:</p>
        <p className="TempItemsValorValor">
          {redondearTemperatura(refrigerator.last_temperature.temperature)}°C
        </p>
      </div>
      <Controladores
        ValorMinimo={variableMinima}
        CambiarMinimo={manejarCambioMinimo}
        ValorMaximo={variableMaxima}
        CambiarMaximo={manejarCambioMaximo}
      />
      <div className="TempItemsHistorial">
        <button className="TempItemsHistorialBoton" onClick={() => onVerHistorialClick(refrigerator.id)}>
          Ver Historial
        </button>
      </div>
    </div>
  );
}