import { ReactNode, useState } from "react";
import { motion } from "framer-motion";
import "./StockGrupos.css";
import StockGruposItem, { type StockTableItem } from "./StockGruposItem/StockGruposItem";

export type { StockTableItem } from "./StockGruposItem/StockGruposItem";

export interface StockFridge {
  id: number;
  name: string;
  location: string;
  min_temp: number;
  max_temp: number;
  deviceCode?: string;
  group: {
    id: number;
    name: string;
    description: string;
  };
  stock: StockTableItem[];
}

interface StockGruposProps {
  group: StockFridge[];
  groupInfo?: StockFridge["group"];
  emptyMessage?: string;
  readOnly?: boolean;
  groupActions?: ReactNode;
  renderFridgeActions?: (fridge: StockFridge) => ReactNode;
  onAddItem?: (fridgeId: number, item: Omit<StockTableItem, "id">) => Promise<void> | void;
  onUpdateItem?: (fridgeId: number, item: StockTableItem) => Promise<void> | void;
  onDeleteItem?: (fridgeId: number, itemId: number) => Promise<void> | void;
}

export default function StockGrupos({
  group,
  groupInfo,
  emptyMessage = "Este grupo todavia no tiene heladeras.",
  readOnly = false,
  groupActions,
  renderFridgeActions,
  onAddItem,
  onUpdateItem,
  onDeleteItem,
}: StockGruposProps) {
  const [isVisible, setIsVisible] = useState(true);
  const firstGroup = group[0]?.group ?? groupInfo;

  return (
    <div className="StockGrupos">
      <div className="TempGruposTitulo StockGruposHeaderRow">
        <button
          onClick={() => setIsVisible((current) => !current)}
          className="StockGruposToggleButton"
          type="button"
        >
          <motion.div
            animate={{ rotate: isVisible ? 180 : 0 }}
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
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 15l6-6 6 6" />
            </svg>
          </motion.div>
          <span className="StockGruposHeaderContent">
            <span>
              <h2 className="StockGruposnombre">{firstGroup?.name || "Grupo"}</h2>
              {firstGroup?.description && <small>{firstGroup.description}</small>}
            </span>
          </span>
        </button>
        {groupActions && <span className="StockGruposActions">{groupActions}</span>}
      </div>

      {isVisible && (
        <motion.div
          className="TempGruposItems"
          initial={{ opacity: 0, y: -10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
        >
          {group.length > 0 ? (
            group.map((fridge) => (
              <StockGruposItem
                key={fridge.id}
                stock={fridge.stock}
                name={fridge.name}
                location={fridge.location}
                readOnly={readOnly}
                actions={renderFridgeActions?.(fridge)}
                onAddItem={onAddItem ? (item) => onAddItem(fridge.id, item) : undefined}
                onUpdateItem={onUpdateItem ? (item) => onUpdateItem(fridge.id, item) : undefined}
                onDeleteItem={onDeleteItem ? (itemId) => onDeleteItem(fridge.id, itemId) : undefined}
              />
            ))
          ) : (
            <p className="StockGruposEmpty">{emptyMessage}</p>
          )}
        </motion.div>
      )}
    </div>
  );
}
