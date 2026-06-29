import { ReactNode, useEffect, useState } from "react";
import { Check, Pencil, Plus, Trash2, X } from "lucide-react";
import { motion } from "framer-motion";
import "./StockGruposItem.css";

export interface StockTableItem {
  id: number;
  name: string;
  quantity: number;
  expirationDate: string;
}

type StockDraft = {
  name: string;
  quantity: string;
  expirationDate: string;
};

interface StockGruposItemProps {
  stock: StockTableItem[];
  name: string;
  location: string;
  readOnly?: boolean;
  actions?: ReactNode;
  onAddItem?: (item: Omit<StockTableItem, "id">) => Promise<void> | void;
  onUpdateItem?: (item: StockTableItem) => Promise<void> | void;
  onDeleteItem?: (id: number) => Promise<void> | void;
}

const emptyDraft = (): StockDraft => ({
  name: "",
  quantity: "0",
  expirationDate: "",
});

const toDraft = (item: StockTableItem): StockDraft => ({
  name: item.name,
  quantity: String(item.quantity),
  expirationDate: item.expirationDate,
});

const toItem = (draft: StockDraft, id: number): StockTableItem => ({
  id,
  name: draft.name.trim(),
  quantity: Math.max(0, Number(draft.quantity) || 0),
  expirationDate: draft.expirationDate,
});

