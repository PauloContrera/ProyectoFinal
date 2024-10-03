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
    group
      .map((fridge) => {
        const filteredStock = fridge.stock.filter((item) =>
          item.name.toLowerCase().includes(searchTerm.toLowerCase())
        );

        const matchesFridgeName = fridge.name.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesGroupName = fridge.group.name.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesLocation = fridge.location.toLowerCase().includes(searchTerm.toLowerCase());

        if (matchesFridgeName || matchesGroupName || matchesLocation) {
          return { ...fridge, stock: fridge.stock };
        }

        if (filteredStock.length > 0) {
          return { ...fridge, stock: filteredStock };
        }

        return null;
      })
      .filter((fridge) => fridge !== null)
  ).filter((group) => group.length > 0);

  return (
    <div className="StockTotal">
      <div className="StockTotalCabesera">
      <h2 className="StockTotalTitulo">Control de Stock</h2>
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

      {/* Mostrar los grupos filtrados con sus respectivas tablas */}
      {filteredGroups.length > 0 ? (
        filteredGroups.map((group, index) => (
          <StockGrupos key={index} group={group} />
        ))
      ) : (
        <p>No se encontraron resultados</p>
      )}
    </div>
  );
}
