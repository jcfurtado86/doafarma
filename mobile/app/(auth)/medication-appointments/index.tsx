// Stub de compatibilidade da rota antiga (pré-#157). Alvo de push notification
// antiga. Remover 1 release depois do rollout.
import React from 'react';

import { Redirect } from 'expo-router';

import { useAuthStore } from '@/stores/authStore';

export default function LegacyAppointmentsRedirect() {
  const user = useAuthStore((state) => state.user);

  if (user?.role === 'doctor') {
    return <Redirect href="/(auth)/doctor/(tabs)/appointments" />;
  }
  if (user?.role === 'receptor') {
    return <Redirect href="/(auth)/receptor/(tabs)/appointments" />;
  }
  return <Redirect href="/(auth)/dashboard" />;
}
