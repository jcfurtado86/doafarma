import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  ActivityIndicator,
  Alert,
  Pressable,
} from 'react-native';
import { useMedicationAppointmentStore } from '@/stores/medicationAppointmentStore';
import { MedicationAppointmentCard } from '@/components/MedicationAppointmentCard';
import { Colors } from '@/constants/Colors';
import { MedicationAppointment, MedicationAppointmentStatus } from '@/types/medicationAppointment';

type FilterOption = 'all' | MedicationAppointmentStatus;

export default function ReceptorAppointmentsScreen() {
  const {
    appointments,
    isLoading,
    error,
    fetchMyAppointments,
    confirmDeliveryReceptor,
    acceptAppointment,
    counterProposeAppointment,
    clearError,
  } = useMedicationAppointmentStore();

  const [filter, setFilter] = useState<FilterOption>('proposed');

  const loadAppointments = () => {
    const status = filter === 'all' ? undefined : filter;
    fetchMyAppointments(status);
  };

  useEffect(() => {
    loadAppointments();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filter]);

  const handleAccept = (appointment: MedicationAppointment) => {
    Alert.alert(
      'Aceitar Proposta',
      `Aceitar o agendamento para ${new Date(appointment.scheduled_date).toLocaleDateString('pt-BR')} às ${appointment.scheduled_time.substring(0, 5)}?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Aceitar',
          onPress: async () => {
            try {
              await acceptAppointment(appointment.id, false);
              Alert.alert('Sucesso', 'Agendamento confirmado!');
            } catch (err: any) {
              Alert.alert('Erro', err.message || 'Não foi possível aceitar o agendamento.');
            }
          },
        },
      ]
    );
  };

  const handleCounterPropose = async (
    appointmentId: number,
    data: { scheduled_date: string; scheduled_time: string; address_id?: number }
  ) => {
    try {
      await counterProposeAppointment(appointmentId, data, false);
      Alert.alert('Sucesso', 'Contraproposta enviada!');
    } catch (err: any) {
      throw err; // Let the modal handle the error display
    }
  };

  const handleConfirmDelivery = (appointment: MedicationAppointment) => {
    Alert.alert(
      'Confirmar Recebimento',
      `Confirmar que você recebeu o medicamento "${appointment.medication_request?.medication_offering?.drug?.product_name}"?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Confirmar',
          onPress: async () => {
            try {
              await confirmDeliveryReceptor(appointment.id);
              Alert.alert('Sucesso', 'Recebimento confirmado com sucesso!');
            } catch (err: any) {
              Alert.alert('Erro', err.message || 'Não foi possível confirmar o recebimento.');
            }
          },
        },
      ]
    );
  };

  const renderItem = ({ item }: { item: MedicationAppointment }) => (
    <MedicationAppointmentCard
      appointment={item}
      variant="receptor"
      onConfirmDelivery={() => handleConfirmDelivery(item)}
      onAccept={() => handleAccept(item)}
      onCounterPropose={(data) => handleCounterPropose(item.id, data)}
    />
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
        <Text style={styles.emptyHint}>
          Após uma solicitação ser confirmada pelo médico, você poderá agendar a retirada.
        </Text>
      </View>
    );
  };

  const FilterButton = ({ value, label }: { value: FilterOption; label: string }) => (
    <Pressable
      style={[styles.filterButton, filter === value && styles.filterButtonActive]}
      onPress={() => setFilter(value)}
    >
      <Text style={[styles.filterButtonText, filter === value && styles.filterButtonTextActive]}>
        {label}
      </Text>
    </Pressable>
  );

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
      {/* Filter Tabs */}
      <View style={styles.filterContainer}>
        <FilterButton value="proposed" label="Propostas" />
        <FilterButton value="confirmed" label="Confirmados" />
        <FilterButton value="completed" label="Concluídos" />
        <FilterButton value="all" label="Todos" />
      </View>

      {isLoading && appointments.length === 0 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={Colors.yellow_green_500} />
          <Text style={styles.loadingText}>Carregando agendamentos...</Text>
        </View>
      ) : (
        <FlatList
          data={appointments}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={isLoading}
              onRefresh={loadAppointments}
              colors={[Colors.yellow_green_500]}
              tintColor={Colors.yellow_green_500}
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
    backgroundColor: '#f3f4f6',
  },
  filterContainer: {
    flexDirection: 'row',
    padding: 12,
    backgroundColor: '#ffffff',
    gap: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  filterButton: {
    flex: 1,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
  },
  filterButtonActive: {
    backgroundColor: Colors.yellow_green_500,
  },
  filterButtonText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#6b7280',
  },
  filterButtonTextActive: {
    color: '#ffffff',
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
    color: '#6b7280',
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
    color: '#1f2937',
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 16,
    color: '#6b7280',
    textAlign: 'center',
    marginBottom: 8,
  },
  emptyHint: {
    fontSize: 14,
    color: '#9ca3af',
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
    color: '#ef4444',
    textAlign: 'center',
    marginBottom: 16,
  },
  retryText: {
    fontSize: 16,
    color: Colors.yellow_green_500,
    fontWeight: '600',
  },
});
