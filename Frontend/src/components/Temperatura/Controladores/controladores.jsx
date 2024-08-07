import "./controladores.css";
import { motion, AnimatePresence } from "framer-motion";

function Controladores({ isVisible, ValorAmarillo, CambiarAmarilla, ValorRojo, CambiarRojo }) {






  const SumarVariableAmarilla = () => {
    CambiarAmarilla((prevValor) => parseFloat((prevValor + 0.1).toFixed(1)));
  };
  
  const RestarVariableAmarilla = () => {
    CambiarAmarilla((prevValor) => parseFloat((prevValor - 0.1).toFixed(1)));
  };
  
  const SumarVariableRoja = () => {
    CambiarRojo((prevValor) => parseFloat((prevValor + 0.1).toFixed(1)));
  };
  
  const RestarVariableRoja = () => {
    CambiarRojo((prevValor) => parseFloat((prevValor - 0.1).toFixed(1)));
  };

 
  return (
    <>
      <AnimatePresence>
        {isVisible && (
          <motion.div
            initial={{y: 50,  opacity: 0 }}
            animate={{y: 0,  opacity: 1 }}
            exit={{y: 50,  opacity: 0 }}
            transition={{
              duration: 0.3,
            }}
          >
            <div className="ControladoresTotal">
              <div className="ControladoresSoloTotal">
                <h3 className="ControladoresSolotitulo">Alerta Amarilla</h3>
                <div className="ControladoresSolo">
                  <button
                    className="ControladoresSolobutton ControladoresSolorestar"
                    onClick={() => RestarVariableAmarilla()}
                  >
                    ⬇
                  </button>
                  <button className="ControladoresSoloTexto">
                  {ValorAmarillo}
                  </button>
                  <button
                    className="ControladoresSolobutton ControladoresSolosumar"
                    onClick={() => SumarVariableAmarilla()}
                  >
                    ⬆
                  </button>
                </div>
              </div>
              <div className="ControladoresSoloTotal">
                <h3 className="ControladoresSolotitulo">Alerta Roja</h3>
                <div className="ControladoresSolo">
                  <button
                    className="ControladoresSolobutton ControladoresSolorestar"
                    onClick={() => RestarVariableRoja()}
                  >
                    ⬇
                  </button>
                  <button className="ControladoresSoloTexto">
                    {ValorRojo}
                  </button>
                  <button
                    className="ControladoresSolobutton ControladoresSolosumar"
                    onClick={() => SumarVariableRoja()}
                  >
                    ⬆
                  </button>
                </div>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}

export default Controladores;
