import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { toast } from '@/utils/toast';
import { getErrorMessage } from '@/types/errors';
import { useMedicationAppointmentStore } from '@/stores/medicationAppointmentStore';
import { MedicationAppointmentCard } from '@/components/MedicationAppointmentCard';
import { FilterButton } from '@/components/FilterButton';
import { colors } from '@/theme/tokens';
import { MedicationAppointment, MedicationAppointmentStatus } from '@/types/medicationAppointment';

type FilterOption = 'all' | MedicationAppointmentStatus;

export default function DoctorReceivedAppointmentsScreen() {
  const {
    receivedAppointments,
    isLoading,
    error,
    fetchReceivedAppointments,
    confirmDeliveryDoctor,
    acceptAppointment,
    counterProposeAppointment,
    clearError,
  } = useMedicationAppointmentStore();

  const [filter, setFilter] = useState<FilterOption>('proposed');

  const loadAppointments = useCallback(() => {
    const status = filter === 'all' ? undefined : filter;
    fetchReceivedAppointments(status);
  }, [filter, fetchReceivedAppointments]);

  useEffect(() => {
    loadAppointments();
  }, [loadAppointments]);

  const handleAccept = useCallback(
    (appointment: MedicationAppointment) => {
      Alert.alert(
        'Aceitar Proposta',
        `Aceitar o agendamento para ${new Date(appointment.scheduled_date).toLocaleDateString('pt-BR')} às ${appointment.scheduled_time.substring(0, 5)}?`,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: 'Aceitar',
            onPress: async () => {
              try {
                await acceptAppointment(appointment.id, true);
                toast.success('Agendamento confirmado!');
              } catch (err: unknown) {
                Alert.alert(
                  'Erro',
                  getErrorMessage(err, 'Não foi possível aceitar o agendamento.')
                );
              }
            },
          },
        ]
      );
    },
    [acceptAppointment]
  );

  const handleCounterPropose = useCallback(
    async (
      appointmentId: number,
      data: { scheduled_date: string; scheduled_time: string; address_id?: number }
    ) => {
      try {
        await counterProposeAppointment(appointmentId, data, true);
        toast.success('Contraproposta enviada!');
      } catch (err: unknown) {
        throw err; // Let the modal handle the error display
      }
    },
    [counterProposeAppointment]
  );

  const handleConfirmDelivery = useCallback(
    (appointment: MedicationAppointment) => {
      Alert.alert(
        'Confirmar Entrega',
        `Confirmar que o medicamento "${appointment.medication_request?.medication_offering?.drug?.product_name}" foi entregue?`,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: 'Confirmar',
            onPress: async () => {
              try {
                await confirmDeliveryDoctor(appointment.id);
                toast.success('Entrega confirmada com sucesso!');
              } catch (err: unknown) {
                Alert.alert('Erro', getErrorMessage(err, 'Não foi possível confirmar a entrega.'));
              }
            },
          },
        ]
      );
    },
    [confirmDeliveryDoctor]
  );

  const renderItem = useCallback(
    ({ item }: { item: MedicationAppointment }) => (
      <MedicationAppointmentCard
        appointment={item}
        variant="doctor"
        onConfirmDelivery={() => handleConfirmDelivery(item)}
        onAccept={() => handleAccept(item)}
        onCounterPropose={(data) => handleCounterPropose(item.id, data)}
        doctorAddresses={[]} // TODO: Pass doctor's addresses from user store
      />
    ),
    [handleConfirmDelivery, handleAccept, handleCounterPropose]
  );

  const renderEmpty = () => {
    if (isLoading) return null;
    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyIcon}>📅</Text>
        <Text style={styles.emptyTitle}>Nenhum agendamento</Text>
        <Text style={styles.emptyText}>
          {filter === 'proposed'
            ? 'Não há propostas de agendamento no momento.'
            : filter === 'confirmed'
              ? 'Não há agendamentos confirmados.'
              : filter === 'completed'
                ? 'Não há agendamentos concluídos.'
                : 'Você não possui nenhum agendamento ainda.'}
        </Text>
      </View>
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
            loadAppointments();
          }}
        >
          Tentar novamente
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.filterContainer}>
        <FilterButton
          value="proposed"
          label="Propostas"
          isActive={filter === 'proposed'}
          onPress={setFilter}
        />
        <FilterButton
          value="confirmed"
          label="Confirmados"
          isActive={filter === 'confirmed'}
          onPress={setFilter}
        />
        <FilterButton
          value="completed"
          label="Concluídos"
          isActive={filter === 'completed'}
          onPress={setFilter}
        />
        <FilterButton value="all" label="Todos" isActive={filter === 'all'} onPress={setFilter} />
      </View>

      {isLoading && receivedAppointments.length === 0 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.primaryPressed} />
          <Text style={styles.loadingText}>Carregando agendamentos...</Text>
        </View>
      ) : (
        <FlatList
          data={receivedAppointments}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={isLoading}
              onRefresh={loadAppointments}
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
  filterContainer: {
    flexDirection: 'row',
    padding: 12,
    backgroundColor: colors.surface,
    gap: 8,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
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
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 32,
  },
  emptyIcon: {
    fontSize: 64,
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: colors.textPrimary,
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 16,
    color: colors.textSecondary,
    textAlign: 'center',
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
