import axios from 'axios';
import { Platform } from 'react-native';
import Constants from 'expo-constants';

const HOST_IP = Constants.expoConfig?.extra?.hostIp || '192.168.1.71'; // Fallback para desenvolvimento

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
