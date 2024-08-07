import "./home.css";
import Temperatura from "../Temperatura/temperatura.jsx";
import Stocks from "../Stocks/stocks.jsx";
import * as React from "react";
import { useState } from "react";
import { MenuDesplejable } from "./../Home/Menu/MenuDesplejable.jsx";

function Home() {
  const [selectedComponent, setSelectedComponent] = useState("Temperatura");
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const componentsMap = {
    Temperatura: <Temperatura />,
    Stocks: <Stocks />,
    // Agrega más componentes aquí según sea necesario
  };

  const handleComponentClick = (componentName) => {
    setSelectedComponent(componentName);
  };

  const toggleMenu = () => {
    setIsMenuOpen((prevState) => !prevState);
  };

  return (
    <>
      <div className={`menuTotal ${isMenuOpen ? "open" : ""}`}>
        <div className="menuHorizaontalTotal">
          <div className="menuDesplejableTotal">
            <MenuDesplejable isOpen={isMenuOpen} toggleMenu={toggleMenu} />
          </div>
          <div className="menuLogoHorizaontalTotal">
            <div className="menuLogoSoloTotal">
              <img
                className="menuLogoSolo"
                src="src/assets/Logo/Logo Verde y Blanco.png"
                alt=""
              />
              <img
                className="menuTextoSolo"
                src="src/assets/Logo/Nombre.png"
                alt=""
              />
            </div>
            <div className="menuConfiguracionesTotal">
              <div className="menuConfiguracionesConfiguraciones">
                <img src="src/assets/icons/configuraciones.svg" alt="" />
              </div>
              <div className="menuConfiguracionesUsuarios">
                <img src="src/assets/FOTO.jpeg" alt="" />
              </div>
            </div>
          </div>
        </div>
        <div className="menuVerticalTotal">
          <div
            className={`menuConfiguracionesVerticalTotal ${
              isMenuOpen ? "open" : "close"
            }`}
          >
            <div
              className={`menuConfiguracionesVerticalSolo ${selectedComponent === "Temperatura" ? "selected" : ""}`}
              onClick={() => handleComponentClick("Temperatura")}
            >
              <img
                className="menuConfiguracionesVerticalSoloImg"
                src="src/assets/icons/termometro.svg"
                alt="Termómetro"
              />
              {isMenuOpen && <h3 
              className={`menuConfiguracionesVerticalSoloTexto ${
                isMenuOpen ? "open" : "close"
              }`}>Temperatura</h3>}
            </div>
            <div
              className={`menuConfiguracionesVerticalSolo ${selectedComponent === "Stocks" ? "selected" : ""}`}
              onClick={() => handleComponentClick("Stocks")}
            >
              <img
                className="menuConfiguracionesVerticalSoloImg"
                src="src/assets/icons/inventario.svg"
                alt="Inventario"
              />
              {isMenuOpen && <h3 
              className={`menuConfiguracionesVerticalSoloTexto ${
                isMenuOpen ? "open" : "close"
              }`}>Stock</h3>}
            </div>
            {/* Agrega más iconos y manejadores de clic aquí según sea necesario */}
          </div>
          <div className="menuContenido">
            {componentsMap[selectedComponent]}
          </div>
        </div>
      </div>
    </>
  );
}

export default Home;
