import React, { useEffect, useState } from "react";
import { Save } from "lucide-react";
import { api } from "../../../services/api";
import { useAuth } from "../../../hooks/useAuth";
import "./config.css";

interface ConfigProps {
  onClose: () => void;
  shouldCloseOnSave?: boolean;
}

const readError = (error: unknown, fallback: string) =>
  error instanceof Error ? error.message : fallback;

const Config: React.FC<ConfigProps> = ({ onClose, shouldCloseOnSave = true }) => {
  const { user } = useAuth();
  const [name, setName] = useState("");
  const [username, setUsername] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    if (!user) return;
    setName(user.name || "");
    setUsername(user.username || "");
    setEmail(user.email || "");
    setPhone(user.phone || "");
  }, [user]);

  const handleSave = async () => {
    if (!user) return;

    setIsSaving(true);
    setError("");
    setMessage("");

    try {
      if (name !== user.name || email !== user.email || phone !== (user.phone || "")) {
        await api.put(`/users/${user.id}`, {
          name: name.trim(),
          email: email.trim(),
          phone: phone.trim(),
        });
      }

      if (username !== user.username) {
        await api.put(`/users/${user.id}/change-username`, {
          new_username: username.trim(),
        });
      }

      if (currentPassword || newPassword) {
        await api.put(`/users/${user.id}/change-password`, {
          current_password: currentPassword,
          new_password: newPassword,
        });
        setCurrentPassword("");
        setNewPassword("");
      }

      const updatedUser = {
        ...user,
        name: name.trim(),
        username: username.trim(),
        email: email.trim(),
        phone: phone.trim(),
      };
      localStorage.setItem("user", JSON.stringify(updatedUser));
      setMessage("Configuracion guardada");

      if (shouldCloseOnSave) {
        onClose();
      }
    } catch (err: unknown) {
      setError(readError(err, "No se pudo guardar la configuracion"));
    } finally {
      setIsSaving(false);
    }
  };

  if (!user) {
    return (
      <div className="ConfigPanel">
        <p className="ConfigMuted">No hay sesion activa.</p>
      </div>
    );
  }

  return (
    <div className="ConfigPanel">
      <div className="ConfigHeader">
        <div>
          <h2>Configuracion de usuario</h2>
          <p>{user.role} | datos personales y credenciales</p>
        </div>
      </div>

      <form className="ConfigForm">
        <label>
          Nombre
          <input type="text" value={name} onChange={(event) => setName(event.target.value)} />
        </label>

        <label>
          Username
          <input type="text" value={username} onChange={(event) => setUsername(event.target.value)} />
        </label>

        <label>
          Email
          <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} />
        </label>

        <label>
          Telefono SMS
          <input type="tel" value={phone} onChange={(event) => setPhone(event.target.value)} />
        </label>

        <div className="ConfigDivider" />

        <label>
          Contrasena actual
          <input type="password" value={currentPassword} onChange={(event) => setCurrentPassword(event.target.value)} autoComplete="current-password" />
        </label>

        <label>
          Nueva contrasena
          <input type="password" value={newPassword} onChange={(event) => setNewPassword(event.target.value)} autoComplete="new-password" />
        </label>

        {message && <p className="ConfigOk">{message}</p>}
        {error && <p className="ConfigError">{error}</p>}

        <button type="button" className="ConfigSave" onClick={handleSave} disabled={isSaving}>
          <Save size={16} />
          {isSaving ? "Guardando" : "Guardar"}
        </button>
      </form>
    </div>
  );
};

export default Config;
