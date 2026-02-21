import React, { useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  ActivityIndicator,
  TouchableOpacity,
} from 'react-native';
import { router } from 'expo-router';
import { Colors } from '@/constants/Colors';
import { Pagination } from '@/constants/Pagination';
import { MedicationAppointment } from '@/types/medicationAppointment';
import { ListFooterLoader } from '@/components/ListFooterLoader';
import { usePagination } from '@/hooks/usePagination';
import { medicationAppointmentService } from '@/services/medicationAppointmentService';

export default function ReceptorHistoryScreen() {
  const fetchHistoryFn = useCallback(async (page: number) => {
    return medicationAppointmentService.listHistoryPaginated(page);
  }, []);

  const {
    items: history,
    isLoading,
    isLoadingMore,
    error,
    totalItems,
    loadMore,
    refresh,
  } = usePagination<MedicationAppointment>({
    fetchFn: fetchHistoryFn,
  });

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    });
  };

  const handleRateDoctor = (appointment: MedicationAppointment) => {
    const drug = appointment.medication_request?.medication_offering?.drug;
    const doctor = appointment.medication_request?.medication_offering?.doctor;

    router.push({
      pathname: '/receptor/rate/[appointmentId]',
      params: {
        appointmentId: appointment.id.toString(),
        doctorName: doctor?.name || '',
        medicationName: drug?.product_name || '',
      },
    });
  };

  const renderItem = ({ item }: { item: MedicationAppointment }) => {
    const drug = item.medication_request?.medication_offering?.drug;
    const doctor = item.medication_request?.medication_offering?.doctor;

    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Text style={styles.medicationName}>{drug?.product_name || 'Medicamento'}</Text>
          <View style={styles.statusBadge}>
            <Text style={styles.statusText}>Recebido</Text>
          </View>
        </View>

        <View style={styles.cardContent}>
          {drug?.substance && (
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Substância:</Text>
              <Text style={styles.infoValue}>{drug.substance}</Text>
            </View>
          )}

          {drug?.laboratory && (
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Laboratório:</Text>
              <Text style={styles.infoValue}>{drug.laboratory}</Text>
            </View>
          )}

          {item.medication_request?.medication_offering?.lot_number && (
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Lote:</Text>
              <Text style={styles.infoValue}>
                {item.medication_request.medication_offering.lot_number}
              </Text>
            </View>
          )}

          {item.medication_request?.medication_offering?.expires_at && (
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Validade:</Text>
              <Text style={styles.infoValue}>
                {formatDate(item.medication_request.medication_offering.expires_at)}
              </Text>
            </View>
          )}

          {doctor?.name && (
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Doador:</Text>
              <Text style={styles.infoValue}>{doctor.name}</Text>
            </View>
          )}

          <View style={styles.divider} />

          <View style={styles.dateRow}>
            <Text style={styles.dateIcon}>📅</Text>
            <Text style={styles.dateText}>Recebido em {formatDate(item.updated_at)}</Text>
          </View>

          {/* Rate Button */}
          <TouchableOpacity
            style={styles.rateButton}
            onPress={() => handleRateDoctor(item)}
            activeOpacity={0.8}
          >
            <Text style={styles.rateButtonIcon}>⭐</Text>
            <Text style={styles.rateButtonText}>Avaliar Doador</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  };

  const renderEmpty = () => {
    if (isLoading) return null;
    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyIcon}>📦</Text>
        <Text style={styles.emptyTitle}>Nenhum medicamento recebido</Text>
        <Text style={styles.emptyText}>
          Quando você receber medicamentos, eles aparecerão aqui.
        </Text>
        <Text style={styles.emptyHint}>
          Busque medicamentos disponíveis e faça solicitações para começar.
        </Text>
      </View>
    );
  };

  if (error && history.length === 0) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>⚠️</Text>
        <Text style={styles.errorText}>{error}</Text>
        <Text style={styles.retryText} onPress={refresh}>
          Tentar novamente
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header info */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Seu histórico de medicamentos</Text>
        <Text style={styles.headerSubtitle}>
          {totalItems} {totalItems === 1 ? 'medicamento recebido' : 'medicamentos recebidos'}
        </Text>
      </View>

      {isLoading && history.length === 0 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={Colors.yellow_green_500} />
          <Text style={styles.loadingText}>Carregando histórico...</Text>
        </View>
      ) : (
        <FlatList
          data={history}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={false}
              onRefresh={refresh}
              colors={[Colors.yellow_green_500]}
              tintColor={Colors.yellow_green_500}
            />
          }
          onEndReached={loadMore}
          onEndReachedThreshold={Pagination.END_REACHED_THRESHOLD}
          ListFooterComponent={<ListFooterLoader isLoading={isLoadingMore} />}
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
  header: {
    backgroundColor: '#ffffff',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 4,
  },
  listContent: {
    padding: 16,
    flexGrow: 1,
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
    overflow: 'hidden',
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#f0fdf4',
    borderBottomWidth: 1,
    borderBottomColor: '#dcfce7',
  },
  medicationName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
    flex: 1,
    marginRight: 8,
  },
  statusBadge: {
    backgroundColor: '#22c55e',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '600',
  },
  cardContent: {
    padding: 16,
  },
  infoRow: {
    flexDirection: 'row',
    marginBottom: 8,
  },
  infoLabel: {
    fontSize: 14,
    color: '#6b7280',
    width: 100,
  },
  infoValue: {
    fontSize: 14,
    color: '#1f2937',
    flex: 1,
  },
  divider: {
    height: 1,
    backgroundColor: '#e5e7eb',
    marginVertical: 12,
  },
  dateRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  dateIcon: {
    fontSize: 16,
    marginRight: 8,
  },
  dateText: {
    fontSize: 14,
    color: '#6b7280',
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
  rateButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: Colors.yellow_green_500,
    borderRadius: 8,
    paddingVertical: 12,
    paddingHorizontal: 16,
    marginTop: 12,
    gap: 8,
  },
  rateButtonIcon: {
    fontSize: 16,
  },
  rateButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '600',
  },
});
