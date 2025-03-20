import axios from 'axios';
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

export default api;
