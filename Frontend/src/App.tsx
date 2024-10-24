import "./App.css";
import Home from "./components/Home/home.tsx";
import Config from "./components/Home/Config/config.tsx";
import Landing from "./components/LadingPage/landing.tsx";
import ModalTerm from "./components/LadingPage/ModalTerm/ModalTerm.tsx";
import { useState, useEffect } from "react";
import Modal from "./components/Modal/modal.tsx";
import TermsAndConditions from "./components/LadingPage/Terminos/terminos.tsx";

function App() {
  
  const getInitialTheme = () => {
    const savedTheme = localStorage.getItem('isDarkMode');
    if (savedTheme !== null) {
      return JSON.parse(savedTheme);
    }
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  };

  const [isDarkMode, setIsDarkMode] = useState(getInitialTheme());
  const [colorSVG, setColorSVG] = useState(isDarkMode ? 'DarkModeSVG' : 'WhiteModeSVG');

  const toggleTheme = () => {
    const newTheme = !isDarkMode;
    setIsDarkMode(newTheme);
    localStorage.setItem('isDarkMode', JSON.stringify(newTheme));
    setColorSVG(newTheme ? 'DarkModeSVG' : 'WhiteModeSVG');
  };

  const [isModalOpen, setIsModalOpen] = useState(false);
  const closeModal = () => setIsModalOpen(prev => !prev);
  const toggleModal = () => setIsModalOpen(prev => !prev);

  const [isModalOpenTermino, setIsModalOpenTermino] = useState(false);
  const closeModalTermino = () => setIsModalOpenTermino(prev => !prev);
  const toggleModalTermino = () => setIsModalOpenTermino(prev => !prev);

  // Estado para controlar la vista
  const [showHome, setShowHome] = useState(false);

  // Funciones para manejar el acceso a Home
  const handleLogin = () => {
    // Lógica futura para manejar la solicitud de login
    setShowHome(true);
  };

  const handleDemo = () => {
    // Simulación de cargar la demo (aquí puedes cargar datos mockeados en el futuro)
    setShowHome(true);
  };
  
  const handleLanding = () => {
    setShowHome(false);
  };

  useEffect(() => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handleChange = (e: MediaQueryListEvent) => {
      const prefersDarkMode = e.matches;
      setIsDarkMode(prefersDarkMode);
      setColorSVG(prefersDarkMode ? 'DarkModeSVG' : 'WhiteModeSVG');
      localStorage.setItem('isDarkMode', JSON.stringify(prefersDarkMode));
    };

    mediaQuery.addEventListener('change', handleChange);

    return () => mediaQuery.removeEventListener('change', handleChange);
  }, []);

  // !colores
  useEffect(() => {
    if (isDarkMode) {
      // darkmode
      document.documentElement.style.setProperty('--ColorFondo1', '#111827');
      document.documentElement.style.setProperty('--ColorFondo2', '#1f2937');
      document.documentElement.style.setProperty('--ColorFondo3', '#2563eb');
      document.documentElement.style.setProperty('--ColorLogo', '#ffffff');
      document.documentElement.style.setProperty('--ColorFondo3Hover', '#1d4ed8');
      document.documentElement.style.setProperty('--ColorFondo4', '#1f2937');
      document.documentElement.style.setProperty('--ColorFondo5', '#111827');
      document.documentElement.style.setProperty('--LetrasColor1', '#ffffff');
      document.documentElement.style.setProperty('--LetrasColor2', '#ccd0cf');
      document.documentElement.style.setProperty('--LetrasColor3', '#f3f4f6');
      document.documentElement.style.setProperty('--LetrasColor4', '#93c5fd');
      document.documentElement.style.setProperty('--LetrasColor5', '#3b82f6');
      document.documentElement.style.setProperty('--LetrasColor6', '#9CA3AF');
      document.documentElement.style.setProperty('--BorderColor1', '#374151');
      document.documentElement.style.setProperty('--BorderColor2', '#e4e4e7');
      document.documentElement.style.setProperty('--BorderColor3', '#e4e4e780');

    
    } else {
      // whiteMode
      document.documentElement.style.setProperty('--ColorFondo1', '#F3F4F6');
      document.documentElement.style.setProperty('--ColorFondo2', '#ffffff');
      document.documentElement.style.setProperty('--ColorFondo3', '#18181b');
      document.documentElement.style.setProperty('--ColorLogo', '#111111');
      document.documentElement.style.setProperty('--ColorFondo3Hover', '#18181bea');
      document.documentElement.style.setProperty('--ColorFondo4', '#f3f4f6');
      document.documentElement.style.setProperty('--ColorFondo5', '#f9fafb');
      document.documentElement.style.setProperty('--LetrasColor1', '#09090b');
      document.documentElement.style.setProperty('--LetrasColor2', '#4b5563');
      document.documentElement.style.setProperty('--LetrasColor3', '#111827');
      document.documentElement.style.setProperty('--LetrasColor4', '#2563eb');
      document.documentElement.style.setProperty('--LetrasColor5', '#3b82f6');
      document.documentElement.style.setProperty('--LetrasColor6', '#71717A');
      document.documentElement.style.setProperty('--BorderColor1', '#e5e7eb');
      document.documentElement.style.setProperty('--BorderColor2', '#4b5563');
      document.documentElement.style.setProperty('--BorderColor3', '#4b556380');
    }
  }, [isDarkMode]);

  return (
    <>
      {!showHome ? (
        <Landing 
          onLogin={handleLogin} 
          onDemo={handleDemo} 
          toggleModal={toggleModalTermino} 
        />
      ) : (
        <>
          <Home 
            isDarkMode={isDarkMode}  
            toggleTheme={toggleTheme} 
            toggleModal={toggleModal}
            onLanding={handleLanding}
            colorSVG={colorSVG}
          />
          <Modal isOpen={isModalOpen} onClose={closeModal} title="Configuración de Usuario">
            <Config onClose={closeModal} shouldCloseOnSave={true} />
          </Modal>
        </>
      )}

      <ModalTerm isOpen={isModalOpenTermino} onClose={closeModalTermino} title="Términos y Condiciones" subtitle="Por favor, lea atentamente los siguientes términos y condiciones de uso de Temp Segura.">
        <TermsAndConditions />
      </ModalTerm>
    </>
  );
}

export default App;