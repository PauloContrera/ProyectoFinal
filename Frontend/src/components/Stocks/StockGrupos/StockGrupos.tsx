import { useState } from "react";
import "./StockGrupos.css";
import StockGruposItem from "./StockGruposItem/StockGruposItem";
import { motion } from "framer-motion";

interface StockGruposProps {
  group: {
    id: number;
    name: string;
    location: string;
    min_temp: number;
    max_temp: number;
    group: {
      id: number;
      name: string;
      description: string;
    };
    stock: {
      id: number;
      name: string;
      quantity: number;
      expirationDate: string;
    }[];
  }[];
}

export default function StockGrupos({ group }: StockGruposProps) {
  const [isVisible, setIsVisible] = useState<boolean>(true);
  const [isRotated, setIsRotated] = useState<boolean>(false);

  const toggleVisibility = () => {
    setIsVisible(!isVisible);
    setIsRotated(!isRotated);
  };
  return (
    <div className="StockGrupos">
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
        <h2 className="StockGruposnombre" >{group[0]?.group.name || "Grupo"}</h2>
      </button>

      {isVisible && (
        <motion.div
          className="TempGruposItems"
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
        >
          {group.map((fridge, index) => (
            <div key={index}>


              <StockGruposItem stock={fridge.stock} name={fridge.name} location={fridge.location} />
            </div>
          ))}
        </motion.div>
      )}
    </div>
  );
}
