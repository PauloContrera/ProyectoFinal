/**
 * Tipos y Interfaces de Temp Segura
 */

// ============ USUARIO ============
export interface User {
  id: number;
  name: string;
  username: string;
  email: string;
  phone?: string;
  role: 'superadmin' | 'admin' | 'client' | 'visitor';
  lang?: string;
  is_verified?: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface LoginRequest {
  identifier: string;
  password: string;
}

export interface RegisterRequest {
  name: string;
  username: string;
  email: string;
  password: string;
  phone?: string;
}

export interface AuthResponse {
  success: boolean;
  status: number;
  message: string;
  data: {
    token: string;
    user: User;
  };
  timestamp: string;
}

// ============ DISPOSITIVO ============
export interface Device {
  id: number;
  device_code: string;
  name: string;
  location?: string;
  status: 'active' | 'inactive' | 'maintenance';
  user_id: number;
  group_id?: number;
  max_temp: number;
  min_temp: number;
  firmware_version?: string | null;
  last_temperature_id?: number | null;
  last_temperature?: number | null;
  last_temperature_recorded_at?: string | null;
  last_reported_at?: string | null;
  created_at: string;
  updated_at?: string;
}

export interface DeviceGroup {
  id: number;
  name: string;
  description?: string;
  user_id: number;
  device_count?: number;
  created_at: string;
  updated_at?: string;
}

// ============ TEMPERATURA ============
export interface Temperature {
  id: number;
  device_id: number;
  temperature: number;
  humidity?: number;
  status: 'normal' | 'warning' | 'critical';
  created_at: string;
}

export interface TemperatureReading {
  id: number;
  device_id: number;
  temperature: number;
  timestamp: string;
}

export interface StockItem {
  id: number;
  device_id: number;
  name: string;
  quantity: number;
  expiration_date?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface TemperatureStats {
  current: number;
  min: number;
  max: number;
  average: number;
  readings_count: number;
}

// ============ ALERTAS ============
export interface Alert {
  id: number;
  device_id: number;
  alert_type: 'high_temp' | 'low_temp' | 'offline' | 'critical';
  severity: 'low' | 'medium' | 'high' | 'critical';
  message: string;
  is_resolved: boolean;
  created_at: string;
  resolved_at?: string;
}

// ============ RESPUESTA API ============
export interface ApiResponse<T = unknown> {
  success: boolean;
  status: number;
  message: string;
  data: T;
  timestamp: string;
}

export interface ApiError {
  success: false;
  status: number;
  message: string;
  errors?: string[];
  timestamp: string;
}

// ============ CONTEXTO DE AUTENTICACIÓN ============
export interface AuthContextType {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
  login: (credentials: LoginRequest) => Promise<void>;
  register: (data: RegisterRequest) => Promise<void>;
  logout: () => void;
  clearError: () => void;
}

// ============ ESTADO DE FORMULARIO ============
export interface FormError {
  field: string;
  message: string;
}

export interface FormState {
  values: Record<string, unknown>;
  errors: FormError[];
  isSubmitting: boolean;
  touched: Record<string, boolean>;
}

// ============ NOTIFICACIÓN ============
export interface Toast {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  message: string;
  duration?: number;
}

export interface ToastContextType {
  toasts: Toast[];
  addToast: (type: Toast['type'], message: string, duration?: number) => void;
  removeToast: (id: string) => void;
}

// ============ PAGINACIÓN ============
export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  per_page: number;
  total_pages: number;
}

export interface PaginationParams {
  page: number;
  per_page: number;
  sort_by?: string;
  order?: 'asc' | 'desc';
  search?: string;
}

// ============ ESTADÍSTICAS ============
export interface DashboardStats {
  total_devices: number;
  active_devices: number;
  alerts_pending: number;
  temp_average: number;
  system_status: 'operational' | 'warning' | 'critical';
}
