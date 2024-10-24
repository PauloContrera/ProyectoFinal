import { useState } from "react";
import { motion } from "framer-motion";
import "./Formulario-Login.css"; // Aquí puedes añadir tus propios estilos

const FormularioLogin = () => {
  const [isLogin, setIsLogin] = useState(true);

  const toggleForm = () => {
    setIsLogin(!isLogin);
  };

  return (
    <div className="LogearteTotal">
      <div className="LogearteHeader">
        <h2>{isLogin ? "Iniciar sesión" : "Registrarse"}</h2>
        <motion.button
          className="LogearteHeaderBoton"
          onClick={toggleForm}
          whileHover={{ scale: 1.1 }}
          whileTap={{ scale: 0.9 }}
        >
          {isLogin
            ? "Nuevo usuario? Regístrate"
            : "Ya tienes cuenta? Inicia sesión"}
        </motion.button>
      </div>
      <motion.div
        className="LogearteFormulario"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
      >
        <form>
          {isLogin ? (
            <>
              <div className="LogearteFormularioGrupo">
                <label>Email</label>
                <input type="email" required />
              </div>
              <div className="LogearteFormularioGrupo">
                <label>Contraseña</label>
                <input type="password" required />
              </div>
            </>
          ) : (
            <>
              <div className="LogearteFormularioGrupo">
                <label>Nombre de usuario</label>
                <input type="text" required />
              </div>
              <div className="LogearteFormularioGrupo">
                <label>Email</label>
                <input type="email" required />
              </div>
              <div className="LogearteFormularioGrupo">
                <label>Contraseña</label>
                <input type="password" required />
              </div>
              <div className="LogearteFormularioGrupo">
                <label>Número de teléfono</label>
                <input type="tel" required />
              </div>
            </>
          )}
          <button className="LogearteFormularioboton" type="submit">
            {isLogin ? "Iniciar sesión" : "Registrarse"}
          </button>
        </form>
      </motion.div>
    </div>
  );
};
export default FormularioLogin;
