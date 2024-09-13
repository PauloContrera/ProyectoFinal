import { useState } from "react";
import TemperaturaGrupos from "./TemperaturaGrupos/TemperaturaGrupos";
import GruposTemperatura from "../../data/temperaturaMuchasGrupal";
import './Temperatura-Total.css';
import TemperaturaIndividual from "./Temperatura-Individual/temperatura-individual";

export default function TemperaturaTotal() {
  const [selectedRefrigeratorId, setSelectedRefrigeratorId] = useState<number | null>(null);
  const [searchTerm, setSearchTerm] = useState<string>("");

  const handleVerHistorialClick = (refrigeratorId: number) => {
    setSelectedRefrigeratorId(refrigeratorId);
  };

  const handleVolver = () => {
    setSelectedRefrigeratorId(null); // Restablece el estado para volver a mostrar los grupos
  };

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value);
  };

  // Filtrar heladeras según el término de búsqueda
  const filteredGroups = GruposTemperatura.map((group) =>
    group.filter((fridge) => {
      const matchesFridgeName = fridge.name.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesGroupName = fridge.group.name.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesLocation = fridge.location.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesTemperature = fridge.last_temperature
        ? fridge.last_temperature.temperature.toString().includes(searchTerm)
        : false;

      return matchesFridgeName || matchesGroupName || matchesLocation || matchesTemperature;
    })
  ).filter(group => group.length > 0); // Eliminar grupos vacíos

  const selectedRefrigerator = selectedRefrigeratorId
    ? filteredGroups.flat().find(fridge => fridge.id === selectedRefrigeratorId)
    : null;

  return (
    <div>
      {!selectedRefrigeratorId && (
        <input
          type="text"
          placeholder="Buscar heladera, grupo, ubicación o temperatura"
          value={searchTerm}
          onChange={handleSearchChange}
          className="TemperaturaSearchInput"
        />
      )}

      {selectedRefrigerator ? (
        <TemperaturaIndividual refrigerator={selectedRefrigerator} onVolver={handleVolver} />
      ) : (
        filteredGroups.map((group, index) => (
          <TemperaturaGrupos
            key={index}
            groupData={group}
            onVerHistorialClick={handleVerHistorialClick}
          />
        ))
      )}
    </div>
  );
}
