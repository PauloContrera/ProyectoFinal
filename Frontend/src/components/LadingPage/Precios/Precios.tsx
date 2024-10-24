import React from "react";
import "./Precios.css";

// Define la interfaz para las propiedades del componente
interface PlanCardProps {
  title: string;
  description: string;
  price: string;
  features: string[];
  buttonLabel: string;
  highlight?: boolean; // Hacer que sea opcional
  styleType: "basico" | "profesional" | "empresarial"; // Define los tipos permitidos
}

// Define la interfaz para los estilos
interface PlanStyles {
  backgroundColor: string;
  borderColor: string;
  headerGradient: string;
  priceColor: string;
  durationColor: string;
  featureColor: string;
  checkmarkColor: string;
  buttonColor: string;
  buttonHoverColor: string;
  highlightBorderColor: string;
  badgeColor: string;
}

const PlanCard: React.FC<PlanCardProps> = ({
  title,
  description,
  price,
  features,
  buttonLabel,
  highlight,
  styleType,
}) => {
  const styles: Record<string, PlanStyles> = {
    basico: {
      backgroundColor: "#1f2937",
      borderColor: "#374151",
      headerGradient: "linear-gradient(to bottom, #1f2937, #374151)",
      priceColor: "#f9fafb",
      durationColor: "#9ca3af",
      featureColor: "#d1d5db",
      checkmarkColor: "#9ca3af",
      buttonColor: "#4b5563",
      buttonHoverColor: "#374151",
      highlightBorderColor: "#2563eb",
      badgeColor: "#2563eb",
    },
    profesional: {
      backgroundColor: "#1f2937",
      borderColor: "#2563eb",
      headerGradient: "linear-gradient(to bottom, #1e3a8a, #1d4ed8)",
      priceColor: "#60a5fa",
      durationColor: "#bfdbfe",
      featureColor: "#e0f2fe",
      checkmarkColor: "#60a5fa",
      buttonColor: "#2563eb",
      buttonHoverColor: "#1d4ed8",
      highlightBorderColor: "#2563eb",
      badgeColor: "#2563eb",
    },
    empresarial: {
      backgroundColor: "#2d2d2d",
      borderColor: "#6b46c1",
      headerGradient: "linear-gradient(to bottom, #2d3748, #6b46c1)",
      priceColor: "#a78bfa",
      durationColor: "#d8b4fe",
      featureColor: "#d1d5db",
      checkmarkColor: "#a78bfa",
      buttonColor: "#7c3aed",
      buttonHoverColor: "#6d28d9",
      highlightBorderColor: "#2563eb",
      badgeColor: "#2563eb",
    },
  };

  const mensaje = `Hola, me gustaría hablar sobre el tema de Temp Segura. Por el plan ${title}.`;
  const numeroWhatsApp: string = "+5492634203042";

  // Obtiene el estilo según el tipo de plan
  const selectedStyle = styles[styleType];

  return (
    <div
      className={`Landing-Plan-Total ${
        highlight ? "Landing-Plan-Resaltar" : ""
      }`}
      style={{
        backgroundColor: selectedStyle.backgroundColor,
        borderColor: selectedStyle.borderColor,
      }}
    >
      {highlight && (
        <div
          className="Landing-Plan-Resaltar-Texto"
          style={{ backgroundColor: selectedStyle.badgeColor }}
        >
          Más Popular
        </div>
      )}
      <div
        className="Landing-Plan-Cabesera"
        style={{ background: selectedStyle.headerGradient }}
      >
        <h3 style={{ color: "#f9fafb" }}>{title}</h3>
        <p style={{ color: selectedStyle.featureColor }}>{description}</p>
      </div>
      <div className="Landing-Plan-Contenido-Total">
        <div>
          <div className="Landing-Plan-Precio-Total">
            <span
              className="Landing-Plan-Precio-Precio"
              style={{ color: selectedStyle.priceColor }}
            >
              ${price}
            </span>
            <span
              className="Landing-Plan-Precio-Duracion"
              style={{ color: selectedStyle.durationColor }}
            >
              /mes
            </span>
          </div>
          <ul className="Landing-Plan-Contenido">
            {features.map((feature: string, index: number) => (
              <li key={index} style={{ color: selectedStyle.featureColor }}>
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
                  className="check-icon"
                >
                  <path
                    d="M20 6 9 17l-5-5"
                    style={{ stroke: selectedStyle.checkmarkColor }}
                  />
                </svg>
                <span>{feature}</span>
              </li>
            ))}
          </ul>
        </div>
        <div className="Landing-Plan-Accion">
          <button
            style={{
              backgroundColor: selectedStyle.buttonColor,
              color: "white",
            }}
            onMouseEnter={(e) =>
              (e.currentTarget.style.backgroundColor =
                selectedStyle.buttonHoverColor)
            }
            onMouseLeave={(e) =>
              (e.currentTarget.style.backgroundColor =
                selectedStyle.buttonColor)
            }
          >
            <a
              href={`https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(
                mensaje
              )}`}
              target="_blank"
              rel="noopener noreferrer"
            >
              {buttonLabel}
            </a>
          </button>
        </div>
      </div>
    </div>
  );
};

export default PlanCard;
