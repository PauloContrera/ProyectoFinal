import React from "react";
import "./terminos.css";

interface SectionProps {
  title: string;
  content: string;
  listItems?: string[]; 
}

const Section: React.FC<SectionProps> = ({ title, content, listItems }) => {
  return (
    <div className="Terminos-Individual">
      <h3 className="Terminos-Ind-Titulo">{title}</h3>
      <p className="Terminos-Ind-Subtitulo"> {content}</p>
      {listItems && (
        <ul className="Terminos-Ind-lista">
          {listItems.map((item, index) => (
            <li className="Terminos-Ind-Lista-item" key={index}>{item}</li>
          ))}
        </ul>
      )}
    </div>
  );
};

export default Section;
