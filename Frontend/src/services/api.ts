/**
 * Cliente API centralizado con interceptores
 */

import axios, { AxiosInstance, AxiosError, AxiosRequestConfig, isAxiosError } from 'axios';
import { ApiResponse, ApiError } from '../types';

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

    this.client = axios.create({
      baseURL,
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Interceptor de request - agregar JWT
    this.client.interceptors.request.use(
      (config) => {
        if (typeof config.url === 'string' && /^([a-z][a-z\d+\-.]*:)?\/\//i.test(config.url)) {
          return Promise.reject(new Error('No se permiten URLs absolutas en el cliente API'));
        }

        const token = localStorage.getItem('token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Interceptor de response - manejar errores globales
    this.client.interceptors.response.use(
      (response) => response,
      (error: AxiosError) => {
        if (error.response?.status === 401) {
          // Token expirado o inválido
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          window.dispatchEvent(new Event('auth:expired'));
        }

        return Promise.reject(error);
      }
    );
  }

  /**
   * GET request
   */
  async get<T = unknown>(url: string, config: AxiosRequestConfig = {}): Promise<ApiResponse<T>> {
    try {
      const response = await this.client.get<ApiResponse<T>>(url, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * POST request
   */
  async post<T = unknown>(url: string, data: unknown, config: AxiosRequestConfig = {}): Promise<ApiResponse<T>> {
    try {
      const response = await this.client.post<ApiResponse<T>>(url, data, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * PUT request
   */
  async put<T = unknown>(url: string, data: unknown, config: AxiosRequestConfig = {}): Promise<ApiResponse<T>> {
    try {
      const response = await this.client.put<ApiResponse<T>>(url, data, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * DELETE request
   */
  async delete<T = unknown>(url: string, config: AxiosRequestConfig = {}): Promise<ApiResponse<T>> {
    try {
      const response = await this.client.delete<ApiResponse<T>>(url, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Maneja errores de API
   */
  private handleError(error: unknown): ApiError {
    if (isAxiosError<ApiError>(error) && error.response) {
      // Error de respuesta del servidor
      return error.response.data;
    } else if (isAxiosError(error) && error.request) {
      // No hay respuesta del servidor
      return {
        success: false,
        status: 0,
        message: 'No hay conexión con el servidor',
        timestamp: new Date().toISOString(),
      };
    } else {
      // Error en la configuración de la request
      return {
        success: false,
        status: 0,
        message: error instanceof Error ? error.message : 'Error desconocido',
        timestamp: new Date().toISOString(),
      };
    }
  }

  /**
   * Obtiene el token del almacenamiento
   */
  getToken(): string | null {
    return localStorage.getItem('token');
  }

  /**
   * Establece el token
   */
  setToken(token: string): void {
    localStorage.setItem('token', token);
  }

  /**
   * Elimina el token
   */
  clearToken(): void {
    localStorage.removeItem('token');
  }

  /**
   * Verifica si hay sesión activa
   */
  isAuthenticated(): boolean {
    return !!this.getToken();
  }
}

export const api = new ApiClient();
