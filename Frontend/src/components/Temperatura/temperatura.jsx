import "./temperatura.css";
import Grafico from "./Grafico/grafico.jsx";
import Indicadores from "./Indicadores/indicadores.jsx";
import Controladores from "./Controladores/controladores.jsx";
import Dia from "../../data/temperaturaDIA.ts";
import Semana from "../../data/temperaturaSEMANA.ts";
import React, { useState, useEffect } from "react";
import axios from "axios";
import { motion, AnimatePresence } from "framer-motion";

function Temperatura() {
  //!Consulta API

  const [temperaturas, setTemperaturas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [mostrarVariables, setMostrarVariables] = useState(false);
  const [promedio, setPromedio] = useState(0);
  const [ultimoValor, setUltimoValor] = useState(0);
  const [minValor, setMinValor] = useState(0);
  const [maxValor, setMaxValor] = useState(0);
  useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await axios.get(
          "http://localhost/ProyectoFinal/Backend/temperatura/seleccionar.php"
        );
        const data = response.data;
        setTemperaturas(data);

        if (data.length > 0) {
          const suma = data.reduce(
            (total, item) => total + parseFloat(item.temperatura),
            0
          );
          const promedio = suma / data.length;
          setPromedio(promedio);

          const ultimoValor = data[data.length - 1]?.temperatura || 0;
          setUltimoValor(ultimoValor);

          const minValor = Math.min(
            ...data.map((item) => parseFloat(item.temperatura))
          );
          setMinValor(minValor);

          const maxValor = Math.max(
            ...data.map((item) => parseFloat(item.temperatura))
          );
          setMaxValor(maxValor);
        }

        setLoading(false);
      } catch (err) {
        setError(err.message);
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const [isOn, setIsOn] = useState(false);

  const toggleSwitch = () => setIsOn(!isOn);
  const spring = {
    type: "spring",
    stiffness: 700,
    damping: 30,
  };

  const [VariableAmarilla, setVariableAmarilla] = useState(6); // Valor inicial de la constante
  const manejarCambioAmarillo = (nuevoValorAmarillo) => {
    setVariableAmarilla(nuevoValorAmarillo);
  };
  const [VariableRoja, setVariableRoja] = useState(8); // Valor inicial de la constante
  const manejarCambioRojo = (nuevoValorRojo) => {
    setVariableRoja(nuevoValorRojo);
  };

  return (
    <>
      <div className="TemperaturaTotal">
        <div className="GraficoTotal">
          <div className="GraficoSolo">
            <Grafico
              datos={temperaturas}
              mostrarVariable={isOn}
              variableAmarrilla={VariableAmarilla}
              variableRoja={VariableRoja}
            ></Grafico>
          </div>
        </div>

        <div className="ControladoresIndicadoresTotal">
            <div className="ControladoresIndicadoresSolos">
            <Indicadores
              ultimoValor={ultimoValor}
              promedio={promedio.toFixed(2)}
              minValor={minValor}
              maxValor={maxValor}
            ></Indicadores>
            </div>
            <div className="ControladoresActivar">
              <span className="ControladoresActivarTexto">Controladores:</span>
              <div
                className="ControladoresActivarSwitch"
                data-isOn={isOn}
                onClick={toggleSwitch}
              >
                <motion.div
                  className="ControladoresActivarHandle"
                  layout
                  transition={spring}
                />
              </div>
            </div>
          <div className="Controladoressolos">
            <Controladores
              isVisible={isOn}
              ValorAmarillo={VariableAmarilla}
              CambiarAmarilla={manejarCambioAmarillo}
              ValorRojo={VariableRoja}
              CambiarRojo={manejarCambioRojo}
            ></Controladores>
          </div>
        </div>
      </div>
    </>
  );
}

export default Temperatura;
