import { View, Text, StyleSheet } from 'react-native';
import { useRouter, Href } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { Button } from '@/components/ui';
import { colors } from '@/theme/tokens';
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

  const handleReceivedAppointments = () => {
    router.push('/(auth)/medication-appointments');
  };

  const handleDonationHistory = () => {
    router.push('/(auth)/doctor/history');
  };

  const handleMyRatings = () => {
    router.push('/(auth)/doctor/ratings');
  };

  // Receptor navigation
  const handleSearchMedications = () => {
    router.push('/(auth)/receptor/(tabs)/search' as Href);
  };

  const handleMyRequests = () => {
    router.push('/(auth)/receptor/(tabs)/requests' as Href);
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
            <Button label="Minhas Ofertas" onPress={handleMedicationOfferings} />
            <Button
              variant="secondary"
              label="Nova Oferta"
              onPress={() => router.push('/(auth)/medication-offerings/create')}
            />
            <Button
              variant="secondary"
              label="Solicitações Recebidas"
              onPress={handleReceivedRequests}
            />
            <Button variant="secondary" label="Agendamentos" onPress={handleReceivedAppointments} />
            <Button
              variant="secondary"
              label="Histórico de Doações"
              onPress={handleDonationHistory}
            />
            <Button variant="secondary" label="Minhas Avaliações" onPress={handleMyRatings} />
          </>
        )}

        {isReceptor && (
          <>
            <Button label="Buscar Medicamentos" onPress={handleSearchMedications} />
            <Button variant="secondary" label="Minhas Solicitações" onPress={handleMyRequests} />
          </>
        )}
      </View>

      <View style={styles.footer}>
        <Button variant="secondary" label="Sair" onPress={handleLogout} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    backgroundColor: colors.surface,
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
    color: colors.primaryPressed,
    marginTop: 8,
  },
  footer: {
    marginBottom: 20,
  },
});
