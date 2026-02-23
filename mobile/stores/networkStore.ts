import { create } from 'zustand';

// Reconnect callbacks are module-level (outside reactive state)
// since they don't need to trigger re-renders
const reconnectCallbacks = new Set<() => void>();

interface NetworkStoreState {
  isConnected: boolean;
  isInternetReachable: boolean | null;
  setNetworkState: (isConnected: boolean, isInternetReachable: boolean | null) => void;
}

export const useNetworkStore = create<NetworkStoreState>((set, get) => ({
  isConnected: true, // optimistic: assume connected until first check
  isInternetReachable: null,

  setNetworkState: (isConnected, isInternetReachable) => {
    const wasConnected = get().isConnected;
    set({ isConnected, isInternetReachable });

    // offline → online transition: fire retry callbacks
    if (!wasConnected && isConnected) {
      reconnectCallbacks.forEach((cb) => {
        try {
          cb();
        } catch {}
      });
    }
  },
}));

/**
 * Registers a callback to execute when connection is restored.
 * Returns an unsubscribe function.
 */
export function onReconnect(callback: () => void): () => void {
  reconnectCallbacks.add(callback);
  return () => reconnectCallbacks.delete(callback);
}
