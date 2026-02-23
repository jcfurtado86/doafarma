import NetInfo from '@react-native-community/netinfo';
import { useEffect } from 'react';
import { useNetworkStore } from '@/stores/networkStore';

/**
 * Initializes the NetInfo listener and syncs network state to the store.
 * Call this ONCE at the root of the app (app/_layout.tsx).
 * Components that need network state should read directly from useNetworkStore.
 */
export function useNetworkStatus() {
  const setNetworkState = useNetworkStore((state) => state.setNetworkState);

  useEffect(() => {
    // Get initial state (async)
    NetInfo.fetch().then((state) => {
      setNetworkState(state.isConnected ?? false, state.isInternetReachable);
    });

    // Continuous listener
    const unsubscribe = NetInfo.addEventListener((state) => {
      setNetworkState(state.isConnected ?? false, state.isInternetReachable);
    });

    return unsubscribe;
  }, [setNetworkState]);
}
