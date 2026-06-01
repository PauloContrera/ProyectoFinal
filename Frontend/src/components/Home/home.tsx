import "./home.css";
import TemperaturaTotal from "../Temperatura/Temperatura-Total";
// import FormularioLogin from "../Login/Formulario-Login";
import Documentacion from "../Document/documentacion";
import StockTotal from "../Stocks/StockTotal";
import BackendData from "../BackendData/BackendData";
import AdminPanel from "../Admin/AdminPanel";
import VisitorView from "../Visitor/VisitorView";
import { useState, useEffect, type ReactNode } from "react";
import { MenuDesplejable } from "./Menu/MenuDesplejable";
import MenuVertical from "./Menu/MenuVertical";
import { useAuth } from "../../hooks/useAuth";


function Home({ isDarkMode, isDemoMode, onLanding,toggleModal, toggleTheme, colorSVG }: { isDarkMode: boolean; isDemoMode: boolean; onLanding: () => void; toggleModal: () => void; toggleTheme: () => void; colorSVG: string }) {
  const { user } = useAuth();
  const [selectedComponent, setSelectedComponent] =
    useState<string>("Temperatura");
  const [isMenuOpen, setIsMenuOpen] = useState<boolean>(false);
  const [isMobile, setIsMobile] = useState<boolean>(window.innerWidth < 600);
  const canUseAdmin = user?.role === "admin" || user?.role === "superadmin";
  const isVisitor = user?.role === "visitor";

  // Escuchar cambios en el tamaño de la ventana
  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth < 600);
    };

    window.addEventListener("resize", handleResize);

    return () => window.removeEventListener("resize", handleResize);
  }, []);

  useEffect(() => {
    if (selectedComponent === "Administracion" && !canUseAdmin) {
      setSelectedComponent("Temperatura");
    }
    if (selectedComponent === "Visitante" && !isVisitor) {
      setSelectedComponent("Temperatura");
    }
  }, [canUseAdmin, isVisitor, selectedComponent]);

  const componentsMap: Record<string, ReactNode> = {
    Temperatura: <TemperaturaTotal useDemoData={isDemoMode} />,
    Stocks: <StockTotal useDemoData={isDemoMode} />,
    Backend: <BackendData />,
    Documentacion: <Documentacion />,
    ...(canUseAdmin ? { Administracion: <AdminPanel /> } : {}),
    ...(isVisitor ? { Visitante: <VisitorView /> } : {}),
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
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
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
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
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
              {canUseAdmin && (
                <div
                  className={`menuConfiguracionesVerticalSolo ${
                    selectedComponent === "Administracion" ? "selected" : ""
                  }`}
                  onClick={() => handleComponentClick("Administracion")}
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="menuConfiguracionesVerticalSoloSVG"
                  >
                    <path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4Z"></path>
                    <path d="M9 12l2 2 4-4"></path>
                  </svg>
                  {isMenuOpen && <h3 className="menuConfiguracionesVerticalSoloDescrip">Admin</h3>}
                </div>
              )}
              {isVisitor && (
                <div
                  className={`menuConfiguracionesVerticalSolo ${
                    selectedComponent === "Visitante" ? "selected" : ""
                  }`}
                  onClick={() => handleComponentClick("Visitante")}
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="menuConfiguracionesVerticalSoloSVG"
                  >
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  {isMenuOpen && <h3 className="menuConfiguracionesVerticalSoloDescrip">Visitante</h3>}
                </div>
              )}
              <div
                className={`menuConfiguracionesVerticalSolo ${
                  selectedComponent === "Backend" ? "selected" : ""
                }`}
                onClick={() => handleComponentClick("Backend")}
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  className="menuConfiguracionesVerticalSoloSVG"
                >
                  <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                  <path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"></path>
                  <path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"></path>
                </svg>
                {isMenuOpen && <h3 className="menuConfiguracionesVerticalSoloDescrip">Backend</h3>}
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
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
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
