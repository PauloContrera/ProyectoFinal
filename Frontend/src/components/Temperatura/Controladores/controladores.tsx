import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import "./controladores.css";

interface ControladoresProps {
  ValorMinimo: number;
  CambiarMinimo: (nuevoValor: number) => void;
  ValorMaximo: number;
  CambiarMaximo: (nuevoValor: number) => void;
  onToggle: (estado: boolean) => void;
  onSave?: () => Promise<void> | void;
  readOnly?: boolean;
}

const Controladores: React.FC<ControladoresProps> = ({
  ValorMinimo,
  CambiarMinimo,
  ValorMaximo,
  CambiarMaximo,
  onToggle,
  onSave,
  readOnly = false,
}) => {
  const [isOn, setIsOn] = useState<boolean>(false);
  const [editMode, setEditMode] = useState<boolean>(false);
  const [saveState, setSaveState] = useState<"idle" | "saving" | "saved" | "error">("idle");

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

  const handleInputChangeMin = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = parseFloat(e.target.value);
    if (!isNaN(newValue)) {
      CambiarMinimo(newValue);
    }
  };

  const handleInputChangeMax = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = parseFloat(e.target.value);
    if (!isNaN(newValue)) {
      CambiarMaximo(newValue);
    }
  };

  const handleEditToggle = async () => {
    if (readOnly) return;

    if (!editMode) {
      setSaveState("idle");
      setEditMode(true);
      return;
    }

    try {
      setSaveState("saving");
      await onSave?.();
      setSaveState("saved");
      setEditMode(false);
    } catch {
      setSaveState("error");
    }
  };

  return (
    <div className="ControladoresContainer">
      {/* Switch de activación */}
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

      {/* Controles de temperatura */}
      <AnimatePresence>
        {isOn && (
          <motion.div
            initial={{ y: 50, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            exit={{ y: 50, opacity: 0 }}
            transition={{ duration: 0.3 }}
          >
            <div className="TempControladores">
              <div className="TempControladores-control">
                <p className="TempControladores-label">Alerta Mínima</p>
                <div className="TempControladores-buttons">
                  <button
                    className="TempControladores-button-minus"
                    onClick={RestarVariableMinimo}
                    disabled={readOnly || !editMode || saveState === "saving"}
                  >
                    <svg
                      className="TempControladores-icon"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M5 12h14" />
                    </svg>
                  </button>
                  <input
                    className="TempControladores-value"
                    type="number"
                    value={ValorMinimo}
                    onChange={handleInputChangeMin}
                    disabled={readOnly || !editMode || saveState === "saving"}
                    step="0.1"
                  />
                  <button
                    className="TempControladores-button-plus"
                    onClick={SumarVariableMinimo}
                    disabled={readOnly || !editMode || saveState === "saving"}
                  >
                    <svg
                      className="TempControladores-icon"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M5 12h14" />
                      <path d="M12 5v14" />
                    </svg>
                  </button>
                </div>
              </div>

              <div className="TempControladores-control">
                <p className="TempControladores-label">Alerta Máxima</p>
                <div className="TempControladores-buttons">
                  <button
                    className="TempControladores-button-minus"
                    onClick={RestarVariableMaximo}
                    disabled={readOnly || !editMode || saveState === "saving"}
                  >
                    <svg
                      className="TempControladores-icon"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M5 12h14" />
                    </svg>
                  </button>
                  <input
                    className="TempControladores-value"
                    type="number"
                    value={ValorMaximo}
                    onChange={handleInputChangeMax}
                    disabled={readOnly || !editMode || saveState === "saving"}
                    step="0.1"
                  />
                  <button
                    className="TempControladores-button-plus"
                    onClick={SumarVariableMaximo}
                    disabled={readOnly || !editMode || saveState === "saving"}
                  >
                    <svg
                      className="TempControladores-icon"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M5 12h14" />
                      <path d="M12 5v14" />
                    </svg>
                  </button>
                </div>
              </div>

              <div className="TempControladores-edit">
                <button
                  className="TempControladores-button-edit"
                  onClick={handleEditToggle}
                  disabled={readOnly || saveState === "saving"}
                >
                  {readOnly ? "Solo lectura" : saveState === "saving" ? "Guardando..." : editMode ? "Guardar" : "Editar"}
                </button>
              </div>
              {saveState === "saved" && <p className="TempControladores-status success">Rangos guardados.</p>}
              {saveState === "error" && <p className="TempControladores-status error">No se pudieron guardar los rangos.</p>}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default Controladores;
