import './CambioTema.css';

function ThemeSwitcher({ isDarkMode, toggleTheme, colorSVG }: { isDarkMode: boolean; toggleTheme: () => void; colorSVG: string }) {



  return (
    <div className="MenuDesplegable">
      <ul>
        <li className="DarkMode">
          <span className="ContenedoresSVG">
            <svg version="1.1" className={colorSVG} xmlns="http://www.w3.org/2000/svg">
              <path className="sunSVG"
                d="M12 3V4M12 20V21M4 12H3M6.31412 6.31412L5.5 5.5M17.6859 6.31412L18.5 5.5M6.31412 17.69L5.5 18.5001M17.6859 17.69L18.5 18.5001M21 12H20M16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12Z"
                strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </span>
          <input className="inputDarkmode" type="checkbox" id="darkmode-toggle" checked={isDarkMode} onChange={toggleTheme} />
          <label className="labelDarkmode" htmlFor="darkmode-toggle" />
          <span className="ContenedoresSVG">
            <svg version="1.1" className={colorSVG} xmlns="http://www.w3.org/2000/svg">
              <path className="moonSVG"
                d="M3.32031 11.6835C3.32031 16.6541 7.34975 20.6835 12.3203 20.6835C16.1075 20.6835 19.3483 18.3443 20.6768 15.032C19.6402 15.4486 18.5059 15.6834 17.3203 15.6834C12.3497 15.6834 8.32031 11.654 8.32031 6.68342C8.32031 5.50338 8.55165 4.36259 8.96453 3.32996C5.65605 4.66028 3.32031 7.89912 3.32031 11.6835Z"
                strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </span>
        </li>
      </ul>
    </div>
  );
}

export default ThemeSwitcher;
