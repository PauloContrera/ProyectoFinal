/**
 * Página de Login
 * Componente que maneja la autenticación del usuario
 */

import { useState } from 'react';
import { useAuth } from '../hooks/useAuth';
import { LoginRequest, RegisterRequest } from '../types';
import Input from '../components/common/Input';
import './LoginPage.css';

type FormMode = 'login' | 'register';

export default function LoginPage() {
  const [mode, setMode] = useState<FormMode>('login');
  const { login, register, isLoading, error } = useAuth();

  // Form state
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    name: '',
    username: '',
    phone: '',
  });

  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [submitError, setSubmitError] = useState('');

  /**
   * Maneja cambios en los inputs
   */
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
    // Limpiar error del campo
    if (formErrors[name]) {
      setFormErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  /**
   * Valida formulario de login
   */
  const validateLogin = (): boolean => {
    const errors: Record<string, string> = {};

    if (!formData.email) {
      errors.email = 'Email es requerido';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      errors.email = 'Email inválido';
    }

    if (!formData.password) {
      errors.password = 'Contraseña es requerida';
    } else if (formData.password.length < 6) {
      errors.password = 'Mínimo 6 caracteres';
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  /**
   * Valida formulario de registro
   */
  const validateRegister = (): boolean => {
    const errors: Record<string, string> = {};

    if (!formData.name) {
      errors.name = 'Nombre es requerido';
    } else if (formData.name.length < 2) {
      errors.name = 'Mínimo 2 caracteres';
    }

    if (!formData.username) {
      errors.username = 'Usuario es requerido';
    } else if (!/^[a-zA-Z][a-zA-Z0-9_]{2,}$/.test(formData.username)) {
      errors.username = 'Mínimo 3 caracteres, comienza con letra';
    }

    if (!formData.email) {
      errors.email = 'Email es requerido';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      errors.email = 'Email inválido';
    }

    if (!formData.password) {
      errors.password = 'Contraseña es requerida';
    } else if (formData.password.length < 8) {
      errors.password = 'Mínimo 8 caracteres';
    } else if (
      !/[A-Z]/.test(formData.password) ||
      !/[a-z]/.test(formData.password) ||
      !/[0-9]/.test(formData.password)
    ) {
      errors.password = '1 mayúscula, 1 minúscula, 1 número requeridos';
    }

    if (formData.phone && !/^\+?[1-9]\d{1,14}$/.test(formData.phone.replace(/\s/g, ''))) {
      errors.phone = 'Teléfono inválido';
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  /**
   * Maneja el submit del formulario
   */
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitError('');

    try {
      if (mode === 'login') {
        if (!validateLogin()) return;

        const credentials: LoginRequest = {
          identifier: formData.email,
          password: formData.password,
        };

        await login(credentials);
      } else {
        if (!validateRegister()) return;

        const data: RegisterRequest = {
          name: formData.name,
          username: formData.username,
          email: formData.email,
          password: formData.password,
          phone: formData.phone || undefined,
        };

        await register(data);
        setSubmitError('Registro creado. Revisa tu email para verificar la cuenta antes de iniciar sesion.');
        setMode('login');
      }
    } catch (err: any) {
      setSubmitError(err.message || 'Error al procesar solicitud');
    }
  };

  /**
   * Alterna entre login y registro
   */
  const toggleMode = () => {
    setMode(mode === 'login' ? 'register' : 'login');
    setFormErrors({});
    setSubmitError('');
  };

  return (
    <div className="login-page">
      <div className="login-container">
        {/* Encabezado */}
        <div className="login-header">
          <h1 className="login-title">Temp Segura</h1>
          <p className="login-subtitle">
            {mode === 'login'
              ? 'Monitoreo de temperatura seguro'
              : 'Crea tu cuenta para empezar'}
          </p>
        </div>

        {/* Formulario */}
        <form onSubmit={handleSubmit} className="login-form">
          {/* Error global */}
          {(submitError || error) && (
            <div className="form-error-banner">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clipRule="evenodd"
                />
              </svg>
              <span>{submitError || error}</span>
            </div>
          )}

          {/* Campos Login */}
          {mode === 'login' && (
            <>
              <Input
                type="email"
                name="email"
                label="Email"
                placeholder="tu@email.com"
                value={formData.email}
                onChange={handleInputChange}
                error={formErrors.email}
                disabled={isLoading}
                required
              />

              <Input
                type="password"
                name="password"
                label="Contraseña"
                placeholder="Tu contraseña"
                value={formData.password}
                onChange={handleInputChange}
                error={formErrors.password}
                helperText="Mínimo 6 caracteres"
                disabled={isLoading}
                required
              />
            </>
          )}

          {/* Campos Registro */}
          {mode === 'register' && (
            <>
              <Input
                type="text"
                name="name"
                label="Nombre completo"
                placeholder="Tu nombre"
                value={formData.name}
                onChange={handleInputChange}
                error={formErrors.name}
                disabled={isLoading}
                required
              />

              <Input
                type="text"
                name="username"
                label="Nombre de usuario"
                placeholder="usuario123"
                value={formData.username}
                onChange={handleInputChange}
                error={formErrors.username}
                helperText="3+ caracteres, comienza con letra"
                disabled={isLoading}
                required
              />

              <Input
                type="email"
                name="email"
                label="Email"
                placeholder="tu@email.com"
                value={formData.email}
                onChange={handleInputChange}
                error={formErrors.email}
                disabled={isLoading}
                required
              />

              <Input
                type="password"
                name="password"
                label="Contraseña"
                placeholder="Contraseña segura"
                value={formData.password}
                onChange={handleInputChange}
                error={formErrors.password}
                helperText="8+ caracteres, incluir mayúscula, minúscula y número"
                disabled={isLoading}
                required
              />

              <Input
                type="tel"
                name="phone"
                label="Número de teléfono"
                placeholder="+54 9 11 1234 5678"
                value={formData.phone}
                onChange={handleInputChange}
                error={formErrors.phone}
                helperText="Opcional"
                disabled={isLoading}
              />
            </>
          )}

          {/* Botón submit */}
          <button
            type="submit"
            disabled={isLoading}
            className="login-button"
          >
            {isLoading ? (
              <>
                <span className="spinner"></span>
                {mode === 'login' ? 'Iniciando...' : 'Registrando...'}
              </>
            ) : mode === 'login' ? (
              'Iniciar sesión'
            ) : (
              'Crear cuenta'
            )}
          </button>
        </form>

        {/* Toggle Login/Register */}
        <div className="login-footer">
          <p>
            {mode === 'login'
              ? '¿No tienes cuenta?'
              : '¿Ya tienes cuenta?'}
            <button
              type="button"
              onClick={toggleMode}
              disabled={isLoading}
              className="login-toggle"
            >
              {mode === 'login' ? 'Regístrate aquí' : 'Inicia sesión'}
            </button>
          </p>
        </div>
      </div>
    </div>
  );
}
