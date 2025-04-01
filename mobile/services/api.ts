import { useAuthStore } from '@/stores/authStore';
import * as SecureStore from 'expo-secure-store';
import axios from 'axios';
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

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    // 403 Forbidden
    if (error.response?.status === 403) {
      await useAuthStore.getState().logout();

      if (router) {
        router.replace('/');
      }
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

export default api;
