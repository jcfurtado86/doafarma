import { useNetworkStore, onReconnect } from '@/stores/networkStore';

describe('networkStore', () => {
  beforeEach(() => {
    // Reset store to initial state before each test
    useNetworkStore.setState({ isConnected: true, isInternetReachable: null });
  });

  describe('initial state', () => {
    it('starts with isConnected: true (optimistic)', () => {
      const { isConnected } = useNetworkStore.getState();
      expect(isConnected).toBe(true);
    });

    it('starts with isInternetReachable: null', () => {
      const { isInternetReachable } = useNetworkStore.getState();
      expect(isInternetReachable).toBeNull();
    });
  });

  describe('setNetworkState', () => {
    it('updates isConnected and isInternetReachable', () => {
      const { setNetworkState } = useNetworkStore.getState();
      setNetworkState(false, false);

      const state = useNetworkStore.getState();
      expect(state.isConnected).toBe(false);
      expect(state.isInternetReachable).toBe(false);
    });

    it('updates to connected with reachable internet', () => {
      const { setNetworkState } = useNetworkStore.getState();
      setNetworkState(false, false);
      setNetworkState(true, true);

      const state = useNetworkStore.getState();
      expect(state.isConnected).toBe(true);
      expect(state.isInternetReachable).toBe(true);
    });

    it('handles null isInternetReachable', () => {
      const { setNetworkState } = useNetworkStore.getState();
      setNetworkState(true, null);

      const state = useNetworkStore.getState();
      expect(state.isInternetReachable).toBeNull();
    });
  });

  describe('onReconnect callbacks', () => {
    it('fires callback on offline → online transition', () => {
      const callback = jest.fn();
      const unsubscribe = onReconnect(callback);

      const { setNetworkState } = useNetworkStore.getState();
      setNetworkState(false, false); // go offline
      setNetworkState(true, true); // go back online

      expect(callback).toHaveBeenCalledTimes(1);

      unsubscribe();
    });

    it('does not fire callback if already connected', () => {
      const callback = jest.fn();
      const unsubscribe = onReconnect(callback);

      const { setNetworkState } = useNetworkStore.getState();
      setNetworkState(true, true); // already connected → connected

      expect(callback).not.toHaveBeenCalled();

      unsubscribe();
    });

    it('does not fire callback when going offline', () => {
      const callback = jest.fn();
      const unsubscribe = onReconnect(callback);

      const { setNetworkState } = useNetworkStore.getState();
      setNetworkState(false, false); // go offline

      expect(callback).not.toHaveBeenCalled();

      unsubscribe();
    });

    it('unsubscribe removes callback so it is not called again', () => {
      const callback = jest.fn();
      const unsubscribe = onReconnect(callback);

      const { setNetworkState } = useNetworkStore.getState();
      setNetworkState(false, false); // go offline
      unsubscribe(); // remove callback
      setNetworkState(true, true); // go back online

      expect(callback).not.toHaveBeenCalled();
    });

    it('fires multiple registered callbacks on reconnect', () => {
      const cb1 = jest.fn();
      const cb2 = jest.fn();
      const unsub1 = onReconnect(cb1);
      const unsub2 = onReconnect(cb2);

      const { setNetworkState } = useNetworkStore.getState();
      setNetworkState(false, false);
      setNetworkState(true, true);

      expect(cb1).toHaveBeenCalledTimes(1);
      expect(cb2).toHaveBeenCalledTimes(1);

      unsub1();
      unsub2();
    });
  });
});
