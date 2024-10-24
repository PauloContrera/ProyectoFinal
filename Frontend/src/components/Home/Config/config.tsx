import React, { useState } from "react";
// import "./config.css"; 

interface ConfigProps {
  onClose: () => void;
  shouldCloseOnSave?: boolean; // Nueva prop opcional
}

const Config: React.FC<ConfigProps> = ({ onClose, shouldCloseOnSave = true }) => {
  const [name, setName] = useState("Juan Pérez");
  const [email, setEmail] = useState("juan.perez@example.com");
  const [phone, setPhone] = useState("+54 9 11 1234-5678");

  const handleSave = () => {
    // Lógica para guardar los cambios
    console.log("Cambios guardados:", { name, email, phone });

    // Cerrar el modal solo si `shouldCloseOnSave` es true
    if (shouldCloseOnSave) {
      onClose();
    }
  };

  return (
    <div className="config-container">
      <h2>Configuración de Usuario</h2>
      <p>Actualiza tu información personal y preferencias de notificación.</p>

      <form className="user-settings-form">
        <div className="form-group">
          <label htmlFor="name">Nombre</label>
          <input 
            type="text" 
            id="name" 
            value={name} 
            onChange={(e) => setName(e.target.value)} 
          />
        </div>

        <div className="form-group">
          <label htmlFor="email">Email</label>
          <input 
            type="email" 
            id="email" 
            value={email} 
            onChange={(e) => setEmail(e.target.value)} 
          />
        </div>

        <div className="form-group">
          <label htmlFor="phone">Teléfono (para alertas SMS)</label>
          <input 
            type="tel" 
            id="phone" 
            value={phone} 
            onChange={(e) => setPhone(e.target.value)} 
          />
        </div>

        <button type="button" className="save-button" onClick={handleSave}>
          Guardar Cambios
        </button>
      </form>
    </div>
  );
};

export default Config;
