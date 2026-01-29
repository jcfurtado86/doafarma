import { useAuthStore } from '@/stores/authStore';
import { STORAGE_KEYS } from '@/config/storage';
import { API_ENDPOINTS } from '@/config/endpoints';
import * as SecureStore from 'expo-secure-store';
import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';
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

let isRefreshing = false;
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
    // Don't override if Authorization header is already set (e.g., refresh token call)
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
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

    if (error.response?.status === 401) {
      // Don't retry the refresh endpoint itself
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
          failedQueue.push({
            resolve: (token: string) => {
              originalRequest.headers.Authorization = `Bearer ${token}`;
              resolve(api.request(originalRequest));
            },
            reject: (err: unknown) => {
              reject(err);
            },
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

        // Calls refresh endpoint directly to avoid circular dependency with authService
        const response = await api.post(
          API_ENDPOINTS.AUTH.REFRESH,
          {},
          {
            headers: {
              Authorization: `Bearer ${refreshToken}`,
            },
          }
        );

        const { access_token, expires_in } = response.data.data;

        await useAuthStore.getState().updateAccessToken(access_token, expires_in);

        // Process queued requests with new token
        processQueue(null, access_token);

        // Retry original request with new token
        originalRequest.headers.Authorization = `Bearer ${access_token}`;
        return api.request(originalRequest);
      } catch (refreshError) {
        // Process queued requests with error
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
