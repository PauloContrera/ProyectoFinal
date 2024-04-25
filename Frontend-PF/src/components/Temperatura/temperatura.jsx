import "./temperatura.css";
import Grafico from "./grafico/grafico.jsx";
import Valores from "./valores/valores.jsx";
import Dia from "../../data/temperaturaDIA.ts";
import Semana from "../../data/temperaturaSEMANA.ts";
import React, { useState } from "react";

function Temperatura() {
  const [VariableAmarrilla, setVariableAmarrilla] = useState(10); // Valor inicial de la constante
  const [VariableRoja, setVariableRoja] = useState(15); // Valor inicial de la constante

  const SumarVariableAmarrilla = () => {
    setVariableAmarrilla(VariableAmarrilla + 1);
  };
  const RestarVariableAmarrilla = () => {
    setVariableAmarrilla(VariableAmarrilla - 1);
  };
  const SumarVariableRoja = () => {
    setVariableRoja(VariableRoja + 1);
  };
  const RestarVariableRoja = () => {
    setVariableRoja(VariableRoja - 1);
  };

  const promedio = Dia.reduce((acc, curr) => acc + curr.temperatura, 0) / Dia.length;

  // Obtener último valor de temperatura
  const ultimoValor = Dia[Dia.length - 1].temperatura;


  return (
    <>
      <div className="TemperaturaTotal">
        <div className="GraficoSolo">
          <Grafico
            datos={Dia}
            alto={400}
            variableAmarrilla={VariableAmarrilla}
            variableRoja={VariableRoja}
          ></Grafico>
        </div>
        <div className="ControladoresTotal">
            
          <div className="ControladoresSoloTotal">
            <h3>Alerta Roja</h3>
            <div className="ControladoresSolo">
            <button className="ControladoresSolobutton ControladoresSolorestar" onClick={() => RestarVariableRoja()}>-</button>
            <p className="ControladoresSoloTexto">{VariableRoja}</p>
            <button className="ControladoresSolobutton ControladoresSolosumar" onClick={() => SumarVariableRoja()}>+</button>
          </div>
          </div>
          <div className="ControladoresSoloTotal">
          <h3>Alerta Amarilla</h3>
          <div className="ControladoresSolo">
            <button className="ControladoresSolobutton ControladoresSolorestar" onClick={() => RestarVariableAmarrilla()}>-</button>
            <div className="ControladoresSoloTexto">{VariableAmarrilla}</div>
            <button className="ControladoresSolobutton ControladoresSolosumar" onClick={() => SumarVariableAmarrilla()}>+</button>
          </div>
          </div>
          <div>
      <h2>Promedio de temperatura: {promedio.toFixed(2)}</h2>
      <h2>Último valor de temperatura: {ultimoValor}</h2>
    </div>
          
        </div>
      </div>
    </>
  );
}

export default Temperatura;
