import api from '@/services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';

interface User {
  id: number;
  name: string;
  email: string;
}

interface AuthStoreState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;

  saveSession: (user: User, token: string) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<boolean>;
}

export const useAuthStore = create<AuthStoreState>((set) => ({
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,

  saveSession: async (user, token) => {
    try {
      await AsyncStorage.setItem('auth_token', token);
      await AsyncStorage.setItem('user', JSON.stringify(user));

      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      set({ user, token, isAuthenticated: true });
    } catch (error) {
      console.error('Error saving session:', error);
      set({ error: 'Failed to save session' });
    }
  },

  logout: async () => {
    try {
      await AsyncStorage.removeItem('auth_token');
      await AsyncStorage.removeItem('user');

      delete api.defaults.headers.common['Authorization'];

      set({ user: null, token: null, isAuthenticated: false });
    } catch (error) {
      console.error('Error logging out:', error);
      set({ error: 'Failed to log out' });
    }
  },

  checkAuth: async () => {
    try {
      set({ isLoading: true });

      const token = await AsyncStorage.getItem('auth_token');
      if (!token) {
        set({ isLoading: false });
        return false;
      }

      const userJson = await AsyncStorage.getItem('user');
      if (!userJson) {
        set({ isLoading: false });
        return false;
      }

      const user = JSON.parse(userJson);

      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      set({ user, token, isAuthenticated: true, isLoading: false });
      return true;
    } catch (error) {
      console.error('Error checking authentication:', error);
      set({ isLoading: false, error: 'Failed to check authentication' });
      return false;
    }
  },
}));
