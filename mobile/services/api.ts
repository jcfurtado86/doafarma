import { useAuthStore } from '@/stores/authStore';
import { useNetworkStore } from '@/stores/networkStore';
import { STORAGE_KEYS } from '@/config/storage';
import { API_ENDPOINTS } from '@/config/endpoints';
import * as SecureStore from 'expo-secure-store';
import axios, {
  AxiosError,
  AxiosRequestConfig,
  AxiosResponse,
  InternalAxiosRequestConfig,
} from 'axios';
import { router } from 'expo-router';
import { Platform } from 'react-native';
import Toast from 'react-native-toast-message';

const API_HOST = process.env.EXPO_PUBLIC_API_HOST || '192.168.0.1';

const getBaseUrl = () => {
  if (Platform.OS === 'web') {
    return 'http://localhost:8000/api';
  }

  if (API_HOST.startsWith('http://') || API_HOST.startsWith('https://')) {
    return `${API_HOST}/api`;
  }

  return `http://${API_HOST}:8000/api`;
};

const api = axios.create({
  baseURL: getBaseUrl(),
});

// Axios bug workaround: async error interceptors return config instead of return value.
// We store the retry response on config and extract it via apiClient wrapper.
// See: mobile/docs/AXIOS_BUG_INVESTIGATION.md

interface ConfigWithRetryResponse extends InternalAxiosRequestConfig {
  _retry?: boolean;
  __retryResponse?: AxiosResponse;
}

let isRefreshing = false;
let isLoggingOut = false;
let failedQueue: {
  resolve: (token: string) => void;
  reject: (error: unknown) => void;
}[] = [];

function processQueue(error: unknown, token: string | null = null): void {
  failedQueue.forEach((promise) => {
    if (error) {
      promise.reject(error);
    } else if (token) {
      promise.resolve(token);
    }
  });
  failedQueue = [];
}

api.interceptors.request.use(
  async (config) => {
    const { isConnected } = useNetworkStore.getState();
    if (!isConnected) {
      const error = new Error('Você está sem conexão com a internet.');
      Object.assign(error, { isNetworkError: true, isOfflineError: true });
      return Promise.reject(error);
    }

    if (config.headers.Authorization) {
      return config;
    }

    const token = await SecureStore.getItemAsync(STORAGE_KEYS.AUTH_TOKEN);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => {
    const configWithResponse = response as unknown as ConfigWithRetryResponse;
    if (
      configWithResponse &&
      configWithResponse.__retryResponse &&
      !('status' in response && typeof response.status === 'number')
    ) {
      return configWithResponse.__retryResponse;
    }
    return response;
  },
  async (error: AxiosError) => {
    const originalRequest = error.config as ConfigWithRetryResponse;

    if (error.response?.status === 401) {
      if (isLoggingOut) {
        return Promise.reject(error);
      }

      if (originalRequest.url?.includes('/auth/refresh')) {
        isRefreshing = false;
        await handleLogout('Sessão expirada', 'Faça login novamente para continuar.');
        return Promise.reject(error);
      }

      if (originalRequest._retry) {
        return Promise.reject(error);
      }

      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({
            resolve: async (token: string) => {
              originalRequest.headers.Authorization = `Bearer ${token}`;
              try {
                const result = await api.request(originalRequest);
                resolve(result);
              } catch (err) {
                reject(err);
              }
            },
            reject: (err: unknown) => reject(err),
          });
        });
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const refreshToken = await SecureStore.getItemAsync(STORAGE_KEYS.REFRESH_TOKEN);

        if (!refreshToken) {
          throw new Error('No refresh token available');
        }

        const refreshResponse = await api.post(
          API_ENDPOINTS.AUTH.REFRESH,
          {},
          {
            headers: {
              Authorization: `Bearer ${refreshToken}`,
            },
          }
        );

        const { access_token, expires_in } = refreshResponse.data.data;

        await useAuthStore.getState().updateAccessToken(access_token, expires_in);
        processQueue(null, access_token);

        originalRequest.headers.Authorization = `Bearer ${access_token}`;
        const retryResult = await api.request(originalRequest);

        originalRequest.__retryResponse = retryResult;
        return originalRequest as unknown as AxiosResponse;
      } catch (refreshError) {
        // Note: isRefreshing is reset in finally block (always executes)
        processQueue(refreshError, null);
        await handleLogout('Sessão expirada', 'Faça login novamente para continuar.');
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    if (error.response?.status === 403) {
      await handleLogout('Acesso negado', 'Você não tem permissão para esta ação.');
    }

    if (error.response?.status === 422) {
      const responseData = error.response.data as { errors?: object; message?: string };
      return Promise.reject({
        ...error,
        isValidationError: true,
        validationErrors: responseData.errors || {},
        message: responseData.message || 'Erro de validação',
      });
    }

    if (error.response?.status === 500) {
      Toast.show({
        type: 'error',
        text1: 'Erro no servidor',
        text2: 'Estamos com problemas técnicos. Tente novamente mais tarde.',
        visibilityTime: 4000,
        autoHide: true,
      });
    }

    return Promise.reject(error);
  }
);

