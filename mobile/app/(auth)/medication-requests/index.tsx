import React, { useEffect, useState, useCallback } from 'react';
import { getErrorMessage } from '@/types/errors';
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
import { useMedicationRequestStore } from '@/stores/medicationRequestStore';
import { MedicationRequestCard } from '@/components/MedicationRequestCard';
import { FilterButton } from '@/components/FilterButton';
import { Colors } from '@/constants/Colors';
import { MedicationRequest, MedicationRequestStatus } from '@/types/medicationRequest';

type FilterOption = 'all' | MedicationRequestStatus;

export default function DoctorReceivedRequestsScreen() {
  const {
    receivedRequests,
    isLoading,
    error,
    fetchReceivedRequests,
    confirmRequest,
    rejectRequest,
    clearError,
  } = useMedicationRequestStore();

  const [filter, setFilter] = useState<FilterOption>('pending');

  const loadRequests = useCallback(() => {
    const status = filter === 'all' ? undefined : filter;
    fetchReceivedRequests(status);
  }, [filter, fetchReceivedRequests]);

  useEffect(() => {
    loadRequests();
  }, [loadRequests]);

  const handleConfirm = useCallback(
    (request: MedicationRequest) => {
      Alert.alert(
        'Confirmar Solicitação',
        `Confirmar a entrega do medicamento "${request.medication_offering?.drug?.product_name}" para ${request.receptor?.name}?`,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: 'Confirmar',
            onPress: async () => {
              try {
                await confirmRequest(request.id);
                toast.success('Solicitação confirmada com sucesso!');
              } catch (error: unknown) {
                Alert.alert(
                  'Erro',
                  getErrorMessage(error, 'Não foi possível confirmar a solicitação.')
                );
              }
            },
          },
        ]
      );
    },
    [confirmRequest]
  );

  const handleReject = useCallback(
    (request: MedicationRequest) => {
      Alert.alert(
        'Recusar Solicitação',
        `Recusar a solicitação do medicamento "${request.medication_offering?.drug?.product_name}" de ${request.receptor?.name}?`,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: 'Recusar',
            style: 'destructive',
            onPress: async () => {
              try {
                await rejectRequest(request.id);
                toast.success('Solicitação recusada.');
              } catch (error: unknown) {
                Alert.alert(
                  'Erro',
                  getErrorMessage(error, 'Não foi possível recusar a solicitação.')
                );
              }
            },
          },
        ]
      );
    },
    [rejectRequest]
  );

  const renderItem = useCallback(
    ({ item }: { item: MedicationRequest }) => (
      <MedicationRequestCard
        request={item}
        variant="doctor"
        onConfirm={() => handleConfirm(item)}
        onReject={() => handleReject(item)}
      />
    ),
    [handleConfirm, handleReject]
  );

  const renderEmpty = () => {
    if (isLoading) return null;
    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyIcon}>📋</Text>
        <Text style={styles.emptyTitle}>Nenhuma solicitação</Text>
        <Text style={styles.emptyText}>
          {filter === 'pending'
            ? 'Não há solicitações pendentes no momento.'
            : filter === 'confirmed'
              ? 'Não há solicitações confirmadas.'
              : filter === 'rejected'
                ? 'Não há solicitações recusadas.'
                : 'Você não recebeu nenhuma solicitação ainda.'}
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
            loadRequests();
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
          value="pending"
          label="Pendentes"
          isActive={filter === 'pending'}
          onPress={setFilter}
        />
        <FilterButton
          value="confirmed"
          label="Confirmadas"
          isActive={filter === 'confirmed'}
          onPress={setFilter}
        />
        <FilterButton
          value="rejected"
          label="Recusadas"
          isActive={filter === 'rejected'}
          onPress={setFilter}
        />
        <FilterButton value="all" label="Todas" isActive={filter === 'all'} onPress={setFilter} />
      </View>

      {isLoading && receivedRequests.length === 0 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={Colors.yellow_green_500} />
          <Text style={styles.loadingText}>Carregando solicitações...</Text>
        </View>
      ) : (
        <FlatList
          data={receivedRequests}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={isLoading}
              onRefresh={loadRequests}
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
