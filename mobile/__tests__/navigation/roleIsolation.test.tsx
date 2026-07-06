import React from 'react';
import { Text } from 'react-native';
import { Stack } from 'expo-router';
import { renderRouter, screen, waitFor } from 'expo-router/testing-library';
import AuthLayout from '@/app/(auth)/_layout';
import LegacyDashboardRedirect from '@/app/(auth)/dashboard';
import { useAuthStore, type User } from '@/stores/authStore';

const doctor: User = {
  id: 1,
  name: 'Ana Souza',
  email: 'ana@doafarma.com',
  role: 'doctor',
  status: 'approved',
};
const receptor: User = { ...doctor, id: 2, name: 'Bruno Lima', role: 'receptor' };

// Passthrough layouts for the `doctor`/`receptor` segments. Without an
// intermediate `_layout`, renderRouter hoists the leaf route to the (auth)
// level (screen name `doctor/(tabs)/offerings`), so the Stack.Protected guard
// on the `doctor` group screen never matches. These stubs reproduce the real
// app's nesting, where `doctor`/`receptor` ARE group screens the guard filters.
const routes = {
  index: () => <Text testID="screen-login">login</Text>,
  '(auth)/_layout': AuthLayout,
  '(auth)/dashboard': LegacyDashboardRedirect,
  '(auth)/pending-approval': () => <Text testID="screen-pending">pending</Text>,
  '(auth)/medication-appointments/index': () => <Text testID="screen-appts">appts</Text>,
  '(auth)/doctor/_layout': () => <Stack screenOptions={{ headerShown: false }} />,
  '(auth)/doctor/(tabs)/offerings': () => <Text testID="screen-doctor-offerings">ofertas</Text>,
  '(auth)/receptor/_layout': () => <Stack screenOptions={{ headerShown: false }} />,
  '(auth)/receptor/(tabs)/search': () => <Text testID="screen-receptor-search">buscar</Text>,
};

function setAuth(user: User | null) {
  useAuthStore.setState({ user, isAuthenticated: user !== null, isLoading: false });
}

describe('role isolation', () => {
  it('doctor reaches the doctor area', async () => {
    setAuth(doctor);
    renderRouter(routes, { initialUrl: '/(auth)/doctor/(tabs)/offerings' });
    expect(await screen.findByTestId('screen-doctor-offerings')).toBeTruthy();
  });

  it('receptor deep-linking into the doctor area never renders doctor content', async () => {
    setAuth(receptor);
    renderRouter(routes, { initialUrl: '/(auth)/doctor/(tabs)/offerings' });
    // The guard removes the doctor screen from the (auth) navigator, so the
    // receptor is anchored to their own area instead.
    expect(await screen.findByTestId('screen-receptor-search')).toBeTruthy();
    expect(screen.queryByTestId('screen-doctor-offerings')).toBeNull();
  });

  it('doctor deep-linking into the receptor area never renders receptor content', async () => {
    setAuth(doctor);
    renderRouter(routes, { initialUrl: '/(auth)/receptor/(tabs)/search' });
    expect(await screen.findByTestId('screen-doctor-offerings')).toBeTruthy();
    expect(screen.queryByTestId('screen-receptor-search')).toBeNull();
  });

  it('unauthenticated user is redirected to login', async () => {
    setAuth(null);
    renderRouter(routes, { initialUrl: '/(auth)/doctor/(tabs)/offerings' });
    expect(await screen.findByTestId('screen-login')).toBeTruthy();
  });

  it('user with invalid role hitting the legacy dashboard is logged out', async () => {
    const logoutMock = jest.fn().mockResolvedValue(undefined);
    useAuthStore.setState({
      user: { ...doctor, role: 'ghost' as User['role'] },
      isAuthenticated: true,
      isLoading: false,
      logout: logoutMock,
    });
    renderRouter(routes, { initialUrl: '/(auth)/dashboard' });
    await waitFor(() => expect(logoutMock).toHaveBeenCalled());
  });
});
