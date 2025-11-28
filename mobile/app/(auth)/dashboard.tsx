import { View, Text, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import PrimaryButton from '@/components/PrimaryButton';
import SecondaryButton from '@/components/SecondaryButton';
import { Colors } from '@/constants/Colors';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';

export default function Dashboard() {
  const router = useRouter();
  const { user, logout } = useAuthStore();

  const handleLogout = async () => {
    await logout();
  };

  const handleMedicationOfferings = () => {
    router.push('/(auth)/medication-offerings');
  };

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Title>Bem-vindo, Dr. {user?.name}</Title>
        <Caption>Painel do Médico</Caption>
        <Text style={styles.email}>{user?.email}</Text>
      </View>

      <View style={styles.menu}>
        <PrimaryButton label="Minhas Ofertas" onPress={handleMedicationOfferings} />

        <SecondaryButton
          label="Nova Oferta"
          onPress={() => router.push('/(auth)/medication-offerings/create')}
        />
      </View>

      <View style={styles.footer}>
        <SecondaryButton label="Sair" onPress={handleLogout} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    backgroundColor: '#ffffff',
  },
  content: {
    flex: 1,
    paddingTop: 50,
  },
  menu: {
    gap: 16,
    marginBottom: 20,
  },
  email: {
    fontSize: 16,
    color: Colors.yellow_green_500,
    marginTop: 8,
  },
  footer: {
    marginBottom: 20,
  },
});
