import "./documentacion.css";
import React, { ReactNode, useState } from "react";
import Grafico from "../Temperatura/Temperatura-Individual/Grafico/grafico";
import Controladores from "../Temperatura/Controladores/controladores";
import StockGruposItem from "../Stocks/StockGrupos/StockGruposItem/StockGruposItem";
import TempEjemplo from "../../data/TempEjemplo";
import StocksEj from "../../data/StockEjem";


interface DocumentacionCardProps {
  title: string;
  children: ReactNode;
}

const DocumentacionCard: React.FC<DocumentacionCardProps> = ({ title, children }) => (
  <div className="Documentacion-card">
    <div className="Documentacion-cardHeader">
      <h3 className="Documentacion-cardTitle">{title}</h3>
    </div>
    <div className="Documentacion-cardContent">{children}</div>
  </div>
);

interface DocumentacionSectionProps {
  id: string;
  title: string;
  children: ReactNode;
}

const DocumentacionSection: React.FC<DocumentacionSectionProps> = ({ id, title, children }) => (
  <section id={id} className="Documentacion-section">
    <h3 className="Documentacion-title">{title}</h3>
    {children}
  </section>
);


const Documentacion = () => {

  const [VariableMinima, setVariableMinima] = useState(2);
  const manejarCambioAmarillo = (nuevoValorAmarillo: number) => {
    setVariableMinima(nuevoValorAmarillo);
  };
  const [VariableMaxima, setVariableMaxima] = useState(8);
  const manejarCambioRojo = (nuevoValorRojo: number) => {
    setVariableMaxima(nuevoValorRojo);
  };



  // const [controladoresActivos, setControladoresActivos] = useState<boolean>(false);
  const manejarToggleControladores = () => {
    // setControladoresActivos(estado);
  };


  return (
    <div className="DocumentacionTotal">
    
      <div className="Documentacion-container">
        <h2 className="Documentacion-title">Documentación de Temp Segura</h2>
        <div className="Documentacion-space">
          <h3 className="Documentacion-subtitle">Contenido:</h3>
          <ul className="Documentacion-list">
            <li><a href="#introduccion" className="Documentacion-link">Introducción</a></li>
            <li><a href="#graficos" className="Documentacion-link">Gráficos de Temperatura</a></li>
            <li><a href="#controladores" className="Documentacion-link">Controladores de Temperatura</a></li>
            <li><a href="#stock" className="Documentacion-link">Control de Stock</a></li>
            {/* <li><a href="#usuarios" className="Documentacion-link">Gestión de Usuarios</a></li>
            <li><a href="#tema" className="Documentacion-link">Cambio de Tema</a></li> */}
          </ul>
        </div>

        <DocumentacionSection id="introduccion" title="Introducción a Temp Segura">
          <p className="Documentacion-paragraph">
            Temp Segura es un sistema integral para el monitoreo de temperatura
            en refrigeradores médicos y la gestión de inventario. Esta
            documentación le guiará a través de las principales funcionalidades
            del sistema.
          </p>
        </DocumentacionSection>

        <DocumentacionSection id="graficos" title="Gráficos de Temperatura">
          <p className="Documentacion-paragraph">
            Los gráficos de temperatura proporcionan una visualización clara de
            las fluctuaciones de temperatura a lo largo del tiempo.
          </p>
          <DocumentacionCard title="Gráfico de Temperatura">
            <div className="Graficooooo">
            <Grafico
              datos={TempEjemplo}
              mostrarAlertas={false}
              alertaMinima={2}
              alertaMaxima={2}
              
            />
            </div>
          
          </DocumentacionCard>
          <p className="Documentacion-paragraph">
            Este gráfico muestra la temperatura a lo largo de un período de 24
            horas...
          </p>
          <p className="Documentacion-paragraph">Para resolver problemas comunes con los gráficos:</p>
          <ul className="Documentacion-list">
            <li>Si el gráfico no se carga, verifique su conexión a internet y recargue la página.</li>
            <li>Si los datos parecen incorrectos, asegúrese de que los sensores estén correctamente calibrados.</li>
            <li>Para ver un período de tiempo diferente, use los controles de fecha en la parte superior del gráfico.</li>
          </ul>
        </DocumentacionSection>

        <DocumentacionSection id="controladores" title="Controladores de Temperatura">
          <p className="Documentacion-paragraph">
            Los controladores de temperatura le permiten ajustar los límites de
            alerta para cada refrigerador.
          </p>
          <DocumentacionCard title="Control de Temperatura">
            <div className="Documentacion-cardContent">
            <div className="Controladoressolos">
            <Controladores
              ValorMinimo={VariableMinima}
              CambiarMinimo={manejarCambioAmarillo}
              ValorMaximo={VariableMaxima}
              CambiarMaximo={manejarCambioRojo} 
              onToggle={manejarToggleControladores}  
      
            />
          </div>
            </div>
          </DocumentacionCard>
          <p className="Documentacion-paragraph">
            Use los deslizadores para ajustar la temperatura actual y los límites de alerta...
          </p>
          <p className="Documentacion-paragraph">Solución de problemas comunes:</p>
          <ul className="Documentacion-list">
            <li>Si los controladores no responden, intente refrescar la página.</li>
            <li>Si las alertas no se activan, verifique que los límites estén configurados correctamente.</li>
            <li>Para una calibración precisa, use un termómetro externo.</li>
          </ul>
        </DocumentacionSection>

        <DocumentacionSection id="stock" title="Control de Stock">
          <p className="Documentacion-paragraph">
            El control de stock le permite gestionar el inventario de productos
            en cada refrigerador.
          </p>
          <DocumentacionCard title="Gestión de Stock">
            <div className="Documentacion-cardContent">
            {StocksEj.map((fridge, index) => (
        <StockGruposItem 
          key={index}
          stock={fridge.stock} 
          name={fridge.name} 
          location={fridge.location} 
        />
      ))}
            </div>
          </DocumentacionCard>
          <p className="Documentacion-paragraph">
            Para agregar un nuevo artículo, complete los campos y haga clic en
            "Agregar Artículo"...
          </p>
          <p className="Documentacion-paragraph">Solución de problemas comunes:</p>
          <ul className="Documentacion-list">
            <li>Si un artículo no aparece después de agregarlo, intente refrescar la página.</li>
            <li>Para corregir errores en los datos, use la función de edición.</li>
            <li>Si el stock no coincide con el inventario físico, realice un conteo manual.</li>
          </ul>
        </DocumentacionSection>

        {/* <DocumentacionSection id="usuarios" title="Gestión de Usuarios">
          <p className="Documentacion-paragraph">
            La gestión de usuarios le permite controlar quién tiene acceso al
            sistema y qué pueden hacer.
          </p>
          <DocumentacionCard title="Permisos de Usuario">
            <div className="Usuarios-cardContent">
              <p>tabluko</p>
            </div>
          </DocumentacionCard>
          <p className="Usuarios-paragraph">
            Use los interruptores para activar o desactivar permisos específicos...
          </p>
          <p className="Usuarios-paragraph">Solución de problemas comunes:</p>
          <ul className="Usuarios-list">
            <li>Si un usuario no puede acceder a ciertas funciones, verifique sus permisos.</li>
            <li>Para añadir un nuevo usuario, use el botón "Agregar Usuario".</li>
            <li>Para revocar permisos, considere desactivar su cuenta.</li>
          </ul>
        </DocumentacionSection>

        <DocumentacionSection id="tema" title="Cambio de Tema">
          <p className="Tema-paragraph">
            Temp Segura ofrece la opción de cambiar entre un tema claro y oscuro...
          </p>
          <DocumentacionCard title="Preferencia de Tema">
            <div className="Tema-cardContent">
              <p>cambio de tema</p>
            </div>
          </DocumentacionCard>
          <p className="Tema-paragraph">
            Use el interruptor para cambiar entre el tema claro y oscuro...
          </p>
          <p className="Tema-paragraph">Solución de problemas comunes:</p>
          <ul className="Tema-list">
            <li>Si el cambio de tema no se aplica correctamente, intente refrescar la página.</li>
            <li>En caso de problemas, cierre sesión y vuelva a iniciar sesión.</li>
            <li>Si prefiere un ajuste automático, busque la opción "Tema Automático".</li>
          </ul>
        </DocumentacionSection> */}

        <DocumentacionSection id="alertas" title="Sistema de Alertas">
          <p className="Alertas-description">
            Temp Segura cuenta con un sistema de alertas SMS que notifica a los usuarios...
          </p>
          <div className="Nota mt-4 p-4">
            <h4 className="Nota-title flex items-center text-lg font-semibold mb-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="Nota-icon mr-2">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
              </svg>
              Importante
            </h4>
            <p className="Nota-text">
              Asegúrese de que los números de teléfono de los usuarios estén actualizados...
            </p>
          </div>
        </DocumentacionSection>

        <DocumentacionSection id="soporte" title="Soporte">
          <p className="Soporte-description">
            Para obtener ayuda adicional o reportar problemas, por favor contacte a
            nuestro equipo de soporte en 
            <a href="mailto:support@Temp Segura.com" className="Soporte-link"> tempsegura.contact@gmail.com</a>
          </p>
        </DocumentacionSection>
      </div>
    </div>
  );
};

export default Documentacion;
