import "./indicadores.css";

function Indicadores( {ultimoValor, promedio, minValor, maxValor}) {

  return (
    <>
      <div className="ControladorIndicadoresTotales">
        <div className="ControladorIndicadoresSolos">
          <h2 className="ControladorIndicadoresSolosTexto" >Último valor: {ultimoValor}</h2>
        </div>
        <div className="ControladorIndicadoresSolos">
          <h2 className="ControladorIndicadoresSolosTexto">Promedio: {promedio}</h2>
        </div>

        <div className="ControladoresIndicadoresHorizontales">
          <div className="ControladorIndicadoresSolos">
            <h2 className="ControladorIndicadoresSolosTexto">T° min: {minValor}</h2>
          </div>
          <div className="ControladorIndicadoresSolos">
            <h2 className="ControladorIndicadoresSolosTexto">T° max: {maxValor}</h2>
          </div>
        </div>
      </div>
    </>
  );
}

export default Indicadores;
