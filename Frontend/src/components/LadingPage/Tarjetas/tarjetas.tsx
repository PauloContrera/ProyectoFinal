import React from "react";
import "./tarjetas.css";

// Define la interfaz para las propiedades del componente
interface FeatureCardProps {
  icon: React.ReactNode; // Puedes cambiar a string si el icono es un texto
  title: string;
  description: string;
  bgColor: string; // Para el color de fondo
  items?: React.ReactNode; // Puede ser opcional y acepta cualquier nodo de React
}

const FeatureCard: React.FC<FeatureCardProps> = ({ icon, title, description, bgColor, items }) => {
  return (
    <div className="Landing-Tarjeta-Total">
      <div className="Landing-Tarjeta-Titulo">
        <div
          className="Landing-Tarjeta-Titulo-Icono"
          style={{ backgroundColor: bgColor }} // Color de fondo dinámico
        >
          {icon}
        </div>
        <h3 className="Landing-Tarjeta-Titulo-Titulo">{title}</h3>
      </div>
      <div className="Landing-Tarjeta-Contenido">
        <p>{description}</p>
        {items}
      </div>
    </div>
  );
};

export default FeatureCard;
