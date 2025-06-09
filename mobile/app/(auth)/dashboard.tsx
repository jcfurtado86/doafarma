import { View, Text, StyleSheet } from 'react-native';
import { useAuthStore } from '@/stores/authStore';
import PrimaryButton from '@/components/PrimaryButton';
import { Colors } from '@/constants/Colors';

export default function Dashboard() {
  const { user, logout } = useAuthStore();

  const handleLogout = async () => {
    await logout();
  };

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.welcome}>Bem-vindo, Dr. {user?.name}</Text>
        <Text style={styles.subtitle}>Painel do Médico</Text>
        <Text style={styles.email}>{user?.email}</Text>
      </View>

      <View style={styles.footer}>
        <PrimaryButton label="Sair" onPress={handleLogout} />
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
  welcome: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 18,
    color: '#666',
    marginBottom: 16,
  },
  email: {
    fontSize: 16,
    color: Colors.yellow_green_500,
  },
  footer: {
    marginBottom: 20,
  },
});
