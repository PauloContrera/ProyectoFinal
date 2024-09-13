import "./indicadores.css";

interface IndicadoresProps {
  ultimoValor: number;
  ubicacion: string;
  minValor: number;
  maxValor: number;
}

function Indicadores( {ultimoValor, ubicacion, minValor, maxValor}:IndicadoresProps) {
  return (
    <>
      <div className="ControladorIndicadoresTotales">
        <div className="ControladorIndicadoresSolos">
          <h2 className="ControladorIndicadoresSolosTexto" >Último valor: <span> {ultimoValor}°C</span></h2>
        </div>
        <div className="ControladorIndicadoresSolos">
          <h2 className="ControladorIndicadoresSolosTexto">Ubicación: <span> {ubicacion}  </span></h2>
        </div>

        <div className="ControladoresIndicadoresHorizontales">
          <div className="ControladorIndicadoresSolos">
            <h2 className="ControladorIndicadoresSolosTexto">T° min: <span> {minValor}°C  </span></h2>
          </div>
          <div className="ControladorIndicadoresSolos">
            <h2 className="ControladorIndicadoresSolosTexto">T° max: <span> {maxValor}°C  </span></h2>
          </div>
        </div>
      </div>
    </>
  );
}

export default Indicadores;
