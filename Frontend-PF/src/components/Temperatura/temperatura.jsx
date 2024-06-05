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

  const promedio =
    Dia.reduce((acc, curr) => acc + curr.temperatura, 0) / Dia.length;

  // Obtener último valor de temperatura
  const ultimoValor = Dia[Dia.length - 1].temperatura;

  const minValor = Math.min(...Dia.map((dia) => dia.temperatura));

  // Obtener el valor máximo de temperatura
  const maxValor = Math.max(...Dia.map((dia) => dia.temperatura));

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
          <div class="ControladoresIndicadoresTotales">
            <div class="ControladoresIndicadoresSolos">
              <h2>Último valor: {ultimoValor}</h2>
            </div>
            <div class="ControladoresIndicadoresSolos">
              <h2>Promedio: {promedio.toFixed(2)}</h2>
            </div>

            <div class="ControladoresIndicadoresHorizontales">
              <div class="ControladoresIndicadoresSolos">
                <h2>T° min: {minValor}</h2>
              </div>
              <div class="ControladoresIndicadoresSolos">
                <h2>T° max: {maxValor}</h2>
              </div>
            </div>
          </div>
          <div className="ControladoresSoloTotal">
            <h3 className="ControladoresSolotitulo">Alerta Roja</h3>
            <div className="ControladoresSolo">
              <button
                className="ControladoresSolobutton ControladoresSolorestar"
                onClick={() => RestarVariableRoja()}
              >
                -
              </button>
              <p className="ControladoresSoloTexto">{VariableRoja}</p>
              <button
                className="ControladoresSolobutton ControladoresSolosumar"
                onClick={() => SumarVariableRoja()}
              >
                +
              </button>
            </div>
          </div>
          <div className="ControladoresSoloTotal">
            <h3 className="ControladoresSolotitulo">Alerta Amarilla</h3>
            <div className="ControladoresSolo">
              <button
                className="ControladoresSolobutton ControladoresSolorestar"
                onClick={() => RestarVariableAmarrilla()}
              >
                -
              </button>
              <div className="ControladoresSoloTexto">{VariableAmarrilla}</div>
              <button
                className="ControladoresSolobutton ControladoresSolosumar"
                onClick={() => SumarVariableAmarrilla()}
              >
                +
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

export default Temperatura;
