/**
 * Componente Input reutilizable con validación
 */

import React, { InputHTMLAttributes, ReactNode } from 'react';
import './Input.css';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  helperText?: string;
  icon?: ReactNode;
  isLoading?: boolean;
  success?: boolean;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
  (
    {
      label,
      error,
      helperText,
      icon,
      isLoading = false,
      success = false,
      className = '',
      disabled = false,
      ...props
    },
    ref
  ) => {
    const hasError = !!error;

    return (
      <div className={`input-wrapper ${className}`}>
        {label && (
          <label className={`input-label ${props.required ? 'required' : ''}`}>
            {label}
          </label>
        )}

        <div className="input-container">
          {icon && <div className="input-icon">{icon}</div>}

          <input
            ref={ref}
            className={`input-field ${hasError ? 'input-error' : ''} ${
              success ? 'input-success' : ''
            } ${icon ? 'input-with-icon' : ''} ${isLoading ? 'input-loading' : ''}`}
            disabled={disabled || isLoading}
            {...props}
          />

          {isLoading && (
            <div className="input-spinner">
              <div className="spinner"></div>
            </div>
          )}

          {success && !hasError && (
            <div className="input-success-icon">✓</div>
          )}
        </div>

        {(error || helperText) && (
          <p className={`input-text ${hasError ? 'input-error-text' : 'input-helper-text'}`}>
            {error || helperText}
          </p>
        )}
      </div>
    );
  }
);

Input.displayName = 'Input';

export default Input;
