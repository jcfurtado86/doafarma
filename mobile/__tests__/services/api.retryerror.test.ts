/**
 * Tests for retry error scenarios
 *
 * Edge case: What happens when refresh succeeds but the retry fails?
 * The queued requests got the new token, but the original request fails.
 */

import MockAdapter from 'axios-mock-adapter';
import * as SecureStore from 'expo-secure-store';

import api, { resetApiState } from '@/services/api';

describe('Retry failure after successful refresh', () => {
  let mockAxios: MockAdapter;

  beforeEach(() => {
    jest.clearAllMocks();
    resetApiState();
    mockAxios = new MockAdapter(api);

    (SecureStore.getItemAsync as jest.Mock).mockResolvedValue(null);
    (SecureStore.setItemAsync as jest.Mock).mockResolvedValue(undefined);
  });

  afterEach(() => {
    mockAxios.restore();
  });

  it('should propagate error if retry fails after successful refresh', async () => {
    const expiredToken = '1|expired';
    const refreshToken = '2|refresh';
    const newToken = '3|new';

    (SecureStore.getItemAsync as jest.Mock)
      .mockResolvedValueOnce(expiredToken) // First request
      .mockResolvedValueOnce(refreshToken) // Refresh token
      .mockResolvedValueOnce(newToken); // Retry request

    // First call: 401
    mockAxios.onGet('/v1/test').replyOnce(401);

    // Refresh: success
    mockAxios.onPost('/v1/auth/refresh').replyOnce(200, {
      data: { access_token: newToken, expires_in: 3600 },
    });

    // Retry: 500 (server error)
    mockAxios.onGet('/v1/test').replyOnce(500, { message: 'Server error' });

    // Should throw the 500 error, not hang
    await expect(api.get('/v1/test')).rejects.toMatchObject({
      response: expect.objectContaining({
        status: 500,
      }),
    });
  });

  it('should not retry if already retried', async () => {
    const expiredToken = '1|expired';
    const refreshToken = '2|refresh';
    const newToken = '3|new';

    (SecureStore.getItemAsync as jest.Mock)
      .mockResolvedValueOnce(expiredToken)
      .mockResolvedValueOnce(refreshToken)
      .mockResolvedValueOnce(newToken);

    // First: 401
    mockAxios.onGet('/v1/test').replyOnce(401);

    // Refresh: success
    mockAxios.onPost('/v1/auth/refresh').replyOnce(200, {
      data: { access_token: newToken, expires_in: 3600 },
    });

    // Retry: another 401 (shouldn't retry again)
    mockAxios.onGet('/v1/test').replyOnce(401);

    // Should reject without infinite loop
    await expect(api.get('/v1/test')).rejects.toBeDefined();

    // Only one refresh should have been called
    const refreshCalls = mockAxios.history.post.filter((req) => req.url === '/v1/auth/refresh');
    expect(refreshCalls.length).toBe(1);
  });

  it('should handle network error during retry', async () => {
    const expiredToken = '1|expired';
    const refreshToken = '2|refresh';
    const newToken = '3|new';

    (SecureStore.getItemAsync as jest.Mock)
      .mockResolvedValueOnce(expiredToken)
      .mockResolvedValueOnce(refreshToken)
      .mockResolvedValueOnce(newToken);

    // First: 401
    mockAxios.onGet('/v1/test').replyOnce(401);

    // Refresh: success
    mockAxios.onPost('/v1/auth/refresh').replyOnce(200, {
      data: { access_token: newToken, expires_in: 3600 },
    });

    // Retry: network error
    mockAxios.onGet('/v1/test').networkError();

    // Should reject with network error
    await expect(api.get('/v1/test')).rejects.toThrow();
  });
});

describe('Concurrent requests during refresh', () => {
  let mockAxios: MockAdapter;

  beforeEach(() => {
    jest.clearAllMocks();
    resetApiState();
    mockAxios = new MockAdapter(api);
  });

  afterEach(() => {
    mockAxios.restore();
  });

  it('should handle multiple 401s with single refresh', async () => {
    const expiredToken = '1|expired';
    const refreshToken = '2|refresh';
    const newToken = '3|new';

    // Multiple calls will need tokens
    (SecureStore.getItemAsync as jest.Mock).mockImplementation(async (key: string) => {
      if (key === 'auth_token') return expiredToken;
      if (key === 'refresh_token') return refreshToken;
      return null;
    });

    // All initial requests return 401
    mockAxios.onGet('/v1/test1').replyOnce(401);
    mockAxios.onGet('/v1/test2').replyOnce(401);
    mockAxios.onGet('/v1/test3').replyOnce(401);

    // Refresh returns new token
    mockAxios.onPost('/v1/auth/refresh').replyOnce(200, {
      data: { access_token: newToken, expires_in: 3600 },
    });

    // Retries succeed
    mockAxios.onGet('/v1/test1').replyOnce(200, { data: 'test1' });
    mockAxios.onGet('/v1/test2').replyOnce(200, { data: 'test2' });
    mockAxios.onGet('/v1/test3').replyOnce(200, { data: 'test3' });

    // Make concurrent requests
    const results = await Promise.allSettled([
      api.get('/v1/test1'),
      api.get('/v1/test2'),
      api.get('/v1/test3'),
    ]);

    // At least one should succeed (the one that triggered refresh)
    const fulfilled = results.filter((r) => r.status === 'fulfilled');
    expect(fulfilled.length).toBeGreaterThanOrEqual(1);

    // Only ONE refresh call should have been made
    const refreshCalls = mockAxios.history.post.filter((req) => req.url === '/v1/auth/refresh');
    expect(refreshCalls.length).toBe(1);
  });
});
