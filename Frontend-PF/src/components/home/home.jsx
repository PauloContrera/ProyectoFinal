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
        <div className="menuLogoTotal">
          <div className="menuLogoSoloLogo">
            <img src="src\assets\Logo\Logo Verde y Blanco.png" alt="" />
          </div>
          <div className="menuLogoSoloNombre">
            <img src="src\assets\Logo\Nombre.png" alt="" />
          </div>
        </div>
        <div className="menuConfiguracionesTotal"></div>
      </div>

      <Temperatura></Temperatura>
    </>
  );
}

export default Home;
