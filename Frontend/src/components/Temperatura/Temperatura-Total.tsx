import { useState } from "react";
import TemperaturaGrupos from "./TemperaturaGrupos/TemperaturaGrupos";
import GruposTemperatura from "../../data/temperaturaMuchasGrupal";
import "./Temperatura-Total.css";
import TemperaturaIndividual from "./Temperatura-Individual/temperatura-individual";

export default function TemperaturaTotal() {
  const [selectedRefrigeratorId, setSelectedRefrigeratorId] = useState<
    number | null
  >(null);
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
      const matchesFridgeName = fridge.name
        .toLowerCase()
        .includes(searchTerm.toLowerCase());
      const matchesGroupName = fridge.group.name
        .toLowerCase()
        .includes(searchTerm.toLowerCase());
      const matchesLocation = fridge.location
        .toLowerCase()
        .includes(searchTerm.toLowerCase());
      const matchesTemperature = fridge.last_temperature
        ? fridge.last_temperature.temperature.toString().includes(searchTerm)
        : false;

      return (
        matchesFridgeName ||
        matchesGroupName ||
        matchesLocation ||
        matchesTemperature
      );
    })
  ).filter((group) => group.length > 0); // Eliminar grupos vacíos

  const selectedRefrigerator = selectedRefrigeratorId
    ? filteredGroups
        .flat()
        .find((fridge) => fridge.id === selectedRefrigeratorId)
    : null;

  return (
    <div className="TemperaturaTotal">
      {!selectedRefrigeratorId && (
        <div className="StockTotalCabesera">
          <h2 className="StockTotalTitulo">Temperaturas</h2>
          <div className="search-bar-container">
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
              className="search-icon"
            >
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.3-4.3"></path>
            </svg>
            <input
              className="search-input"
              type="text"
              placeholder="Buscar"
              value={searchTerm}
              onChange={handleSearchChange}
            />
          </div>
        </div>
      )}

      {selectedRefrigerator ? (
        <TemperaturaIndividual
          refrigerator={selectedRefrigerator}
          onVolver={handleVolver}
        />
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
