import { Stack } from 'expo-router';

export default function ReceptorLayout() {
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
