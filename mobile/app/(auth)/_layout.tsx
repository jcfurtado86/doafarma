import { colors } from '@/theme/tokens';
import { useAuthStore } from '@/stores/authStore';
import { Redirect, Stack } from 'expo-router';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

export default function AuthLayout() {
  const { isAuthenticated, isLoading } = useAuthStore();

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

  return <Stack />;
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
