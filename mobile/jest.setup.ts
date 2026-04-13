/**
 * Jest Setup File
 * Configures mocks for React Native / Expo modules
 */

// Mock expo-secure-store
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

// Mock @react-native-async-storage/async-storage
jest.mock('@react-native-async-storage/async-storage', () => ({
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
}));

// Mock expo-router
jest.mock('expo-router', () => ({
  router: {
    replace: jest.fn(),
    push: jest.fn(),
    back: jest.fn(),
  },
}));

// Mock react-native-toast-message
jest.mock('react-native-toast-message', () => ({
  show: jest.fn(),
  hide: jest.fn(),
}));

// Mock @react-native-community/netinfo
jest.mock('@react-native-community/netinfo', () => ({
  fetch: jest.fn().mockResolvedValue({ isConnected: true, isInternetReachable: true }),
  addEventListener: jest.fn().mockReturnValue(jest.fn()), // returns unsubscribe fn
}));

// Mock push notification service
jest.mock('@/services/pushNotificationService', () => ({
  registerForPushNotificationsAsync: jest.fn(),
  registerPushTokenWithBackend: jest.fn(),
  removePushTokenFromBackend: jest.fn(),
}));

// Mock network store (used by <Button disableWhenOffline /> and services/api.ts)
jest.mock('@/stores/networkStore', () => {
  const state = {
    isConnected: true,
    isInternetReachable: null as boolean | null,
    setNetworkState: jest.fn(),
  };
  const useNetworkStore = jest.fn((selector?: (s: typeof state) => unknown) =>
    selector ? selector(state) : state
  ) as jest.Mock & {
    getState: jest.Mock;
    setState: jest.Mock;
    subscribe: jest.Mock;
  };
  useNetworkStore.getState = jest.fn(() => state);
  useNetworkStore.setState = jest.fn();
  useNetworkStore.subscribe = jest.fn(() => jest.fn());
  return {
    useNetworkStore,
    onReconnect: jest.fn(() => jest.fn()),
  };
});

// Silence console.error in tests (optional - can be removed if you want to see errors)
// const originalError = console.error;
// beforeAll(() => {
//   console.error = jest.fn();
// });
// afterAll(() => {
//   console.error = originalError;
// });
