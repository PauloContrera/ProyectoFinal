import React from "react";
import "./ModalTerm.css";
interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  subtitle?: string;
  children?: React.ReactNode; // Esto permite que el modal tenga contenido dinámico
}

const ModalTerm: React.FC<ModalProps> = ({
  isOpen,
  onClose,
  title,
  subtitle,
  children,
}) => {
  if (!isOpen) return null;

  return (
    <div className="ModalFondo-Term" onClick={onClose}>
      <div className="ModalTotal-Term" role="dialog" aria-modal="true" aria-labelledby="terms-modal-title" onClick={(event) => event.stopPropagation()}>
        <div className="Modal-Term-Cabecera">
          <div className="Modal-Term-Cabecera-Texto">
            <h2 className="Modal-Term-Cabecera-Titulo" id="terms-modal-title">{title}</h2>
            {subtitle && <p className="Modal-Term-Cabecera-Subtitulo">{subtitle}</p>}
          </div>
          <button className="Modal-Term-Cabecera-Cerrar" onClick={onClose} aria-label="Cerrar modal" type="button">
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
            >
              <path d="M18 6 6 18"></path>
              <path d="m6 6 12 12"></path>
            </svg>
          </button>
        </div>
        <div className="Modal-Term-Contenido">{children}</div>
      </div>
    </div>
  );
};

export default ModalTerm;
