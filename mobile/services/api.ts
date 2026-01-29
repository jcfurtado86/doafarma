import { useAuthStore } from '@/stores/authStore';
import * as SecureStore from 'expo-secure-store';
import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';
import { router } from 'expo-router';
import { Platform } from 'react-native';
import Toast from 'react-native-toast-message';

const API_HOST = process.env.EXPO_PUBLIC_API_HOST || '192.168.0.1'; // Substitua pelo IP correto do seu servidor

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

// ============================================
// REFRESH TOKEN STATE
// ============================================
let isRefreshing = false;
let refreshSubscribers: ((token: string) => void)[] = [];

function subscribeTokenRefresh(callback: (token: string) => void): void {
  refreshSubscribers.push(callback);
}

function onTokenRefreshed(newToken: string): void {
  refreshSubscribers.forEach((callback) => callback(newToken));
  refreshSubscribers = [];
}

function onRefreshFailed(): void {
  refreshSubscribers = [];
}

// ============================================
// REQUEST INTERCEPTOR
// ============================================
api.interceptors.request.use(
  async (config) => {
    const token = await SecureStore.getItemAsync('auth_token');

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
  },
  (error) => Promise.reject(error)
);

// ============================================
// RESPONSE INTERCEPTOR
// ============================================
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

    // 401 Unauthorized (token expirado ou inválido)
    if (error.response?.status === 401) {
      // Don't retry if this is already the refresh endpoint (avoid infinite loop)
      if (originalRequest.url?.includes('/auth/refresh')) {
        await handleLogout('Sessão expirada', 'Faça login novamente para continuar.');
        return Promise.reject(error);
      }

      // Don't retry if we already tried
      if (originalRequest._retry) {
        return Promise.reject(error);
      }

      // If already refreshing, queue this request
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          subscribeTokenRefresh((newToken: string) => {
            originalRequest.headers.Authorization = `Bearer ${newToken}`;
            resolve(api.request(originalRequest));
          });
          // If refresh fails, reject queued requests
          setTimeout(() => {
            if (!isRefreshing) {
              reject(error);
            }
          }, 10000); // 10s timeout
        });
      }

      // Start refresh process
      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const refreshToken = await useAuthStore.getState().getRefreshToken();

        if (!refreshToken) {
          throw new Error('No refresh token available');
        }

        // Call refresh endpoint directly (not through authService to avoid circular dependency)
        const response = await api.post(
          '/v1/auth/refresh',
          {},
          {
            headers: {
              Authorization: `Bearer ${refreshToken}`,
            },
          }
        );

        const { access_token, expires_in } = response.data.data;

        // Update token in store
        await useAuthStore.getState().updateAccessToken(access_token, expires_in);

        // Notify queued requests
        onTokenRefreshed(access_token);

        // Retry original request with new token
        originalRequest.headers.Authorization = `Bearer ${access_token}`;
        return api.request(originalRequest);
      } catch (refreshError) {
        // Refresh failed - logout user
        onRefreshFailed();
        await handleLogout('Sessão expirada', 'Faça login novamente para continuar.');
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    // 403 Forbidden (sem permissão)
    if (error.response?.status === 403) {
      await handleLogout('Acesso negado', 'Você não tem permissão para esta ação.');
    }

    // 422 Unprocessable Entity (erros de validação)
    if (error.response?.status === 422) {
      const responseData = error.response.data as { errors?: object; message?: string };
      return Promise.reject({
        ...error,
        isValidationError: true,
        validationErrors: responseData.errors || {},
        message: responseData.message || 'Erro de validação',
      });
    }

    // 500 Internal Server Error
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

// ============================================
// HELPER FUNCTIONS
// ============================================
async function handleLogout(title: string, message: string): Promise<void> {
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

export default api;
