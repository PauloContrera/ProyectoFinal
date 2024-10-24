import "./lading.css";
import FeatureCard from "./Tarjetas/tarjetas";
import PlanCard from "./Precios/Precios";
import Logo from 'src/assets/Logos/TBlanca.svg';
import TempSegura from 'src/assets/Logos/SeguraBlanca.svg';
const Landing = ({
  toggleModal,
  onDemo,
  onLogin,
}: {
  toggleModal: () => void;
  onDemo: () => void;
  onLogin?: () => void;
}) => {
  const numeroWhatsApp: string = "+5492634203042";
  const mensaje: string =
    "Hola, me gustaría hablar sobre el tema de Temp Segura.";
  console.log(onLogin);
  
  return (
    <div id="Home" className="LandingTotal">
      <header className="Landing-header">
        <a
          className="Landing-header-logo"
          href="#Home"
          aria-label="Ir a la página de inicio"
        >
          <img src={Logo} alt="Logo de Temp Segura" />
          <img src={TempSegura} className="Landing-header-logo-text" alt="Logo de Temp Segura" />
          {/* <div className="Landing-header-logo-text">
            <span>Temp</span>
            <span>Segura</span>
          </div> */}
        </a>
        <nav className="Landing-header-nav">
          {/* Botón para ver la demo */}
          <button
            className="Landing-header-nav-button"
            aria-label="Ver demo"
            onClick={onDemo}
          >
            Ver Demo
          </button>

          {/* <button
            className="Landing-header-nav-button"
            aria-label="Iniciar sesión"
            onClick={onLogin}
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
              className="Landing-header-nav-SVG"
              aria-hidden="true"
            >
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
              <polyline points="10 17 15 12 10 7"></polyline>
              <line x1="15" x2="3" y1="12" y2="12"></line>
            </svg>
          </button> */}
        </nav>
      </header>
      <main>
        <section className="Landing-hero-Total" aria-labelledby="hero-title">
          <h1 id="hero-title" className="Landing-hero-Titulo">
            Monitoreo de Temperatura Inteligente
          </h1>
          <p className="Landing-hero-SubTitulo">
            Proteja sus activos críticos con Temp Segura. Monitoreo en tiempo real,
            alertas instantáneas y gestión de inventario.
          </p>
          <div className="Landing-hero-Botones-Todos">
            <button className="Landing-hero-Botones-Comerzar" onClick={onDemo}>
              <a  aria-label="Comenzar ahora">
                Comenzar Ahora
              </a>
            </button>
            <button className="Landing-hero-Botones-Documentacion" >
              <a href="#Beneficios"
                aria-label="Saber más sobre el funcionamiento"
              >
                Saber Más
              </a>
            </button>
          </div>
        </section>

        <section
          className="Landing-Areas-Total"
          aria-labelledby="benefits-title"
          id="Beneficios"
        >
          <h2 id="benefits-title" className="Landing-Areas-titulo">
            Beneficios de Temp Segura
          </h2>
          <div className="Landing-Areas-Items-Total">
            <FeatureCard
              icon={
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="#60A5FA"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  data-id="289"
                  aria-hidden="true"
                >
                  <path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                </svg>
              }
              bgColor="#1E3A8A"
              title="Monitoreo en Tiempo Real"
              description="Seguimiento continuo de la temperatura con actualizaciones instantáneas para garantizar la integridad de sus productos."
              items=""
            />
            <FeatureCard
              icon={
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="#4ADE80"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  aria-hidden="true"
                >
                  <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                  <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                </svg>
              }
              bgColor="#14532D"
              title="Alertas Instantáneas"
              description="Reciba notificaciones inmediatas cuando la temperatura se desvíe de los rangos establecidos."
              items=""
            />
            <FeatureCard
              icon={
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="#C084FC"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  aria-hidden="true"
                >
                  <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                  <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                  <path d="M10 9H8"></path>
                  <path d="M16 13H8"></path>
                  <path d="M16 17H8"></path>
                </svg>
              }
              bgColor="#581C87"
              title="Gestión de Inventario"
              description="Mantenga un registro detallado de su inventario, incluyendo fechas de vencimiento y ubicaciones."
              items=""
            />
          </div>
        </section>

        <section
          className="Landing-Areas-Total"
          aria-labelledby="application-areas-title"
        >
          <h2 id="application-areas-title" className="Landing-Areas-titulo">
            Áreas de Aplicación
          </h2>
          <div className="Landing-Areas-Items-Total">
            <FeatureCard
              icon={
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="#FB923C"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  aria-hidden="true"
                >
                  <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"></path>
                  <path d="M7 2v20"></path>
                  <path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"></path>
                </svg>
              }
              bgColor="#7C2D12"
              title="Industria Alimenticia"
              description="Aseguramos la calidad y seguridad de los alimentos mediante el monitoreo preciso de la cadena de frío."
              items={
                <ul>
                  <li>Restaurantes y servicios de catering</li>
                  <li>Supermercados y tiendas de conveniencia</li>
                  <li>Almacenes frigoríficos</li>
                  <li>Transporte de alimentos perecederos</li>
                </ul>
              }
            />
            <FeatureCard
              icon={
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="#4ADE80"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  aria-hidden="true"
                >
                  <path d="M6 18h8"></path>
                  <path d="M3 22h18"></path>
                  <path d="M14 22a7 7 0 1 0 0-14h-1"></path>
                  <path d="M9 14h2"></path>
                  <path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"></path>
                  <path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"></path>
                </svg>
              }
              bgColor="#14532D"
              title="Salud y Medicina"
              description="Controlamos las condiciones ambientales en hospitales, clínicas y laboratorios para garantizar la efectividad de los tratamientos."
              items={
                <ul>
                  <li>Laboratorios farmacéuticos</li>
                  <li>Clínicas y hospitales</li>
                  <li>Almacenamiento de vacunas</li>
                  <li>Transporte de equipos médicos</li>
                </ul>
              }
            />
            <FeatureCard
              icon={
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="#22D3EE"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  data-id="79"
                >
                  <path d="M4.5 3h15"></path>
                  <path d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"></path>
                  <path d="M6 14h12"></path>
                </svg>
              }
              bgColor="#164E63"
              title="Investigación y Desarrollo"
              description="Mantenemos condiciones óptimas para experimentos y almacenamiento de muestras en entornos de investigación."
              items={
                <ul>
                  <li>Laboratorios de investigación</li>
                  <li>Centros de biotecnología</li>
                  <li>Instalaciones de pruebas de materiales</li>
                  <li>Centros de investigación académica</li>
                </ul>
              }
            />
          </div>
        </section>

        <section className="Landing-Areas-Total">
          <h2 className="Landing-Areas-titulo">Planes y Precios</h2>
          <div className="Landing-Areas-Items-Total">
            <PlanCard
              title="Básico"
              description="Para pequeñas instalaciones"
              price="1"
              features={[
                "1 usuario",
                "Acceso al historial de temperatura",
                "Alertas básicas",
              ]}
              buttonLabel="Elegir Plan"
              highlight={false}
              styleType="basico" // Agrega el tipo de plan aquí
            />
            <PlanCard
              title="Profesional"
              description="Recomendado para la mayoría"
              price="5"
              features={[
                "Hasta 5 usuarios",
                "Acceso completo al historial",
                "Alertas personalizables",
                "Gestión de inventario",
              ]}
              buttonLabel="Elegir Plan"
              highlight={true}
              styleType="profesional"
            />
            <PlanCard
              title="Empresarial"
              description="Para grandes instalaciones"
              price="10"
              features={[
                "Usuarios ilimitados",
                "Características del plan Profesional",
                "Soporte prioritario 24/7",
                "Informes mensuales personalizados",
              ]}
              buttonLabel="Elegir Plan"
              highlight={false}
              styleType="empresarial"
            />
          </div>
        </section>

        <section className="Landing-Areas-Total2">
          <h2 className="Landing-Areas-titulo">
            ¿Listo para asegurar la integridad de sus productos?
          </h2>
          <p className="Landing-hero-SubTitulo">
            Únase a Temp Segura para el monitoreo de temperatura y la gestión de
            su inventario.
          </p>
          <div className="Landing-hero-Botones-Todos">
            <button className="Landing-hero-Botones-Documentacion">
              <a
                href={`https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(
                  mensaje
                )}`}
                target="_blank"
                rel="noopener noreferrer"
              >
                Contactenos
              </a>
            </button>
          </div>
        </section>
      </main>
      <footer className="Landing-footer-Total" role="contentinfo">
        <div className="Landing-footer-container">
          <p>
            &copy; {new Date().getFullYear()} Temp Segura. Todos los derechos
            reservados.
          </p>
          <nav>
            <button
              className="Landing-footer-button"
              type="button"
              aria-haspopup="dialog"
              aria-expanded="false"
              aria-controls="terms-dialog"
              onClick={toggleModal}
            >
              Términos y Condiciones
            </button>
          </nav>
        </div>
      </footer>
    </div>
  );
};

export default Landing;
