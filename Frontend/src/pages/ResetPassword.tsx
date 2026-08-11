import { useEffect, useState } from "react";
import { authService } from "../services/auth";
import "./AccountPage.css";

type EstadoToken = "verificando" | "valido" | "invalido";

const passwordValida = (p: string) =>
  p.length >= 8 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /[0-9]/.test(p);

export default function ResetPassword() {
  const [token, setToken] = useState("");
  const [estadoToken, setEstadoToken] = useState<EstadoToken>("verificando");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [error, setError] = useState("");
  const [enviando, setEnviando] = useState(false);
  const [exito, setExito] = useState(false);

  useEffect(() => {
    const t = new URLSearchParams(window.location.search).get("token") || "";
    setToken(t);
    if (!t) {
      setEstadoToken("invalido");
      return;
    }
    authService.verifyResetToken(t).then((ok) => setEstadoToken(ok ? "valido" : "invalido"));
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");

    if (!passwordValida(password)) {
      setError("La contraseña debe tener 8+ caracteres, una mayúscula, una minúscula y un número.");
      return;
    }
    if (password !== confirm) {
      setError("Las contraseñas no coinciden.");
      return;
    }

    setEnviando(true);
    try {
      await authService.resetPassword(token, password);
      setExito(true);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : "No se pudo restablecer la contraseña.");
    } finally {
      setEnviando(false);
    }
  };

  return (
    <div className="AccountPage">
      <div className="AccountCard">
        <div className="AccountLogo">TEMP SEGURA</div>

        {estadoToken === "verificando" && (
          <>
            <h1 className="AccountTitulo cargando">Restablecer contraseña</h1>
            <p className="AccountMensaje">Validando el enlace...</p>
          </>
        )}

        {estadoToken === "invalido" && (
          <>
            <h1 className="AccountTitulo error">Enlace inválido o expirado</h1>
            <p className="AccountMensaje">
              El enlace de restablecimiento no es válido o ya expiró. Solicitá uno nuevo desde
              "¿Olvidaste tu contraseña?".
            </p>
            <button className="AccountBtn" type="button" onClick={() => (window.location.href = "/")}>
              Volver al inicio
            </button>
          </>
        )}

        {estadoToken === "valido" && !exito && (
          <>
            <h1 className="AccountTitulo">Nueva contraseña</h1>
            <p className="AccountMensaje">Ingresá tu nueva contraseña para tu cuenta.</p>
            <form className="AccountForm" onSubmit={handleSubmit}>
              <input
                className="AccountInput"
                type="password"
                placeholder="Nueva contraseña"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                disabled={enviando}
              />
              <input
                className="AccountInput"
                type="password"
                placeholder="Confirmar contraseña"
                value={confirm}
                onChange={(e) => setConfirm(e.target.value)}
                disabled={enviando}
              />
              {error && <p className="AccountError">{error}</p>}
              <button className="AccountBtn" type="submit" disabled={enviando}>
                {enviando ? "Guardando..." : "Restablecer contraseña"}
              </button>
            </form>
          </>
        )}

        {exito && (
          <>
            <h1 className="AccountTitulo ok">Contraseña actualizada</h1>
            <p className="AccountMensaje">
              Tu contraseña fue restablecida correctamente. Ya podés iniciar sesión.
            </p>
            <button className="AccountBtn" type="button" onClick={() => (window.location.href = "/")}>
              Ir a iniciar sesión
            </button>
          </>
        )}
      </div>
    </div>
  );
}
