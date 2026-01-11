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

  const isDoctor = user?.role === 'doctor';
  const isReceptor = user?.role === 'receptor';

  const handleLogout = async () => {
    await logout();
  };

  // Doctor navigation
  const handleMedicationOfferings = () => {
    router.push('/(auth)/medication-offerings');
  };

  const handleReceivedRequests = () => {
    router.push('/(auth)/medication-requests');
  };

  // Receptor navigation
  const handleSearchMedications = () => {
    router.push('/(auth)/receptor/search');
  };

  const handleMyRequests = () => {
    router.push('/(auth)/receptor/requests');
  };

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Title>
          Bem-vindo{isDoctor ? ', Dr.' : ','} {user?.name}
        </Title>
        <Caption>{isDoctor ? 'Painel do Médico' : 'Painel do Receptor'}</Caption>
        <Text style={styles.email}>{user?.email}</Text>
      </View>

      <View style={styles.menu}>
        {isDoctor && (
          <>
            <PrimaryButton label="Minhas Ofertas" onPress={handleMedicationOfferings} />
            <SecondaryButton
              label="Nova Oferta"
              onPress={() => router.push('/(auth)/medication-offerings/create')}
            />
            <SecondaryButton label="Solicitações Recebidas" onPress={handleReceivedRequests} />
          </>
        )}

        {isReceptor && (
          <>
            <PrimaryButton label="Buscar Medicamentos" onPress={handleSearchMedications} />
            <SecondaryButton label="Minhas Solicitações" onPress={handleMyRequests} />
          </>
        )}
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
