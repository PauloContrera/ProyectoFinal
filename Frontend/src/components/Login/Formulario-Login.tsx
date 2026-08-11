import { useRef, useState } from "react";
import { motion } from "framer-motion";
import { useAuth } from "../../hooks/useAuth";
import { authService } from "../../services/auth";
import Input from "../common/Input";
import { LoginRequest, RegisterRequest } from "../../types";
import "./Formulario-Login.css";

interface FormErrors {
  [key: string]: string;
}

interface FormularioLoginProps {
  onSuccess?: () => void;
}

const errorMessage = (error: unknown, fallback: string) =>
  error instanceof Error ? error.message : fallback;

const FormularioLogin = ({ onSuccess }: FormularioLoginProps) => {
  const [isLogin, setIsLogin] = useState(true);
  const [forgotMode, setForgotMode] = useState(false);
  const [forgotEmail, setForgotEmail] = useState("");
  const [forgotMsg, setForgotMsg] = useState("");
  const [forgotSending, setForgotSending] = useState(false);
  const [formErrors, setFormErrors] = useState<FormErrors>({});
  const [submitError, setSubmitError] = useState<string>("");
  const { login, register, isLoading, error } = useAuth();

  const identifierRef = useRef<HTMLInputElement>(null);
  const emailRef = useRef<HTMLInputElement>(null);
  const passwordRef = useRef<HTMLInputElement>(null);
  const usernameRef = useRef<HTMLInputElement>(null);
  const nameRef = useRef<HTMLInputElement>(null);
  const phoneRef = useRef<HTMLInputElement>(null);

  const toggleForm = () => {
    setIsLogin(!isLogin);
    setFormErrors({});
    setSubmitError("");
  };

  const abrirForgot = () => {
    setForgotMode(true);
    setForgotMsg("");
    setSubmitError("");
    setFormErrors({});
  };

  const volverALogin = () => {
    setForgotMode(false);
    setForgotMsg("");
  };

  const handleForgot = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setForgotMsg("");
    const email = forgotEmail.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setForgotMsg("Ingresá un email válido.");
      return;
    }
    setForgotSending(true);
    try {
      await authService.forgotPassword(email);
      setForgotMsg(
        "Si el email está registrado, te enviamos un enlace para restablecer la contraseña."
      );
    } catch {
      setForgotMsg(
        "Si el email está registrado, te enviamos un enlace para restablecer la contraseña."
      );
    } finally {
      setForgotSending(false);
    }
  };

  const validateLogin = (): boolean => {
    const errors: FormErrors = {};
    const identifier = identifierRef.current?.value.trim() || "";
    const password = passwordRef.current?.value || "";

    if (!identifier) {
      errors.identifier = "Email o usuario es requerido";
    }

    if (!password) {
      errors.password = "Contraseña es requerida";
    } else if (password.length < 6) {
      errors.password = "Mínimo 6 caracteres";
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const validateRegister = (): boolean => {
    const errors: FormErrors = {};
    const email = emailRef.current?.value.trim() || "";
    const password = passwordRef.current?.value || "";
    const username = usernameRef.current?.value.trim() || "";
    const name = nameRef.current?.value.trim() || "";
    const phone = phoneRef.current?.value.trim() || "";

    if (!name) {
      errors.name = "Nombre es requerido";
    } else if (name.length < 2) {
      errors.name = "Mínimo 2 caracteres";
    }

    if (!username) {
      errors.username = "Usuario es requerido";
    } else if (!/^[a-zA-Z][a-zA-Z0-9_]{2,}$/.test(username)) {
      errors.username = "Mínimo 3 caracteres, comienza con letra";
    }

    if (!email) {
      errors.email = "Email es requerido";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      errors.email = "Email inválido";
    }

    if (!password) {
      errors.password = "Contraseña es requerida";
    } else if (password.length < 8) {
      errors.password = "Mínimo 8 caracteres";
    } else if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password)) {
      errors.password = "1 mayúscula, 1 minúscula, 1 número";
    }

    if (phone && !/^\+?[1-9]\d{1,14}$/.test(phone.replace(/\s/g, ""))) {
      errors.phone = "Teléfono inválido";
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setSubmitError("");

    try {
      if (isLogin) {
        if (!validateLogin()) return;

        const credentials: LoginRequest = {
          identifier: identifierRef.current?.value.trim() || "",
          password: passwordRef.current?.value || "",
        };

        await login(credentials);
        onSuccess?.();
        return;
      }

      if (!validateRegister()) return;

      const data: RegisterRequest = {
        name: nameRef.current?.value.trim() || "",
        username: usernameRef.current?.value.trim() || "",
        email: emailRef.current?.value.trim() || "",
        password: passwordRef.current?.value || "",
        phone: phoneRef.current?.value.trim() || undefined,
      };

      await register(data);
      setSubmitError("Registro creado. Revisa tu email para verificar la cuenta antes de iniciar sesión.");
      setIsLogin(true);
    } catch (err: unknown) {
      setSubmitError(errorMessage(err, "Error al procesar solicitud"));
    }
  };

  return (
    <div className="LogearteTotal">
      <div className="LogearteHeader">
        <h2>{forgotMode ? "Recuperar contraseña" : isLogin ? "Iniciar sesión" : "Registrarse"}</h2>
        <motion.button
          className="LogearteHeaderBoton"
          onClick={forgotMode ? volverALogin : toggleForm}
          whileHover={{ scale: 1.05 }}
          whileTap={{ scale: 0.95 }}
          disabled={isLoading || forgotSending}
          type="button"
        >
          {forgotMode
            ? "Volver a iniciar sesión"
            : isLogin
            ? "¿Nuevo usuario? Registrate"
            : "¿Ya tienes cuenta? Inicia sesión"}
        </motion.button>
      </div>

      <motion.div
        className="LogearteFormulario"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={{ duration: 0.3 }}
      >
        {forgotMode && (
          <form onSubmit={handleForgot}>
            <p className="LogearteForgotTexto">
              Ingresá tu email y te enviaremos un enlace para restablecer tu contraseña.
            </p>
            {forgotMsg && (
              <div className="form-error-banner">
                <span>{forgotMsg}</span>
              </div>
            )}
            <Input
              type="email"
              label="Email"
              placeholder="tu@email.com"
              value={forgotEmail}
              onChange={(e) => setForgotEmail(e.target.value)}
              disabled={forgotSending}
              required
            />
            <motion.button
              className="LogearteFormularioBoton"
              type="submit"
              disabled={forgotSending}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
            >
              {forgotSending ? "Enviando..." : "Enviar enlace"}
            </motion.button>
          </form>
        )}

        {!forgotMode && (
        <form onSubmit={handleSubmit}>
          {(submitError || error) && (
            <div className="form-error-banner">
              <span>{submitError || error}</span>
            </div>
          )}

          {isLogin ? (
            <>
              <Input
                ref={identifierRef}
                type="text"
                label="Email o usuario"
                placeholder="tu@email.com o usuario"
                error={formErrors.identifier}
                required
                disabled={isLoading}
              />

              <Input
                ref={passwordRef}
                type="password"
                label="Contraseña"
                placeholder="Tu contraseña"
                error={formErrors.password}
                helperText="Mínimo 6 caracteres"
                required
                disabled={isLoading}
              />
              <button
                type="button"
                className="LogearteForgotLink"
                onClick={abrirForgot}
                disabled={isLoading}
              >
                ¿Olvidaste tu contraseña?
              </button>
            </>
          ) : (
            <>
              <Input
                ref={nameRef}
                type="text"
                label="Nombre completo"
                placeholder="Tu nombre"
                error={formErrors.name}
                required
                disabled={isLoading}
              />

              <Input
                ref={usernameRef}
                type="text"
                label="Nombre de usuario"
                placeholder="usuario123"
                error={formErrors.username}
                helperText="3+ caracteres, comienza con letra"
                required
                disabled={isLoading}
              />

              <Input
                ref={emailRef}
                type="email"
                label="Email"
                placeholder="tu@email.com"
                error={formErrors.email}
                required
                disabled={isLoading}
              />

              <Input
                ref={passwordRef}
                type="password"
                label="Contraseña"
                placeholder="Contraseña segura"
                error={formErrors.password}
                helperText="8+ caracteres, incluir mayúscula, minúscula y número"
                required
                disabled={isLoading}
              />

              <Input
                ref={phoneRef}
                type="tel"
                label="Número de teléfono"
                placeholder="+54 9 11 1234 5678"
                error={formErrors.phone}
                helperText="Opcional"
                disabled={isLoading}
              />
            </>
          )}

          <motion.button
            className="LogearteFormularioBoton"
            type="submit"
            disabled={isLoading}
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
          >
            {isLoading ? (
              <>
                <span className="spinner"></span>
                {isLogin ? "Iniciando..." : "Registrando..."}
              </>
            ) : (
              isLogin ? "Iniciar sesión" : "Registrarse"
            )}
          </motion.button>
        </form>
        )}
      </motion.div>
    </div>
  );
};

export default FormularioLogin;
