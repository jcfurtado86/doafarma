import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '@/theme/tokens';

export default function ReceptorTabsLayout() {
  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: colors.primaryPressed,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
          height: 60,
          paddingBottom: 8,
          paddingTop: 8,
        },
        tabBarLabelStyle: {
          fontSize: 12,
          fontWeight: '600',
        },
        headerStyle: {
          backgroundColor: colors.surface,
        },
        headerTitleStyle: {
          color: colors.textPrimary,
          fontWeight: '600',
        },
      }}
    >
      <Tabs.Screen
        name="search"
        options={{
          title: 'Buscar',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="search-outline" size={size} color={color} />
          ),
          headerTitle: 'Buscar Medicamentos',
        }}
      />
      <Tabs.Screen
        name="requests"
        options={{
          title: 'Solicitações',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="document-text-outline" size={size} color={color} />
          ),
          headerTitle: 'Minhas Solicitações',
        }}
      />
      <Tabs.Screen
        name="appointments"
        options={{
          title: 'Agendamentos',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="calendar-outline" size={size} color={color} />
          ),
          headerTitle: 'Meus Agendamentos',
        }}
      />
      <Tabs.Screen
        name="history"
        options={{
          title: 'Histórico',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="time-outline" size={size} color={color} />
          ),
          headerTitle: 'Histórico de Medicamentos',
        }}
      />
    </Tabs>
  );
}
