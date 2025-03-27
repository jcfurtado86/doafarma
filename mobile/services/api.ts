import { useAuthStore } from '@/stores/authStore';
import AsyncStorage from '@react-native-async-storage/async-storage';
import axios from 'axios';
import { router } from 'expo-router';
import { Platform } from 'react-native';

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
    const token = await AsyncStorage.getItem('auth_token');

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
    // 401 Unauthorized error handling
    if (error.response?.status === 401) {
      await useAuthStore.getState().logout();

      if (router) {
        router.replace('/');
      }
    }
    return Promise.reject(error);
  }
);

export default api;
