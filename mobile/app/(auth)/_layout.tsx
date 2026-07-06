import { colors } from '@/theme/tokens';
import { useAuthStore } from '@/stores/authStore';
import { Redirect, Stack } from 'expo-router';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

export default function AuthLayout() {
  const { isAuthenticated, isLoading, user } = useAuthStore();

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Carregando...</Text>
      </View>
    );
  }

  if (!isAuthenticated) {
    return <Redirect href="/" />;
  }

  return (
    <Stack>
      <Stack.Protected guard={user?.role === 'doctor'}>
        <Stack.Screen name="doctor" options={{ headerShown: false }} />
      </Stack.Protected>
      <Stack.Protected guard={user?.role === 'receptor'}>
        <Stack.Screen name="receptor" options={{ headerShown: false }} />
      </Stack.Protected>
      <Stack.Screen name="pending-approval" />
      <Stack.Screen name="dashboard" options={{ headerShown: false }} />
      <Stack.Screen name="medication-appointments/index" options={{ headerShown: false }} />
    </Stack>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    color: colors.primary,
    fontSize: 16,
  },
});
