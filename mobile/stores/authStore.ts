import api from '@/services/api';
import {
  registerForPushNotificationsAsync,
  registerPushTokenWithBackend,
  removePushTokenFromBackend,
} from '@/services/pushNotificationService';
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';

export interface User {
  id: number;
  name: string;
  email: string;
  phone_number?: string;
  role: 'doctor' | 'receptor';
}

interface AuthStoreState {
  user: User | null;
  token: string | null;
  pushToken: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;

  saveSession: (user: User, token: string) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<boolean>;
  clearError: () => void;
  setupPushNotifications: () => Promise<void>;
}

export const useAuthStore = create<AuthStoreState>((set, get) => ({
  user: null,
  token: null,
  pushToken: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,

  saveSession: async (user, token) => {
    try {
      await SecureStore.setItemAsync('auth_token', token);
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
      const { pushToken } = get();

      // Remove push token from backend before logging out
      if (pushToken) {
        await removePushTokenFromBackend(pushToken);
      }

      await SecureStore.deleteItemAsync('auth_token');
      await AsyncStorage.removeItem('user');
      await AsyncStorage.removeItem('push_token');

      delete api.defaults.headers.common['Authorization'];

      set({ user: null, token: null, pushToken: null, isAuthenticated: false });
    } catch (error) {
      console.error('Error logging out:', error);
      set({ error: 'Failed to log out' });
    }
  },

  checkAuth: async () => {
    try {
      set({ isLoading: true });

      const token = await SecureStore.getItemAsync('auth_token');
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

  clearError: () => set({ error: null }),

  setupPushNotifications: async () => {
    try {
      // Get push token from device
      const expoPushToken = await registerForPushNotificationsAsync();

      if (expoPushToken) {
        // Register with backend
        await registerPushTokenWithBackend(expoPushToken);

        // Save locally
        await AsyncStorage.setItem('push_token', expoPushToken);
        set({ pushToken: expoPushToken });

        console.log('Push notifications configured:', expoPushToken);
      }
    } catch (error) {
      console.error('Error setting up push notifications:', error);
    }
  },
}));
