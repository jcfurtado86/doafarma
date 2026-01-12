import { Stack } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { Redirect } from 'expo-router';

export default function ReceptorLayout() {
  const { user } = useAuthStore();

  // Proteção de rota baseada em role
  if (user?.role !== 'receptor') {
    return <Redirect href="/(auth)/dashboard" />;
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      <Stack.Screen
        name="offering/[id]"
        options={{
          title: 'Detalhes do Medicamento',
          headerShown: true,
        }}
      />
      <Stack.Screen
        name="schedule/[requestId]"
        options={{
          title: 'Agendar Retirada',
          headerShown: true,
        }}
      />
    </Stack>
  );
}
