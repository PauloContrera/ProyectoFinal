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
  const [VariableMaxima, setVariableMaxima] = useState(8);

  return (
    <div className="DocumentacionTotal">
      <div className="Documentacion-container">
        <h2 className="Documentacion-title">Manual de uso de Temp Segura</h2>
        <p className="Documentacion-paragraph">
          Esta guía explica, paso a paso, cómo usar Temp Segura para controlar la temperatura de tus
          heladeras, recibir alarmas y administrar tu inventario. No necesitás conocimientos técnicos.
        </p>

        <div className="Documentacion-space">
          <h3 className="Documentacion-subtitle">Contenido</h3>
          <ul className="Documentacion-list">
            <li><a href="#introduccion" className="Documentacion-link">¿Qué es Temp Segura?</a></li>
            <li><a href="#roles" className="Documentacion-link">Tipos de usuario</a></li>
            <li><a href="#temperaturas" className="Documentacion-link">Temperaturas e historial</a></li>
            <li><a href="#controladores" className="Documentacion-link">Configurar el rango (mín/máx)</a></li>
            <li><a href="#alarmas" className="Documentacion-link">Alarmas</a></li>
            <li><a href="#stock" className="Documentacion-link">Inventario (stock)</a></li>
            <li><a href="#compartir" className="Documentacion-link">Compartir una heladera</a></li>
            <li><a href="#cuenta" className="Documentacion-link">Tu cuenta y contraseña</a></li>
            <li><a href="#soporte" className="Documentacion-link">Soporte</a></li>
          </ul>
        </div>

        <DocumentacionSection id="introduccion" title="¿Qué es Temp Segura?">
          <p className="Documentacion-paragraph">
            Temp Segura es una plataforma para vigilar la temperatura de heladeras y freezers (por
            ejemplo de vacunas, medicamentos o alimentos). Cada heladera tiene un dispositivo que mide
            la temperatura y la envía al sistema. Vos podés ver el historial en gráficos, recibir
            <strong> alarmas </strong> cuando la temperatura se sale del rango seguro, y llevar el
            <strong> inventario </strong> de lo que hay adentro.
          </p>
          <p className="Documentacion-paragraph">
            Para empezar, iniciá sesión con el usuario y contraseña que te dieron. Al entrar verás el
            menú lateral con las secciones: <strong>Temperaturas</strong>, <strong>Stock</strong>,
            <strong> Alarmas</strong> y este <strong>Manual</strong>.
          </p>
        </DocumentacionSection>

        <DocumentacionSection id="roles" title="Tipos de usuario">
          <p className="Documentacion-paragraph">Según tu rol vas a ver y poder hacer cosas distintas:</p>
          <ul className="Documentacion-list">
            <li><strong>Cliente:</strong> administra sus propias heladeras, grupos, rangos e inventario, y puede compartir heladeras con visitantes.</li>
            <li><strong>Visitante:</strong> solo lectura. Ve las heladeras que le compartieron (temperaturas, historial y stock), pero no puede editar.</li>
            <li><strong>Administrador / Superadmin:</strong> gestiona usuarios y dispositivos, y ve todas las heladeras con su dueño.</li>
          </ul>
        </DocumentacionSection>

        <DocumentacionSection id="temperaturas" title="Temperaturas e historial">
          <p className="Documentacion-paragraph">
            En <strong>Temperaturas</strong> ves tus heladeras agrupadas, cada una con su última lectura.
            Si la temperatura está dentro del rango se muestra en <span style={{ color: "#22c55e" }}>verde</span>;
            si está fuera, en <span style={{ color: "#ef4444" }}>rojo</span>. Tocá <strong>"Ver Historial"</strong>
            para abrir el gráfico de la heladera.
          </p>
          <DocumentacionCard title="Ejemplo de gráfico de historial">
            <div className="Graficooooo">
              <Grafico datos={TempEjemplo} mostrarAlertas={false} alertaMinima={2} alertaMaxima={2} />
            </div>
          </DocumentacionCard>
          <p className="Documentacion-paragraph">
            El gráfico muestra cómo varió la temperatura en el tiempo. Pasá el mouse (o tocá) sobre la
            línea para ver el valor exacto y la fecha de cada medición.
          </p>
        </DocumentacionSection>

        <DocumentacionSection id="controladores" title="Configurar el rango (mín/máx)">
          <p className="Documentacion-paragraph">
            Cada heladera tiene una temperatura <strong>mínima</strong> y <strong>máxima</strong> permitida.
            Si una lectura queda por debajo del mínimo o por encima del máximo, se genera una alarma.
            Ajustá los valores con los deslizadores y guardá los cambios.
          </p>
          <DocumentacionCard title="Control de rango de temperatura">
            <div className="Documentacion-cardContent">
              <div className="Controladoressolos">
                <Controladores
                  ValorMinimo={VariableMinima}
                  CambiarMinimo={setVariableMinima}
                  ValorMaximo={VariableMaxima}
                  CambiarMaximo={setVariableMaxima}
                  onToggle={() => {}}
                />
              </div>
            </div>
          </DocumentacionCard>
          <p className="Documentacion-paragraph">
            Consejo: configurá el rango según lo que guardás (por ejemplo, las vacunas suelen ir entre
            2&nbsp;°C y 8&nbsp;°C). Solo los clientes (y administradores) pueden cambiar el rango; los
            visitantes solo ven.
          </p>
        </DocumentacionSection>

        <DocumentacionSection id="alarmas" title="Alarmas">
          <p className="Documentacion-paragraph">
            Cuando una heladera se sale del rango, Temp Segura crea una <strong>alarma</strong> y le
            avisa por correo al dueño. Entrá a la sección <strong>Alarmas</strong> para verlas todas.
            Cada alarma pasa por tres estados:
          </p>
          <ul className="Documentacion-list">
            <li><strong>Activa:</strong> recién detectada, nadie la atendió todavía.</li>
            <li><strong>Reconocida:</strong> alguien la vio y está al tanto, pero el problema sigue. Usá el botón <strong>"Reconocer"</strong>.</li>
            <li><strong>Resuelta:</strong> el problema se solucionó. Usá el botón <strong>"Resolver"</strong>.</li>
          </ul>
          <p className="Documentacion-paragraph">
            Podés filtrar por estado (Activas, Reconocidas, Resueltas o Todas) para enfocarte en lo que
            falta atender. También vas a ver las alarmas dentro del historial de cada heladera.
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
              Para recibir los avisos, mantené tu correo y tu teléfono actualizados en la configuración
              de tu cuenta. Los avisos por SMS los envía el propio dispositivo de la heladera.
            </p>
          </div>
        </DocumentacionSection>

        <DocumentacionSection id="stock" title="Inventario (stock)">
          <p className="Documentacion-paragraph">
            En <strong>Stock</strong> llevás el control de lo que hay dentro de cada heladera: nombre del
            artículo, cantidad y fecha de vencimiento. Tocá <strong>"Agregar Nuevo Artículo"</strong>,
            completá los datos y guardá. Para corregir o borrar un ítem, usá los botones de cada fila.
          </p>
          <DocumentacionCard title="Ejemplo de inventario de una heladera">
            <div className="Documentacion-cardContent">
              {StocksEj.map((fridge, index) => (
                <StockGruposItem key={index} stock={fridge.stock} name={fridge.name} location={fridge.location} />
              ))}
            </div>
          </DocumentacionCard>
        </DocumentacionSection>

        <DocumentacionSection id="compartir" title="Compartir una heladera">
          <p className="Documentacion-paragraph">
            Si sos cliente, podés darle acceso de <strong>solo lectura</strong> a un visitante (por
            ejemplo, un auditor o inspector) para que pueda mirar las temperaturas y el stock de una
            heladera, sin poder modificar nada. Desde la heladera, otorgá el acceso al usuario que quieras.
            Para quitarlo, revocá el acceso en cualquier momento.
          </p>
        </DocumentacionSection>

        <DocumentacionSection id="cuenta" title="Tu cuenta y contraseña">
          <p className="Documentacion-paragraph">
            Tocá el ícono de usuario (arriba a la derecha) para abrir la configuración de tu cuenta. Ahí
            podés actualizar tu nombre, correo y teléfono, y cambiar tu nombre de usuario o tu contraseña.
          </p>
          <ul className="Documentacion-list">
            <li>¿Olvidaste la contraseña? En la pantalla de inicio de sesión usá <strong>"¿Olvidaste tu contraseña?"</strong> y te llega un enlace por correo para crear una nueva.</li>
            <li>Al registrarte, vas a recibir un correo para <strong>verificar tu cuenta</strong> antes de poder iniciar sesión.</li>
          </ul>
        </DocumentacionSection>

        <DocumentacionSection id="soporte" title="Soporte">
          <p className="Documentacion-paragraph">
            ¿Necesitás ayuda o querés reportar un problema? Escribinos a{" "}
            <a href="mailto:soporte@tempsegura.orbitar.dev" className="Documentacion-link">
              soporte@tempsegura.orbitar.dev
            </a>.
          </p>
        </DocumentacionSection>
      </div>
    </div>
  );
};

export default Documentacion;
