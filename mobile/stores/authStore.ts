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
  refreshToken: string | null;
  expiresAt: number | null;
  pushToken: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;

  saveSession: (
    user: User,
    token: string,
    refreshToken?: string,
    expiresIn?: number
  ) => Promise<void>;
  updateAccessToken: (token: string, expiresIn: number) => Promise<void>;
  getRefreshToken: () => Promise<string | null>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<boolean>;
  clearError: () => void;
  setupPushNotifications: () => Promise<void>;
}

export const useAuthStore = create<AuthStoreState>((set, get) => ({
  user: null,
  token: null,
  refreshToken: null,
  expiresAt: null,
  pushToken: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,

  saveSession: async (user, token, refreshToken?, expiresIn?) => {
    try {
      await SecureStore.setItemAsync('auth_token', token);

      // Only save refresh token if provided (login has it, register may not)
      if (refreshToken) {
        await SecureStore.setItemAsync('refresh_token', refreshToken);
      }

      await AsyncStorage.setItem('user', JSON.stringify(user));

      // Calculate expiration if expiresIn is provided
      const expiresAt = expiresIn ? Date.now() + expiresIn * 1000 : null;

      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      set({
        user,
        token,
        refreshToken: refreshToken || null,
        expiresAt,
        isAuthenticated: true,
      });
    } catch (error) {
      console.error(
        'Error saving session:',
        error instanceof Error ? error.message : 'Unknown error'
      );
      set({ error: 'Failed to save session' });
    }
  },

  updateAccessToken: async (token, expiresIn) => {
    try {
      await SecureStore.setItemAsync('auth_token', token);

      const expiresAt = Date.now() + expiresIn * 1000;

      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      set({ token, expiresAt });
    } catch (error) {
      console.error(
        'Error updating access token:',
        error instanceof Error ? error.message : 'Unknown error'
      );
      set({ error: 'Failed to update access token' });
    }
  },

  getRefreshToken: async () => {
    try {
      return await SecureStore.getItemAsync('refresh_token');
    } catch (error) {
      console.error(
        'Error getting refresh token:',
        error instanceof Error ? error.message : 'Unknown error'
      );
      return null;
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
      await SecureStore.deleteItemAsync('refresh_token');
      await AsyncStorage.removeItem('user');
      await AsyncStorage.removeItem('push_token');

      delete api.defaults.headers.common['Authorization'];

      set({
        user: null,
        token: null,
        refreshToken: null,
        expiresAt: null,
        pushToken: null,
        isAuthenticated: false,
      });
    } catch (error) {
      console.error('Error logging out:', error instanceof Error ? error.message : 'Unknown error');
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
      const refreshToken = await SecureStore.getItemAsync('refresh_token');

      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      set({ user, token, refreshToken, isAuthenticated: true, isLoading: false });
      return true;
    } catch (error) {
      console.error(
        'Error checking authentication:',
        error instanceof Error ? error.message : 'Unknown error'
      );
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
