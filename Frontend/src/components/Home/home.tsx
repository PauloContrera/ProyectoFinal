import "./home.css";
import TemperaturaTotal from "../Temperatura/Temperatura-Total";
// import FormularioLogin from "../Login/Formulario-Login";
import Documentacion from "../Document/documentacion";
import StockTotal from "../Stocks/StockTotal";
import { useState, useEffect } from "react";
import { MenuDesplejable } from "./Menu/MenuDesplejable";
import MenuVertical from "./Menu/MenuVertical";


function Home({ isDarkMode, onLanding,toggleModal, toggleTheme, colorSVG }: { isDarkMode: boolean; onLanding: () => void; toggleModal: () => void; toggleTheme: () => void; colorSVG: string }) {
  const [selectedComponent, setSelectedComponent] =
    useState<string>("Temperatura");
  const [isMenuOpen, setIsMenuOpen] = useState<boolean>(false);
  const [isMobile, setIsMobile] = useState<boolean>(window.innerWidth < 600);

  // Escuchar cambios en el tamaño de la ventana
  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth < 600);
    };

    window.addEventListener("resize", handleResize);

    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const componentsMap: any = {
    Temperatura: <TemperaturaTotal />,
    Stocks: <StockTotal />,
    Documentacion: <Documentacion />,
  };

  const handleComponentClick = (componentName: string): void => {
    setSelectedComponent(componentName);
    if (isMobile) {
      setIsMenuOpen(false); // Cierra el menú automáticamente en móvil
    }
  };

  const toggleMenu = (): void => {
    setIsMenuOpen((prevState) => !prevState);
  };

  return (
    <>
      <div className="appContainer">
        <div className="menuTotal">
          <div className="menuHorizaontalTotal">
            <div className="menuDesplejableTotal">
              <MenuDesplejable isOpen={isMenuOpen} toggleMenu={toggleMenu} />
            </div>
            <div className="menuLogoHorizaontalTotal">
              <MenuVertical isDarkMode={isDarkMode} toggleTheme={toggleTheme} onLanding={onLanding} toggleModal={toggleModal} colorSVG={colorSVG}/>
            </div>
          </div>
        </div>
        <div className={`mainContent ${isMenuOpen ? "shifted" : ""}`}>
          <div className={`menuVerticalTotal ${isMenuOpen ? "open" : ""}`}>
            <div
              className={`menuConfiguracionesVerticalTotal ${
                isMenuOpen ? "open" : "close"
              }`}
            >
              <div className="menuConfiguracionesVerticalArriba">
              <div
                className={`menuConfiguracionesVerticalSolo ${
                  selectedComponent === "Temperatura" ? "selected" : ""
                }`}
                onClick={() => handleComponentClick("Temperatura")}
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  className="menuConfiguracionesVerticalSoloSVG"
                  data-id="289"
                >
                  <path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                </svg>
                {isMenuOpen && <h3 className="menuConfiguracionesVerticalSoloDescrip">Temperatura</h3>}
              </div>
              <div
                className={`menuConfiguracionesVerticalSolo ${
                  selectedComponent === "Stocks" ? "selected" : ""
                }`}
                onClick={() => handleComponentClick("Stocks")}
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  className="menuConfiguracionesVerticalSoloSVG"
                  data-id="291"
                >
                  <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                  <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                  <path d="M10 9H8"></path>
                  <path d="M16 13H8"></path>
                  <path d="M16 17H8"></path>
                </svg>
               {isMenuOpen && <h3 className="menuConfiguracionesVerticalSoloDescrip">Stock</h3>}
              </div>
              </div>
              <div
                className={`menuConfiguracionesVerticalSolo ${
                  selectedComponent === "Documentacion" ? "selected" : ""
                }`}
                onClick={() => handleComponentClick("Documentacion")}
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  className="menuConfiguracionesVerticalSoloSVG"
                  data-id="211"
                >
                  <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                  <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                {isMenuOpen && <h3 className="menuConfiguracionesVerticalSoloDescrip">Documentacion</h3>}
              </div>
            </div>
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