async function handleLogout(title: string, message: string): Promise<void> {
  if (isLoggingOut) {
    return;
  }

  isLoggingOut = true;

  await useAuthStore.getState().logout();

  if (router) {
    router.replace('/');
  }

  Toast.show({
    type: 'error',
    text1: title,
    text2: message,
    visibilityTime: 3000,
    autoHide: true,
  });
}

/**
 * Reset all internal state flags.
 * Should be called when user logs in to clear stale state from previous session.
 */
export function resetApiState(): void {
  isLoggingOut = false;
  isRefreshing = false;
  failedQueue = [];
}

/**
 * Get current internal state for debugging/testing.
 * @internal - Only use in tests
 */
export function getApiState(): {
  isRefreshing: boolean;
  isLoggingOut: boolean;
  queueLength: number;
} {
  return {
    isRefreshing,
    isLoggingOut,
    queueLength: failedQueue.length,
  };
}

function isConfigWithRetryResponse(obj: unknown): obj is ConfigWithRetryResponse {
  if (!obj || typeof obj !== 'object') {
    return false;
  }

  const config = obj as ConfigWithRetryResponse;

  if (!config.__retryResponse) {
    return false;
  }

  if ('status' in config && typeof config.status === 'number') {
    return false;
  }

  if (!('method' in config) || !('url' in config)) {
    return false;
  }

  return true;
}

function extractResponse<T>(result: unknown): AxiosResponse<T> {
  if (isConfigWithRetryResponse(result)) {
    return result.__retryResponse as AxiosResponse<T>;
  }
  return result as AxiosResponse<T>;
}

/**
 * Wrapper that extracts __retryResponse from config objects due to axios bug.
 * Use this instead of raw `api` in all services.
 * @see mobile/docs/AXIOS_BUG_INVESTIGATION.md
 */
export const apiClient = {
  async get<T = any>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    const result = await api.get<T>(url, config);
    return extractResponse<T>(result);
  },

  async post<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<AxiosResponse<T>> {
    const result = await api.post<T>(url, data, config);
    return extractResponse<T>(result);
  },

  async put<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<AxiosResponse<T>> {
    const result = await api.put<T>(url, data, config);
    return extractResponse<T>(result);
  },

  async patch<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<AxiosResponse<T>> {
    const result = await api.patch<T>(url, data, config);
    return extractResponse<T>(result);
  },

  async delete<T = any>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    const result = await api.delete<T>(url, config);
    return extractResponse<T>(result);
  },

  async request<T = any>(config: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    const result = await api.request<T>(config);
    return extractResponse<T>(result);
  },

  defaults: api.defaults,
};

export default api;
