import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import "./controladores.css";

interface ControladoresProps {
  ValorMinimo: number;
  CambiarMinimo: (nuevoValor: number) => void;
  ValorMaximo: number;
  CambiarMaximo: (nuevoValor: number) => void;
  onToggle: (estado: boolean) => void;
}

const Controladores: React.FC<ControladoresProps> = ({
  ValorMinimo,
  CambiarMinimo,
  ValorMaximo,
  CambiarMaximo,
  onToggle,
}) => {
  const [isOn, setIsOn] = useState<boolean>(false);
  
  const toggleSwitch = () => {
    const nuevoEstado = !isOn;
    setIsOn(nuevoEstado);
    onToggle(nuevoEstado); 
  };
  const spring = {
    type: "spring",
    stiffness: 700,
    damping: 30,
  };

  const SumarVariableMinimo = () => {
    CambiarMinimo(parseFloat((ValorMinimo + 0.1).toFixed(1)));
  };

  const RestarVariableMinimo = () => {
    CambiarMinimo(parseFloat((ValorMinimo - 0.1).toFixed(1)));
  };

  const SumarVariableMaximo = () => {
    CambiarMaximo(parseFloat((ValorMaximo + 0.1).toFixed(1)));
  };

  const RestarVariableMaximo = () => {
    CambiarMaximo(parseFloat((ValorMaximo - 0.1).toFixed(1)));
  };

  return (
    <div className="ControladoresContainer">
      <div className="ControladoresActivar">
        <span className="ControladoresActivarTexto">Controladores:</span>
        <div
          className="ControladoresActivarSwitch"
          data-ison={isOn}
          onClick={toggleSwitch}
        >
          <motion.div
            className="ControladoresActivarHandle"
            layout
            transition={spring}
          />
        </div>
      </div>

      <AnimatePresence>
        {isOn && (
          <motion.div
            initial={{ y: 50, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            exit={{ y: 50, opacity: 0 }}
            transition={{
              duration: 0.3,
            }}
          >
            <div className="ControladoresTotal">
              <div className="ControladoresSoloTotal">
                <h3 className="ControladoresSolotitulo">Alerta Mínima</h3>
                <div className="ControladoresSolo">
                  <button
                    className="ControladoresSolobutton ControladoresSolorestar"
                    onClick={RestarVariableMinimo}
                  >
                    ⬇
                  </button>
                  <button className="ControladoresSoloTexto">
                    {ValorMinimo}
                  </button>
                  <button
                    className="ControladoresSolobutton ControladoresSolosumar"
                    onClick={SumarVariableMinimo}
                  >
                    ⬆
                  </button>
                </div>
              </div>
              <div className="ControladoresSoloTotal">
                <h3 className="ControladoresSolotitulo">Alerta Máxima</h3>
                <div className="ControladoresSolo">
                  <button
                    className="ControladoresSolobutton ControladoresSolorestar"
                    onClick={RestarVariableMaximo}
                  >
                    ⬇
                  </button>
                  <button className="ControladoresSoloTexto">
                    {ValorMaximo}
                  </button>
                  <button
                    className="ControladoresSolobutton ControladoresSolosumar"
                    onClick={SumarVariableMaximo}
                  >
                    ⬆
                  </button>
                </div>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default Controladores;
