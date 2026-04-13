import React from 'react';
import { View, StyleSheet, ImageBackground } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { UserStatus } from '@/types/user';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Button } from '@/components/ui';
import { colors } from '@/theme/tokens';
import { Ionicons } from '@expo/vector-icons';

export default function PendingApprovalScreen() {
  const router = useRouter();
  const { logout, user } = useAuthStore();

  const handleLogout = async () => {
    await logout();
    router.replace('/');
  };

  const isRejected = user?.status === UserStatus.Rejected;

  return (
    <ImageBackground
      source={require('@/assets/images/img-fundo2.png')}
      style={styles.background}
      resizeMode="cover"
    >
      <View style={styles.container}>
        <View style={styles.iconContainer}>
          <Ionicons
            name={isRejected ? 'close-circle-outline' : 'time-outline'}
            size={80}
            color={isRejected ? colors.error : colors.primaryPressed}
          />
        </View>

        <View style={styles.content}>
          <Title>{isRejected ? 'Cadastro Rejeitado' : 'Aguardando Aprovação'}</Title>
          <Caption>
            {isRejected
              ? 'Seu cadastro foi rejeitado. Entre em contato com o suporte para mais informações.'
              : 'Seu cadastro está sendo analisado. Você receberá uma notificação quando for aprovado.'}
          </Caption>
        </View>

        <View style={styles.infoContainer}>
          <View style={styles.infoItem}>
            <Ionicons name="person-outline" size={20} color={colors.primaryPressed} />
            <Caption>{user?.name}</Caption>
          </View>
          <View style={styles.infoItem}>
            <Ionicons name="mail-outline" size={20} color={colors.primaryPressed} />
            <Caption>{user?.email}</Caption>
          </View>
        </View>

        <View style={styles.buttonContainer}>
          <Button variant="secondary" label="Sair" onPress={handleLogout} />
        </View>
      </View>
    </ImageBackground>
  );
}

const styles = StyleSheet.create({
  background: {
    flex: 1,
  },
  container: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
    alignItems: 'center',
  },
  iconContainer: {
    marginBottom: 24,
  },
  content: {
    alignItems: 'center',
    marginBottom: 32,
  },
  infoContainer: {
    backgroundColor: 'rgba(255, 255, 255, 0.9)',
    padding: 16,
    borderRadius: 12,
    width: '100%',
    gap: 12,
    marginBottom: 32,
  },
  infoItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  buttonContainer: {
    width: '100%',
  },
});
