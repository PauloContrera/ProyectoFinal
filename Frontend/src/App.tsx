import "./App.css";
import Home from "./components/Home/home.tsx";
import { useState, useEffect } from "react";

function App() {
  
  const getInitialTheme = () => {
    // Obtener el valor almacenado en localStorage, si existe
    const savedTheme = localStorage.getItem('isDarkMode');
    if (savedTheme !== null) {
      return JSON.parse(savedTheme);
    }

    // Si no está almacenado, obtener el valor del sistema operativo
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

  // Escuchar cambios en el esquema de colores del sistema operativo
  useEffect(() => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

    const handleChange = (e: MediaQueryListEvent) => {
      const prefersDarkMode = e.matches;
      setIsDarkMode(prefersDarkMode);
      setColorSVG(prefersDarkMode ? 'DarkModeSVG' : 'WhiteModeSVG');
      localStorage.setItem('isDarkMode', JSON.stringify(prefersDarkMode));
    };

    mediaQuery.addEventListener('change', handleChange);

    // Cleanup listener cuando se desmonta el componente
    return () => mediaQuery.removeEventListener('change', handleChange);
  }, []);

  useEffect(() => {
    if (isDarkMode) {
      // darkmode
      document.documentElement.style.setProperty('--ColorFondo1', '#111827');
      document.documentElement.style.setProperty('--ColorFondo2', '#1f2937');
      document.documentElement.style.setProperty('--ColorFondo3', '#18181b');
      document.documentElement.style.setProperty('--LetrasColor1', '#ffffff');
      document.documentElement.style.setProperty('--LetrasColor2', '#ccd0cf');

    
    } else {
      // whiteMode
      document.documentElement.style.setProperty('--ColorFondo1', '#F3F4F6');
      document.documentElement.style.setProperty('--ColorFondo2', '#ffffff');
      document.documentElement.style.setProperty('--ColorFondo3', '#18181b');
      document.documentElement.style.setProperty('--LetrasColor1', '#09090b');
      document.documentElement.style.setProperty('--LetrasColor2', '#4b5563');
    }
  }, [isDarkMode]);
  return (
    <>
      <Home isDarkMode={isDarkMode} toggleTheme={toggleTheme} colorSVG={colorSVG}></Home>
    </>
  );
}

export default App;
