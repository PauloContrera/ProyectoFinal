import "./home.css";
import Temperatura from "../Temperatura/temperatura.jsx";

function Home() {
  return (
    <>
      <div className="menuTotal">
        <div className="menuDesplejableTotal">
          <svg focusable="false" viewBox="0 0 24 24">
            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"></path>
          </svg>
        </div>
        <div className="menuLogoHorizaontalTotal">
          <div className="menuLogoSoloTotal">
              <img className="menuLogoSolo" src="src\assets\Logo\Logo Verde y Blanco.png" alt="" />
              <img className="menuTextoSolo" src="src\assets\Logo\Nombre.png" alt="" />
          </div>
          <div className="menuConfiguracionesTotal">
          <div className="menuConfiguracionesConfiguraciones">
              <img src="src\assets\icons\configuraciones.svg" alt="" />
            </div>
            <div className="menuConfiguracionesUsuarios">
              <img src="src\assets\FOTO.jpeg" alt="" />
            </div>
          
          </div>

        </div>
        <div className="menuConfiguracionesVerticalTotal">
          <img className="menuConfiguracionesVerticalIconos" src="src\assets\icons\termometro.svg" alt=""/>
          <img className="menuConfiguracionesVerticalIconos" src="src\assets\icons\inventario.svg" alt=""/>
        </div>
        <div className="menuContenido">
        <Temperatura></Temperatura>

        </div>
      </div>
    </>
  );
}

export default Home;
