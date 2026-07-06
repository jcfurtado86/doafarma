// Stub de compatibilidade da rota antiga (pré-#157). Remover 1 release
// depois que nenhuma sessão restaurada puder apontar pra cá.
import React, { useEffect } from 'react';

import { Redirect } from 'expo-router';

import { useAuthStore } from '@/stores/authStore';

export default function LegacyDashboardRedirect() {
  const { user, logout } = useAuthStore();
  const hasValidRole = user?.role === 'doctor' || user?.role === 'receptor';

  useEffect(() => {
    if (!hasValidRole) {
      void logout();
    }
  }, [hasValidRole, logout]);

  if (user?.role === 'doctor') {
    return <Redirect href="/(auth)/doctor/(tabs)/offerings" />;
  }
  if (user?.role === 'receptor') {
    return <Redirect href="/(auth)/receptor/(tabs)/search" />;
  }
  return null; // logout em curso; guard de auth do (auth) derruba pro login
}
