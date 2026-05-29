import "./terminos.css";
import Section from "./Seccion";

const TermsAndConditions = () => {
    return (
      <div className="Terminos-Total">
        <Section
          title="1. Aceptación de los Términos"
          content="Al acceder y utilizar los servicios de Temp Segura, usted acepta estar sujeto a estos Términos y Condiciones, todas las leyes y regulaciones aplicables, y acepta que es responsable del cumplimiento de las leyes locales aplicables."
        />
  
        <Section
          title="2. Uso de la Licencia"
          content="Se le concede permiso para utilizar temporalmente Temp Segura en un solo dispositivo, ya sea personal o empresarial. Esta licencia no permitirá:"
          listItems={[
            "Modificar o copiar los materiales.",
            "Usar los materiales para cualquier propósito comercial o para exhibición pública.",
            "Intentar descompilar o aplicar ingeniería inversa a cualquier software contenido en Temp Segura.",
            "Eliminar cualquier copyright u otras notaciones de propiedad de los materiales.",
            "Transferir los materiales a otra persona o duplicar los materiales en cualquier otro servidor."
          ]}
        />
  
        <Section
          title="3. Exención de Responsabilidad"
          content="Los materiales en Temp Segura se proporcionan 'tal cual'. No ofrecemos garantías, expresas o implícitas, y por la presente renunciamos y negamos todas las demás garantías."
        />
  
        <Section
          title="4. Limitaciones"
          content="En ningún caso Temp Segura o sus proveedores serán responsables por cualquier daño (incluyendo, sin limitación, daños por pérdida de datos o beneficio, o debido a interrupción del negocio)."
        />
  
        <Section
          title="5. Revisiones y Erratas"
          content="Los materiales que aparecen en Temp Segura podrían incluir errores técnicos, tipográficos o fotográficos. Temp Segura no garantiza que los materiales sean precisos o actuales."
        />
  
        <Section
          title="6. Enlaces"
          content="Temp Segura no ha revisado todos los sitios enlazados a su sitio web y no es responsable por el contenido de ningún sitio enlazado. El uso de cualquier sitio enlazado es bajo el propio riesgo del usuario."
        />
  
        <Section
          title="7. Modificaciones a los Términos de Servicio"
          content="Temp Segura puede revisar estos términos de servicio para su sitio web en cualquier momento sin previo aviso. Al usar este sitio web, usted acepta estar sujeto a la versión actual de estos términos de servicio."
        />
  
        <Section
          title="8. Ley Aplicable"
          content="Estos términos y condiciones se rigen e interpretan de acuerdo con las leyes de [Su País/Estado], y usted se somete irrevocablemente a la jurisdicción exclusiva de los tribunales en esa ubicación."
        />
      </div>
    );
  };
  
export default TermsAndConditions;
