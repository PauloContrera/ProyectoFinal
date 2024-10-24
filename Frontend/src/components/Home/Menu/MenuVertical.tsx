import { useState } from 'react';
import './MenuVertical.css';
import ThemeSwitcher from './CambioTema/CambioTema';
import LogoBlanco from 'src/assets/Logos/TBlanca.svg';
import LogoNegro from 'src/assets/Logos/TNegra.svg';
import TempSeguraBlanco from 'src/assets/Logos/SeguraBlanca.svg';
import TempSeguraNegro from 'src/assets/Logos/SeguraNegro.svg';



function MenuVertical({ isDarkMode, toggleTheme, toggleModal, onLanding , colorSVG }: { isDarkMode: boolean;   toggleTheme: () => void; toggleModal: () => void; onLanding: () => void; colorSVG: string }) {
  const [menuAbierto, setMenuAbierto] = useState(false);

  const toggleMenu = () => {
    setMenuAbierto(!menuAbierto);
  };


  const animacionRotacion = menuAbierto ? 'Rotacion-positiva' : 'Rotacion-negativa';
console.log(toggleModal);


  const handleLogoClick = () => {
    onLanding(); 
  };

  return (
    <>
   
      <div className='Menu-Logo-Total' onClick={handleLogoClick}>
      <a
          className="Menu-Logo-Solo"
          aria-label="Ir a la página de inicio"
        >
          <img 
  src={isDarkMode ? LogoBlanco : LogoNegro} 
  alt="Logo de Temp Segura" 
/>
<img 
  src={isDarkMode ? TempSeguraBlanco : TempSeguraNegro} 
  className="Menu-Logo-Solo-text"
  alt="Logo de Temp Segura" 
/>
          {/* <div className="Menu-Logo-Solo-text">
            <span className="Menu-Logo-Solo-text-span">Temp</span>
            <span className="Menu-Logo-Solo-text-span">Segura</span>
          </div> */}
        </a>
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
                {/* <button className="menuConfiguracionesConfiguraciones" onClick={toggleModal}>
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


        </button> */}
        <div>


    </div>
      </div>
    </>
  );
}

export default MenuVertical;
