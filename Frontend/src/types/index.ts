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
  is_email_verified?: boolean | number;
  last_login_at?: string | null;
  registered_at?: string;
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
  mac_address?: string | null;
  name: string;
  location?: string;
  status: 'active' | 'inactive' | 'maintenance';
  user_id: number;
  group_id?: number;
  max_temp: number;
  min_temp: number;
  firmware_version?: string | null;
  account_enabled?: boolean | number;
  send_interval_seconds?: number;
  protocol_version?: string | null;
  config_version?: number;
  last_sync_at?: string | null;
  last_packet_id?: string | null;
  last_packet_at?: string | null;
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
  request_id?: string;
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

export interface AuditRequestLog {
  id: number;
  request_id: string;
  user_id: number | null;
  method: string;
  path: string;
  status_code: number;
  success: number | boolean;
  duration_ms: number;
  ip_address?: string | null;
  error_message?: string | null;
  created_at: string;
}

export interface AuditEventLog {
  id: number;
  request_id: string | null;
  actor_user_id: number | null;
  event_type: string;
  entity_type?: string | null;
  entity_id?: string | null;
  action?: string | null;
  severity: 'info' | 'warning' | 'error' | 'critical';
  message?: string | null;
  created_at: string;
}

export interface AuditChangeLog {
  entity_type: string;
  entity_id: string | null;
  actor_user_id: number | null;
  action: string;
  field_changed: string;
  old_value: string | null;
  new_value: string | null;
  created_at: string;
}

export interface AuditSummary {
  hours: number;
  requests: {
    total: number;
    successful: number;
    client_errors: number;
    server_errors: number;
    avg_duration_ms: number | null;
    max_duration_ms: number | null;
  };
  top_paths: Array<{
    method: string;
    path: string;
    total: number;
    errors: number;
  }>;
  events_by_severity: Array<{
    severity: string;
    total: number;
  }>;
}
