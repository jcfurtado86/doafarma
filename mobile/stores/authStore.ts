import api, { resetApiState } from '@/services/api';
import {
  registerForPushNotificationsAsync,
  registerPushTokenWithBackend,
  removePushTokenFromBackend,
} from '@/services/pushNotificationService';
import { STORAGE_KEYS } from '@/config/storage';
import { getErrorMessage } from '@/utils/error';
import { logger } from '@/utils/logger';
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';

export interface User {
  id: number;
  name: string;
  email: string;
  phone_number?: string;
  role: 'doctor' | 'receptor';
  status: 'pending' | 'approved' | 'rejected';
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

const MS_PER_SECOND = 1000;

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
      resetApiState();

      await SecureStore.setItemAsync(STORAGE_KEYS.AUTH_TOKEN, token);

      if (refreshToken) {
        await SecureStore.setItemAsync(STORAGE_KEYS.REFRESH_TOKEN, refreshToken);
      }

      await AsyncStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user));

      const expiresAt = expiresIn ? Date.now() + expiresIn * MS_PER_SECOND : null;

      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      set({
        user,
        token,
        refreshToken: refreshToken || null,
        expiresAt,
        isAuthenticated: true,
      });
    } catch (error) {
      logger.error('Error saving session:', getErrorMessage(error));
      set({ error: 'Failed to save session' });
    }
  },

  updateAccessToken: async (token, expiresIn) => {
    try {
      await SecureStore.setItemAsync(STORAGE_KEYS.AUTH_TOKEN, token);

      const expiresAt = Date.now() + expiresIn * MS_PER_SECOND;

      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      set({ token, expiresAt });
    } catch (error) {
      logger.error('Error updating access token:', getErrorMessage(error));
      set({ error: 'Failed to update access token' });
    }
  },

  getRefreshToken: async () => {
    try {
      return await SecureStore.getItemAsync(STORAGE_KEYS.REFRESH_TOKEN);
    } catch (error) {
      logger.error('Error getting refresh token:', getErrorMessage(error));
      return null;
    }
  },

  logout: async () => {
    try {
      const { pushToken } = get();

      // Fire-and-forget to avoid blocking logout when tokens are invalid
      if (pushToken) {
        removePushTokenFromBackend(pushToken).catch(() => {});
      }

      await SecureStore.deleteItemAsync(STORAGE_KEYS.AUTH_TOKEN);
      await SecureStore.deleteItemAsync(STORAGE_KEYS.REFRESH_TOKEN);
      await AsyncStorage.removeItem(STORAGE_KEYS.USER);
      await AsyncStorage.removeItem(STORAGE_KEYS.PUSH_TOKEN);

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
      logger.error('Error logging out:', getErrorMessage(error));
      delete api.defaults.headers.common['Authorization'];
      set({
        user: null,
        token: null,
        refreshToken: null,
        expiresAt: null,
        pushToken: null,
        isAuthenticated: false,
        error: 'Failed to log out',
      });
    }
  },

  checkAuth: async () => {
    try {
      set({ isLoading: true });

      const token = await SecureStore.getItemAsync(STORAGE_KEYS.AUTH_TOKEN);
      if (!token) {
        set({ isLoading: false });
        return false;
      }

      const userJson = await AsyncStorage.getItem(STORAGE_KEYS.USER);
      if (!userJson) {
        set({ isLoading: false });
        return false;
      }

      const user = JSON.parse(userJson);
      const refreshToken = await SecureStore.getItemAsync(STORAGE_KEYS.REFRESH_TOKEN);

      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      set({ user, token, refreshToken, isAuthenticated: true, isLoading: false });
      return true;
    } catch (error) {
      logger.error('Error checking authentication:', getErrorMessage(error));
      set({ isLoading: false, error: 'Failed to check authentication' });
      return false;
    }
  },

  clearError: () => set({ error: null }),

  setupPushNotifications: async () => {
    try {
      const expoPushToken = await registerForPushNotificationsAsync();

      if (expoPushToken) {
        await registerPushTokenWithBackend(expoPushToken);
        await AsyncStorage.setItem(STORAGE_KEYS.PUSH_TOKEN, expoPushToken);
        set({ pushToken: expoPushToken });
      }
    } catch (error) {
      logger.error('Error setting up push notifications:', getErrorMessage(error));
    }
  },
}));
