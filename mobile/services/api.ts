import axios from 'axios';
import { Platform } from 'react-native';

const HOST_IP = process.env.EXPO_PUBLIC_HOST_IP || '192.168.0.1';

const getBaseUrl = () => {
  const baseUrl =
    Platform.OS === 'web' ? 'http://localhost:8000/api' : `http://${HOST_IP}:8000/api`;

  console.log('API está usando baseURL:', baseUrl);
  console.log('Platform.OS:', Platform.OS);
  return baseUrl;
};

const api = axios.create({
  baseURL: getBaseUrl(),
});

export default api;
