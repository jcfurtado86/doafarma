import React from 'react';
import { Stack } from 'expo-router';

import { colors } from '@/theme/tokens';

export default function DoctorLayout() {
  return (
    <Stack
      screenOptions={{
        headerStyle: { backgroundColor: colors.surface },
        headerTitleStyle: { color: colors.textPrimary, fontWeight: '600' },
        headerTintColor: colors.primaryPressed,
        headerBackButtonDisplayMode: 'minimal',
      }}
    >
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      {/* Título vazio: essas telas já mostram título e subtítulo no corpo. */}
      <Stack.Screen name="offering/create" options={{ title: 'Nova Oferta', headerTitle: '' }} />
      <Stack.Screen
        name="offering/edit/[id]"
        options={{ title: 'Editar Oferta', headerTitle: '' }}
      />
      <Stack.Screen name="addresses/index" options={{ title: 'Meus Endereços', headerTitle: '' }} />
      <Stack.Screen name="addresses/create" options={{ title: 'Novo Endereço', headerTitle: '' }} />
      <Stack.Screen
        name="addresses/edit/[id]"
        options={{ title: 'Editar Endereço', headerTitle: '' }}
      />
      <Stack.Screen name="ratings" options={{ title: 'Minhas Avaliações' }} />
      <Stack.Screen name="settings" options={{ title: 'Configurações' }} />
    </Stack>
  );
}
