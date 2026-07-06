import React from 'react';
import { Alert, Pressable, ScrollView, Text, View } from 'react-native';

import { Ionicons } from '@expo/vector-icons';

import { useAuthStore } from '@/stores/authStore';
import { colors } from '@/theme/tokens';
import { a11y } from '@/utils/accessibility';

import { styles } from './styles';

export function SettingsScreen() {
  const logout = useAuthStore((state) => state.logout);

  const handleLogoutPress = () => {
    Alert.alert('Sair', 'Deseja encerrar a sessão?', [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Sair', style: 'destructive', onPress: () => void logout() },
    ]);
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <View>
        <Text style={styles.sectionTitle}>Sessão</Text>
        <Pressable
          testID="settings-logout"
          {...a11y.button('Sair da conta')}
          style={styles.logoutButton}
          onPress={handleLogoutPress}
        >
          <Ionicons name="log-out-outline" size={20} color={colors.error} />
          <Text style={styles.logoutLabel}>Sair</Text>
        </Pressable>
      </View>
    </ScrollView>
  );
}
