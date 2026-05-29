/**
 * Contexto Global de Autenticación
 */

import React, { useReducer, useCallback, ReactNode } from 'react';
import { User, LoginRequest, RegisterRequest, AuthContextType } from '../types';
import { authService } from '../services/auth';
import { AuthContext } from './auth-context';

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}

type AuthAction =
  | { type: 'LOGIN_START' }
  | { type: 'LOGIN_SUCCESS'; payload: { user: User; token: string } }
  | { type: 'LOGIN_ERROR'; payload: string }
  | { type: 'REGISTER_START' }
  | { type: 'REGISTER_SUCCESS'; payload: { user: User; token: string } }
  | { type: 'REGISTER_COMPLETE' }
  | { type: 'REGISTER_ERROR'; payload: string }
  | { type: 'LOGOUT' }
  | { type: 'CLEAR_ERROR' }
  | { type: 'RESTORE_SESSION'; payload: { user: User; token: string } };

const initialState: AuthState = {
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,
};

function authReducer(state: AuthState, action: AuthAction): AuthState {
  switch (action.type) {
    case 'LOGIN_START':
      return { ...state, isLoading: true, error: null };

    case 'LOGIN_SUCCESS':
      return {
        ...state,
        user: action.payload.user,
        token: action.payload.token,
        isAuthenticated: true,
        isLoading: false,
        error: null,
      };

    case 'LOGIN_ERROR':
      return {
        ...state,
        isLoading: false,
        error: action.payload,
      };

    case 'REGISTER_START':
      return { ...state, isLoading: true, error: null };

    case 'REGISTER_SUCCESS':
      return {
        ...state,
        user: action.payload.user,
        token: action.payload.token,
        isAuthenticated: true,
        isLoading: false,
        error: null,
      };

    case 'REGISTER_COMPLETE':
      return {
        ...state,
        isLoading: false,
        error: null,
      };

    case 'REGISTER_ERROR':
      return {
        ...state,
        isLoading: false,
        error: action.payload,
      };

    case 'LOGOUT':
      return {
        ...initialState,
      };

    case 'CLEAR_ERROR':
      return {
        ...state,
        error: null,
      };

    case 'RESTORE_SESSION':
      return {
        ...state,
        user: action.payload.user,
        token: action.payload.token,
        isAuthenticated: true,
        isLoading: false,
      };

    default:
      return state;
  }
}

interface AuthProviderProps {
  children: ReactNode;
}

const errorMessage = (error: unknown, fallback: string) =>
  error instanceof Error ? error.message : fallback;

export function AuthProvider({ children }: AuthProviderProps) {
  const [state, dispatch] = useReducer(authReducer, initialState);

  // Restaurar sesión al cargar (si existe token guardado)
  React.useEffect(() => {
    const token = authService.getToken();
    const user = authService.getCurrentUser();

    if (token && user) {
      dispatch({
        type: 'RESTORE_SESSION',
        payload: { user, token },
      });
    }
  }, []);

  const login = useCallback(async (credentials: LoginRequest) => {
    dispatch({ type: 'LOGIN_START' });

    try {
      const { user, token } = await authService.login(credentials);
      dispatch({
        type: 'LOGIN_SUCCESS',
        payload: { user, token },
      });
    } catch (error: unknown) {
      dispatch({
        type: 'LOGIN_ERROR',
        payload: errorMessage(error, 'Error al iniciar sesion'),
      });
      throw error;
    }
  }, []);

  const register = useCallback(async (data: RegisterRequest) => {
    dispatch({ type: 'REGISTER_START' });

    try {
      await authService.register(data);
      dispatch({ type: 'REGISTER_COMPLETE' });
    } catch (error: unknown) {
      dispatch({
        type: 'REGISTER_ERROR',
        payload: errorMessage(error, 'Error al registrarse'),
      });
      throw error;
    }
  }, []);

  const logout = useCallback(() => {
    authService.logout();
    dispatch({ type: 'LOGOUT' });
  }, []);

  const clearError = useCallback(() => {
    dispatch({ type: 'CLEAR_ERROR' });
  }, []);

  const value: AuthContextType = {
    user: state.user,
    token: state.token,
    isAuthenticated: state.isAuthenticated,
    isLoading: state.isLoading,
    error: state.error,
    login,
    register,
    logout,
    clearError,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
