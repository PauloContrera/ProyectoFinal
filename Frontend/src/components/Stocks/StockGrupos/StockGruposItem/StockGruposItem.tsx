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
  const [newStock, setNewStock] = useState<StockItem[]>(stock); // Inicializamos con los datos recibidos
  const [tempItem, setTempItem] = useState<StockItem>({
    id: Date.now(),
    name: "",
    quantity: 0,
    expirationDate: "",
  });

  // Estado para controlar la visibilidad del formulario
  const [showForm, setShowForm] = useState<boolean>(false);

  // Estado para controlar la fila que se está editando
  const [editItemId, setEditItemId] = useState<number | null>(null);

  const toggleVisibility = () => {
    setIsVisible(!isVisible);
    setIsRotated(!isRotated);
  };

  const handleAddNewItem = () => {
    setNewStock([...newStock, tempItem]);
    // Reseteamos el formulario y volvemos a mostrar el botón "Agregar Nuevo Artículo"
    setTempItem({
      id: Date.now(),
      name: "",
      quantity: 0,
      expirationDate: "",
    });
    setShowForm(false);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setTempItem((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  // Función para cancelar el formulario
  const handleCancel = () => {
    // Reseteamos el formulario y ocultamos la sección de agregar
    setTempItem({
      id: Date.now(),
      name: "",
      quantity: 0,
      expirationDate: "",
    });
    setShowForm(false);
  };

  // Alternar visibilidad del formulario
  const toggleFormVisibility = () => {
    setShowForm(!showForm);
  };

  // Función para comenzar a editar un item
  const handleEdit = (item: StockItem) => {
    setEditItemId(item.id);
    setTempItem(item);
  };

  // Función para guardar los cambios de edición
  const handleSave = () => {
    setNewStock((prev) =>
      prev.map((item) => (item.id === editItemId ? { ...tempItem } : item))
    );
    setEditItemId(null);
    setTempItem({
      id: Date.now(),
      name: "",
      quantity: 0,
      expirationDate: "",
    });
  };

  // Función para cancelar la edición
  const handleCancelEdit = () => {
    setEditItemId(null);
    setTempItem({
      id: Date.now(),
      name: "",
      quantity: 0,
      expirationDate: "",
    });
  };

  // Función para eliminar un item
  const handleDelete = (id: number) => {
    setNewStock((prev) => prev.filter((item) => item.id !== id));
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
        <h3 className="StockItemsTitulo">
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
                {newStock.map((item, index) => (
                  <tr key={index} className="stock-grupos-tr">
                    {editItemId === item.id ? (
                      <>
                        <td className="stock-grupos-td">
                          <input
                            className="StockItemsEntradas"
                            placeholder="Nombre del artículo"
                            name="name"
                            value={tempItem.name}
                            onChange={handleChange}
                          />
                        </td>
                        <td className="stock-grupos-td ">
                          <input
                            className="StockItemsEntradas StockItemsEntradasCentrado"
                            type="number"
                            placeholder="Cantidad"
                            name="quantity"
                            value={tempItem.quantity}
                            onChange={handleChange}
                          />
                        </td>
                        <td className="stock-grupos-td">
                          <input
                            className="StockItemsEntradas StockItemsEntradasCentrado"
                            type="date"
                            name="expirationDate"
                            value={tempItem.expirationDate}
                            onChange={handleChange}
                          />
                        </td>
                        <td className="stock-grupos-td ">
                          <button
                            className="custom-button"
                            onClick={handleSave}
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              width="24"
                              height="24"
                              viewBox="0 0 24 24"
                              fill="none"
                              stroke="currentColor"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              className="custom-icon"
                            >
                              <path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
                              <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"></path>
                              <path d="M7 3v4a1 1 0 0 0 1 1h7"></path>
                            </svg>
                          </button>

                          <button
                            className="custom-button"
                            onClick={handleCancelEdit}
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              width="24"
                              height="24"
                              viewBox="0 0 24 24"
                              fill="none"
                              stroke="currentColor"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              className="custom-icon"
                            >
                              <path d="M18 6 6 18"></path>
                              <path d="M6 6l12 12"></path>
                            </svg>
                          </button>
                        </td>
                      </>
                    ) : (
                      <>
                        <td className="stock-grupos-td">{item.name}</td>
                        <td className="stock-grupos-td">{item.quantity}</td>
                        <td className="stock-grupos-td">
                          {item.expirationDate}
                        </td>
                        <td className="stock-grupos-td">
                          <button
                            className="custom-button"
                            onClick={() => handleEdit(item)}
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              width="24"
                              height="24"
                              viewBox="0 0 24 24"
                              fill="none"
                              stroke="currentColor"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              className="custom-icon"
                            >
                              <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                              <path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"></path>
                            </svg>
                          </button>
                          <button
                            className="custom-button"
                            onClick={() => handleDelete(item.id)}
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              width="24"
                              height="24"
                              viewBox="0 0 24 24"
                              fill="none"
                              stroke="currentColor"
                              stroke-width="2"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              className="custom-icon"
                            >
                              <path d="M3 6h18"></path>
                              <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                              <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                              <line x1="10" x2="10" y1="11" y2="17"></line>
                              <line x1="14" x2="14" y1="11" y2="17"></line>
                            </svg>
                          </button>
                        </td>
                      </>
                    )}
                  </tr>
                ))}
                {/* Renderizar el formulario solo si showForm es true */}
                {showForm && (
                  <tr className="stock-grupos-tr">
                    <td className="stock-grupos-td">
                      <input
                        className="StockItemsEntradas"
                        placeholder="Nombre del artículo"
                        name="name"
                        value={tempItem.name}
                        onChange={handleChange}
                      />
                    </td>
                    <td className="stock-grupos-td">
                      <input
                        className="StockItemsEntradas"
                        type="number"
                        placeholder="Cantidad"
                        name="quantity"
                        value={tempItem.quantity}
                        onChange={handleChange}
                      />
                    </td>
                    <td className="stock-grupos-td">
                      <input
                        className="StockItemsEntradas"
                        type="date"
                        name="expirationDate"
                        value={tempItem.expirationDate}
                        onChange={handleChange}
                      />
                    </td>
                    <td className="stock-grupos-td">
                      <button
                        className="custom-button"
                        onClick={handleAddNewItem}
                      >
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          width="24"
                          height="24"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="2"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          className="custom-icon"
                        >
                          <path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
                          <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"></path>
                          <path d="M7 3v4a1 1 0 0 0 1 1h7"></path>
                        </svg>
                      </button>

                      <button className="custom-button" onClick={handleCancel}>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          width="24"
                          height="24"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="2"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          className="custom-icon"
                        >
                          <path d="M18 6 6 18"></path>
                          <path d="M6 6l12 12"></path>
                        </svg>
                      </button>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
            <button className="StockItemBoton" onClick={toggleFormVisibility}>
              {showForm ? "Cancelar" : "Agregar Nuevo Artículo"}
            </button>
          </div>
        </motion.div>
      )}
    </div>
  );
}
