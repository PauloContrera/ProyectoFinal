/**
 * Servicio de Autenticación
 */

import { api } from './api';
import { User, LoginRequest, RegisterRequest } from '../types';

class AuthService {
  /**
   * Inicia sesión con email y contraseña
   */
  async login(credentials: LoginRequest): Promise<{ user: User; token: string }> {
    try {
      const response = await api.post<{ user: User; token: string }>(
        '/login',
        credentials
      );

      if (response.success && response.data.token) {
        api.setToken(response.data.token);
        this.saveUser(response.data.user);
        return response.data;
      }

      throw new Error(response.message || 'Error en login');
    } catch (error: any) {
      throw new Error(error.message || 'Error al iniciar sesión');
    }
  }

  /**
   * Registra un nuevo usuario
   */
  async register(data: RegisterRequest): Promise<void> {
    try {
      const response = await api.post(
        '/register',
        data
      );

      if (response.success) {
        return;
      }

      throw new Error(response.message || 'Error en registro');
    } catch (error: any) {
      throw new Error(error.message || 'Error al registrarse');
    }
  }

  /**
   * Verifica email
   */
  async verifyEmail(token: string): Promise<void> {
    try {
      const response = await api.get(`/verify-email?token=${encodeURIComponent(token)}`);

      if (!response.success) {
        throw new Error(response.message || 'Error en verificación');
      }
    } catch (error: any) {
      throw new Error(error.message || 'Error al verificar email');
    }
  }

  /**
   * Solicita recuperación de contraseña
   */
  async forgotPassword(email: string): Promise<void> {
    try {
      const response = await api.post('/request-password-reset', { email });

      if (!response.success) {
        throw new Error(response.message || 'Error en solicitud');
      }
    } catch (error: any) {
      throw new Error(error.message || 'Error al solicitar recuperación');
    }
  }

  /**
   * Recupera contraseña con token
   */
  async resetPassword(token: string, password: string): Promise<void> {
    try {
      const response = await api.post('/reset-password', {
        token,
        new_password: password,
      });

      if (!response.success) {
        throw new Error(response.message || 'Error en reset');
      }
    } catch (error: any) {
      throw new Error(error.message || 'Error al resetear contraseña');
    }
  }

  /**
   * Cierra sesión
   */
  logout(): void {
    api.clearToken();
    localStorage.removeItem('user');
  }

  /**
   * Obtiene el usuario actual del almacenamiento
   */
  getCurrentUser(): User | null {
    const user = localStorage.getItem('user');
    if (!user) return null;

    try {
      return JSON.parse(user);
    } catch {
      localStorage.removeItem('user');
      api.clearToken();
      return null;
    }
  }

  /**
   * Guarda el usuario en almacenamiento
   */
  private saveUser(user: User): void {
    localStorage.setItem('user', JSON.stringify(user));
  }

  /**
   * Verifica si hay sesión activa
   */
  isAuthenticated(): boolean {
    return api.isAuthenticated() && !!this.getCurrentUser();
  }

  /**
   * Obtiene el token actual
   */
  getToken(): string | null {
    return api.getToken();
  }
}

export const authService = new AuthService();
