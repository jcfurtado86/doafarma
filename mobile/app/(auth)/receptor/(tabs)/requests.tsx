import React, { useEffect, useMemo } from 'react';
import { View, Text, StyleSheet, FlatList, RefreshControl, ActivityIndicator } from 'react-native';
import { useRouter } from 'expo-router';
import { useMedicationRequestStore } from '@/stores/medicationRequestStore';
import { useMedicationAppointmentStore } from '@/stores/medicationAppointmentStore';
import { MedicationRequestCard } from '@/components/MedicationRequestCard';
import { EmptyState } from '@/components/ui';
import { colors } from '@/theme/tokens';
import { MedicationRequest } from '@/types/medicationRequest';

export default function ReceptorRequestsScreen() {
  const router = useRouter();
  const { requests, isLoading, error, fetchMyRequests, clearError } = useMedicationRequestStore();
  const { appointments, fetchMyAppointments } = useMedicationAppointmentStore();

  useEffect(() => {
    fetchMyRequests();
    fetchMyAppointments();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Set de request IDs que já têm agendamento
  const requestIdsWithAppointment = useMemo(() => {
    return new Set(appointments.map((a) => a.medication_request?.id).filter(Boolean));
  }, [appointments]);

  const handleSchedule = (requestId: number) => {
    router.push(`/(auth)/receptor/schedule/${requestId}`);
  };

  const handleRefresh = () => {
    fetchMyRequests();
    fetchMyAppointments();
  };

  const renderItem = ({ item }: { item: MedicationRequest }) => (
    <MedicationRequestCard
      request={item}
      variant="receptor"
      hasAppointment={requestIdsWithAppointment.has(item.id)}
      onSchedule={() => handleSchedule(item.id)}
    />
  );

  const renderEmpty = () => {
    if (isLoading) return null;
    return (
      <EmptyState
        icon="file-tray-outline"
        title="Nenhuma solicitação feita"
        description="Você ainda não fez nenhuma solicitação. Busque medicamentos disponíveis e solicite os que você precisa."
      />
    );
  };

  if (error) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>⚠️</Text>
        <Text style={styles.errorText}>{error}</Text>
        <Text
          style={styles.retryText}
          onPress={() => {
            clearError();
            fetchMyRequests();
          }}
        >
          Tentar novamente
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {isLoading && requests.length === 0 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.primaryPressed} />
          <Text style={styles.loadingText}>Carregando solicitações...</Text>
        </View>
      ) : (
        <FlatList
          data={requests}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={isLoading}
              onRefresh={handleRefresh}
              colors={[colors.primaryPressed]}
              tintColor={colors.primaryPressed}
            />
          }
          ListEmptyComponent={renderEmpty}
          showsVerticalScrollIndicator={false}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  listContent: {
    padding: 16,
    flexGrow: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: 16,
  },
  loadingText: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  errorIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 16,
    color: colors.error,
    textAlign: 'center',
    marginBottom: 16,
  },
  retryText: {
    fontSize: 16,
    color: colors.primaryPressed,
    fontWeight: '600',
  },
});
