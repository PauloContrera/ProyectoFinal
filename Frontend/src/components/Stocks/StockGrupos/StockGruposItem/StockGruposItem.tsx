import { useState } from "react";
import "./StockGruposItem.css";
import { motion } from "framer-motion";

interface StockItem {
  id: number;
  name: string;
  quantity: number;
  expirationDate: string;
}

interface StockGruposItemProps {
  stock: StockItem[];
  name: string;
  location: string;
}

export default function StockGruposItem({
  stock,
  name,
  location,
}: StockGruposItemProps) {
  const [isVisible, setIsVisible] = useState<boolean>(true);
  const [isRotated, setIsRotated] = useState<boolean>(false);

  const toggleVisibility = () => {
    setIsVisible(!isVisible);
    setIsRotated(!isRotated);
  };
  return (
    <div className="stock-grupos-container">
      <button onClick={toggleVisibility} className="TempGruposTitulo">
        <motion.div
          animate={{ rotate: isRotated ? 0 : 180 }}
          transition={{ duration: 0.3 }}
          className="TempGruposTituloBoton"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            className="ToggleIcon"
            width="24"
            height="24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M6 15l6-6 6 6"
            />
          </svg>
        </motion.div>
        <h3>
          {name} - {location}
        </h3>
      </button>
      {isVisible && (
        <motion.div
          className="TempGruposItems"
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
        >
          <div className="stock-gruposItemTotal">
          <table className="stock-grupos-table">
            <thead className="stock-grupos-thead">
              <tr>
                <th className="stock-grupos-th">Artículo</th>
                <th className="stock-grupos-th">Cantidad</th>
                <th className="stock-grupos-th">Fecha de Vencimiento</th>
                <th className="stock-grupos-th">Acciones</th>
              </tr>
            </thead>
            <tbody className="stock-grupos-tbody">
              {stock.map((item, index) => (
                <tr key={index} className="stock-grupos-tr">
                  <td className="stock-grupos-td">{item.name}</td>
                  <td className="stock-grupos-td">{item.quantity}</td>
                  <td className="stock-grupos-td">{item.expirationDate}</td>
                  <td className="stock-grupos-td">
                    <button className="stock-grupos-button">-</button>
                    <button className="stock-grupos-button">+</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          </div>
        </motion.div>
      )}
    </div>
  );
}
