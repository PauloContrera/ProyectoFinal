import { useState } from 'react';
import './MenuVertical.css';
import ThemeSwitcher from './CambioTema/CambioTema';

function MenuVertical({ isDarkMode, toggleTheme, colorSVG }: { isDarkMode: boolean; toggleTheme: () => void; colorSVG: string }) {
  const [menuAbierto, setMenuAbierto] = useState(false);

  const toggleMenu = () => {
    setMenuAbierto(!menuAbierto);
  };


  const animacionRotacion = menuAbierto ? 'Rotacion-positiva' : 'Rotacion-negativa';

  return (
    <>
      <div className="menuLogoSoloTotal">
        <img className="menuLogoSolo" src="src/assets/Logo/Logo Verde y Blanco.png" alt="Logo" />
        <h1 className="menuTextoSolo">MedixSecure</h1>
      </div>
      <div className="menuConfiguracionesTotal">
        <button
          className={`menuConfiguracionesConfiguraciones ${animacionRotacion}`}
          onClick={toggleMenu}
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
            className="menuConfiguracionesConfiguracionesSVG"
            data-id="270"
          >
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
        </button>
        {menuAbierto && (
          <ThemeSwitcher isDarkMode={isDarkMode} toggleTheme={toggleTheme} colorSVG={colorSVG}/>
        )}
                <button className="menuConfiguracionesConfiguraciones">
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
            className="menuConfiguracionesConfiguracionesSVG"
            data-id="280"
          >
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </button>
      </div>
    </>
  );
}

export default MenuVertical;
