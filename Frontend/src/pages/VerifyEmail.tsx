import { useEffect, useRef, useState } from "react";
import { authService } from "../services/auth";
import "./AccountPage.css";

type Estado = "cargando" | "ok" | "error";

export default function VerifyEmail() {
  const [estado, setEstado] = useState<Estado>("cargando");
  const [mensaje, setMensaje] = useState("Verificando tu correo electrónico...");
  const yaVerifico = useRef(false);

  useEffect(() => {
    // Evita la doble ejecucion del efecto en React StrictMode (dev).
    if (yaVerifico.current) return;
    yaVerifico.current = true;

    const token = new URLSearchParams(window.location.search).get("token") || "";
    if (!token) {
      setEstado("error");
      setMensaje("El enlace no es válido: falta el token de verificación.");
      return;
    }

    authService
      .verifyEmail(token)
      .then(() => {
        setEstado("ok");
        setMensaje("¡Tu correo fue verificado correctamente! Ya podés iniciar sesión.");
      })
      .catch((e: unknown) => {
        setEstado("error");
        setMensaje(
          e instanceof Error
            ? e.message
            : "No se pudo verificar el correo. El enlace puede haber expirado."
        );
      });
  }, []);

  return (
    <div className="AccountPage">
      <div className="AccountCard">
        <div className="AccountLogo">TEMP SEGURA</div>
        <h1 className={`AccountTitulo ${estado}`}>
          {estado === "cargando"
            ? "Verificando..."
            : estado === "ok"
            ? "Correo verificado"
            : "No se pudo verificar"}
        </h1>
        <p className="AccountMensaje">{mensaje}</p>
        {estado !== "cargando" && (
          <button className="AccountBtn" type="button" onClick={() => (window.location.href = "/")}>
            Ir a iniciar sesión
          </button>
        )}
      </div>
    </div>
  );
}