export default function StockGruposItem({
  stock,
  name,
  location,
  readOnly = false,
  actions,
  onAddItem,
  onUpdateItem,
  onDeleteItem,
}: StockGruposItemProps) {
  const [isVisible, setIsVisible] = useState(true);
  const [items, setItems] = useState<StockTableItem[]>(stock);
  const [draft, setDraft] = useState<StockDraft>(emptyDraft);
  const [showForm, setShowForm] = useState(false);
  const [editItemId, setEditItemId] = useState<number | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const hasRemoteHandlers = Boolean(onAddItem || onUpdateItem || onDeleteItem);

  useEffect(() => {
    setItems(stock);
  }, [stock]);

  const toggleVisibility = () => setIsVisible((current) => !current);

  const resetDraft = () => {
    setDraft(emptyDraft());
    setEditItemId(null);
    setShowForm(false);
  };

  const updateDraft = (field: keyof StockDraft, value: string) => {
    setDraft((current) => ({ ...current, [field]: value }));
  };

  const handleAddNewItem = async () => {
    const nextItem = toItem(draft, Date.now());
    if (!nextItem.name) return;

    setIsSaving(true);
    try {
      if (onAddItem) {
        await onAddItem({
          name: nextItem.name,
          quantity: nextItem.quantity,
          expirationDate: nextItem.expirationDate,
        });
      }
      if (!hasRemoteHandlers) {
        setItems((current) => [...current, nextItem]);
      }
      resetDraft();
    } finally {
      setIsSaving(false);
    }
  };

  const handleEdit = (item: StockTableItem) => {
    setEditItemId(item.id);
    setDraft(toDraft(item));
    setShowForm(false);
  };

  const handleSave = async () => {
    if (editItemId === null) return;

    const nextItem = toItem(draft, editItemId);
    if (!nextItem.name) return;

    setIsSaving(true);
    try {
      if (onUpdateItem) {
        await onUpdateItem(nextItem);
      }
      if (!hasRemoteHandlers) {
        setItems((current) =>
          current.map((item) => (item.id === editItemId ? nextItem : item))
        );
      }
      resetDraft();
    } finally {
      setIsSaving(false);
    }
  };

  const handleDelete = async (id: number) => {
    setIsSaving(true);
    try {
      if (onDeleteItem) {
        await onDeleteItem(id);
      }
      if (!hasRemoteHandlers) {
        setItems((current) => current.filter((item) => item.id !== id));
      }
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="stock-grupos-container">
      <div className="TempGruposTitulo StockItemsHeaderRow">
        <button onClick={toggleVisibility} className="StockItemsToggleButton" type="button">
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
          <span className="StockItemsHeader">
            <h3 className="StockItemsTitulo">
              {name} - {location || "Sin ubicacion"}
            </h3>
          </span>
        </button>
        {actions && <span className="StockItemsHeaderActions">{actions}</span>}
      </div>

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
                  <th className="stock-grupos-th">Articulo</th>
                  <th className="stock-grupos-th">Cantidad</th>
                  <th className="stock-grupos-th">Vencimiento</th>
                  {!readOnly && <th className="stock-grupos-th">Acciones</th>}
                </tr>
              </thead>
              <tbody className="stock-grupos-tbody">
                {items.length === 0 && (
                  <tr className="stock-grupos-tr">
                    <td className="stock-grupos-td stock-grupos-readonly" colSpan={readOnly ? 3 : 4}>
                      Sin items cargados
                    </td>
                  </tr>
                )}

                {items.map((item) => (
                  <tr key={item.id} className="stock-grupos-tr">
                    {editItemId === item.id ? (
                      <>
                        <td className="stock-grupos-td">
                          <input
                            className="StockItemsEntradas"
                            placeholder="Nombre del articulo"
                            value={draft.name}
                            onChange={(event) => updateDraft("name", event.target.value)}
                          />
                        </td>
                        <td className="stock-grupos-td">
                          <input
                            className="StockItemsEntradas StockItemsEntradasCentrado"
                            type="number"
                            min="0"
                            placeholder="Cantidad"
                            value={draft.quantity}
                            onChange={(event) => updateDraft("quantity", event.target.value)}
                          />
                        </td>
                        <td className="stock-grupos-td">
                          <input
                            className="StockItemsEntradas StockItemsEntradasCentrado"
                            type="date"
                            value={draft.expirationDate}
                            onChange={(event) => updateDraft("expirationDate", event.target.value)}
                          />
                        </td>
                        <td className="stock-grupos-td">
                          <div className="stock-grupos-actions">
                            <button className="custom-button" onClick={handleSave} disabled={isSaving} type="button" title="Guardar item">
                              <Check className="custom-icon" />
                            </button>
                            <button className="custom-button" onClick={resetDraft} disabled={isSaving} type="button" title="Cancelar">
                              <X className="custom-icon" />
                            </button>
                          </div>
                        </td>
                      </>
                    ) : (
                      <>
                        <td className="stock-grupos-td">{item.name}</td>
                        <td className="stock-grupos-td">{item.quantity}</td>
                        <td className="stock-grupos-td">{item.expirationDate || "Sin vencimiento"}</td>
                        {!readOnly && (
                          <td className="stock-grupos-td">
                            <div className="stock-grupos-actions">
                              <button className="custom-button" onClick={() => handleEdit(item)} disabled={isSaving} type="button" title="Editar item">
                                <Pencil className="custom-icon" />
                              </button>
                              <button className="custom-button" onClick={() => handleDelete(item.id)} disabled={isSaving} type="button" title="Eliminar item">
                                <Trash2 className="custom-icon" />
                              </button>
                            </div>
                          </td>
                        )}
                      </>
                    )}
                  </tr>
                ))}

                {!readOnly && showForm && (
                  <tr className="stock-grupos-tr">
                    <td className="stock-grupos-td">
                      <input
                        className="StockItemsEntradas"
                        placeholder="Nombre del articulo"
                        value={draft.name}
                        onChange={(event) => updateDraft("name", event.target.value)}
                      />
                    </td>
                    <td className="stock-grupos-td">
                      <input
                        className="StockItemsEntradas"
                        type="number"
                        min="0"
                        placeholder="Cantidad"
                        value={draft.quantity}
                        onChange={(event) => updateDraft("quantity", event.target.value)}
                      />
                    </td>
                    <td className="stock-grupos-td">
                      <input
                        className="StockItemsEntradas"
                        type="date"
                        value={draft.expirationDate}
                        onChange={(event) => updateDraft("expirationDate", event.target.value)}
                      />
                    </td>
                    <td className="stock-grupos-td">
                      <div className="stock-grupos-actions">
                        <button className="custom-button" onClick={handleAddNewItem} disabled={isSaving} type="button" title="Guardar item">
                          <Check className="custom-icon" />
                        </button>
                        <button className="custom-button" onClick={resetDraft} disabled={isSaving} type="button" title="Cancelar">
                          <X className="custom-icon" />
                        </button>
                      </div>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>

            {!readOnly && (
              <button
                className="StockItemBoton"
                onClick={() => {
                  setShowForm((current) => !current);
                  setEditItemId(null);
                  setDraft(emptyDraft());
                }}
                disabled={isSaving}
                type="button"
              >
                {showForm ? "Cancelar" : (
                  <>
                    <Plus size={16} />
                    Agregar Nuevo Articulo
                  </>
                )}
              </button>
            )}
          </div>
        </motion.div>
      )}
    </div>
  );
}
