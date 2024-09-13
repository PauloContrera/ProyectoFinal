import { useState } from "react";
import StockGrupos from "./StockGrupos/StockGrupos";
import GruposStocks from "../../data/Stocks";
import "./StockTotal.css";

export default function StockTotal() {
  const [searchTerm, setSearchTerm] = useState<string>("");

  // Manejar el cambio en el input de búsqueda
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value);
  };

  // Filtrar grupos, heladeras, artículos y ubicación según el término de búsqueda
  const filteredGroups = GruposStocks.map((group) =>
    group.map((fridge) => {
      // Filtrar artículos en la heladera según el término de búsqueda
      const filteredStock = fridge.stock.filter((item) =>
        item.name.toLowerCase().includes(searchTerm.toLowerCase())
      );

      // Verificar si el término de búsqueda coincide con el nombre de la heladera, grupo o ubicación
      const matchesFridgeName = fridge.name.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesGroupName = fridge.group.name.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesLocation = fridge.location.toLowerCase().includes(searchTerm.toLowerCase());

      // Si coincide con el nombre de heladera, grupo o ubicación, mantenemos todos los artículos
      if (matchesFridgeName || matchesGroupName || matchesLocation) {
        return { ...fridge, stock: fridge.stock }; // Devolver la heladera completa
      }

      // Si coincide con algún artículo, solo mantenemos los artículos filtrados
      if (filteredStock.length > 0) {
        return { ...fridge, stock: filteredStock }; // Devolver heladera con stock filtrado
      }

      // Si no hay coincidencias, devolver null
      return null;
    })
    .filter(fridge => fridge !== null) // Eliminar las heladeras nulas
  ).filter(group => group.length > 0); // Eliminar grupos vacíos

  return (
    <div className="StockTotal">
      <input
        type="text"
        placeholder="Buscar heladera, grupo, ubicación o artículo"
        value={searchTerm}
        onChange={handleSearchChange}
        className="StockSearchInput"
      />

      {/* Mostrar los grupos filtrados con sus respectivas tablas */}
      {filteredGroups.map((group, index) => (
        <StockGrupos key={index} group={group} />
      ))}
    </div>
  );
}
