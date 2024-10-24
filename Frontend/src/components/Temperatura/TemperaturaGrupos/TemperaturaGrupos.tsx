import { useState } from "react";
import TemperaturaGruposItem from "./TemperaturaGruposIndividual/TemperaturaGruposItem";
import "./TemperaturaGrupos.css";
import { GroupData } from "../../../interfaces/Temperatura"; // Ajusta la ruta según tu estructura de carpetas
import { motion } from "framer-motion";

interface TemperaturaGruposProps {
  groupData: GroupData;
  onVerHistorialClick: (refrigeratorId: number) => void;
}

export default function TemperaturaGrupos({
  groupData,
  onVerHistorialClick
}: TemperaturaGruposProps) {
  const [isVisible, setIsVisible] = useState<boolean>(true);
  const [isRotated, setIsRotated] = useState<boolean>(false);

  const toggleVisibility = () => {
    setIsVisible(!isVisible);
    setIsRotated(!isRotated);
  };

  return (
    <div className="TempGruposTotal">
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
              d="M6 15l6-6 6 6" // Dibuja una flecha ^
            />
          </svg>
        </motion.div>
        {/* <h2>{groupData[0]?.group.name || "Grupo"}</h2> */}
        <h2>{groupData.length > 0 && groupData[0]?.group?.name ? groupData[0].group.name : "Grupo"}</h2>


      </button>
      {isVisible && (
        <motion.div
          className="TempGruposItems"
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
        >
          {/* <p className="TempGruposItemsDescripcion">{groupData[0]?.group.description}</p> */}
          <p className="TempGruposItemsDescripcion">
  {groupData.length > 0 && groupData[0]?.group?.description ? groupData[0].group.description : "Descripción no disponible"}
</p>
          <div className="TempGruposItemsGrilla">
            {groupData.map((refrigerator) => (
              <TemperaturaGruposItem
                key={refrigerator.id}
                refrigerator={refrigerator}
                onVerHistorialClick={onVerHistorialClick}
              />
            ))}
          </div>
        </motion.div>
      )}
    </div>
  );
}
