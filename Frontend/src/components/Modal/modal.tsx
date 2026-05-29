import React from "react";
import "./modal.css";
interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  subtitle?: string;
  children?: React.ReactNode; // Esto permite que el modal tenga contenido dinámico
}

const Modal: React.FC<ModalProps> = ({
  isOpen,
  onClose,
  title,
  subtitle,
  children,
}) => {
  if (!isOpen) return null;

  return (
    <div className="ModalFondo" onClick={onClose}>
      <div className="ModalTotal" role="dialog" aria-modal="true" aria-labelledby="modal-title" onClick={(event) => event.stopPropagation()}>
        <div className="Modal-Cabecera">
          <div className="Modal-Cabecera-Texto">
            <h2 className="Modal-Cabecera-Titulo" id="modal-title">{title}</h2>
            {subtitle && <p className="Modal-Cabecera-Subtitulo">{subtitle}</p>}
          </div>
          <button className="Modal-Cabecera-Cerrar" onClick={onClose} aria-label="Cerrar modal" type="button">
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
        <div className="Modal-Contenido">{children}</div>
      </div>
    </div>
  );
};

export default Modal;
