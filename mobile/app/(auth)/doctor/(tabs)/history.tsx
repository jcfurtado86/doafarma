import React, { useCallback } from 'react';
import { View, Text, StyleSheet, FlatList, RefreshControl, ActivityIndicator } from 'react-native';
import { colors } from '@/theme/tokens';
import { Pagination } from '@/constants/Pagination';
import { formatLongDate } from '@/utils/dateFormatters';
import { MedicationAppointment } from '@/types/medicationAppointment';
import { ListFooterLoader } from '@/components/ListFooterLoader';
import { usePagination } from '@/hooks/usePagination';
import { medicationAppointmentService } from '@/services/medicationAppointmentService';

export default function DoctorHistoryScreen() {
  const fetchDoctorHistoryFn = useCallback(async (page: number) => {
    return medicationAppointmentService.listDoctorHistoryPaginated(page);
  }, []);

  const {
    items: donationHistory,
    isLoading,
    isLoadingMore,
    error,
    totalItems,
    loadMore,
    refresh,
  } = usePagination<MedicationAppointment>({
    fetchFn: fetchDoctorHistoryFn,
  });

  const renderItem = ({ item }: { item: MedicationAppointment }) => {
    const drug = item.medication_request?.medication_offering?.drug;
    const receptor = item.medication_request?.receptor;

    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Text style={styles.medicationName}>{drug?.product_name || 'Medicamento'}</Text>
          <View style={styles.statusBadge}>
            <Text style={styles.statusText}>Doado</Text>
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
                {formatLongDate(item.medication_request.medication_offering.expires_at)}
              </Text>
            </View>
          )}

          {receptor?.name && (
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Beneficiário:</Text>
              <Text style={styles.infoValue}>{receptor.name}</Text>
            </View>
          )}

          <View style={styles.divider} />

          <View style={styles.dateRow}>
            <Text style={styles.dateIcon}>📅</Text>
            <Text style={styles.dateText}>Doado em {formatLongDate(item.updated_at)}</Text>
          </View>
        </View>
      </View>
    );
  };

  const renderEmpty = () => {
    if (isLoading) return null;
    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyIcon}>🎁</Text>
        <Text style={styles.emptyTitle}>Nenhuma doação realizada</Text>
        <Text style={styles.emptyText}>
          Quando você concluir doações de medicamentos, elas aparecerão aqui.
        </Text>
        <Text style={styles.emptyHint}>
          Cadastre ofertas de medicamentos para começar a ajudar quem precisa.
        </Text>
      </View>
    );
  };

  if (error && donationHistory.length === 0) {
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
        <Text style={styles.headerTitle}>Seu histórico de doações</Text>
        <Text style={styles.headerSubtitle}>
          {totalItems} {totalItems === 1 ? 'doação realizada' : 'doações realizadas'}
        </Text>
      </View>

      {isLoading && donationHistory.length === 0 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.primaryPressed} />
          <Text style={styles.loadingText}>Carregando histórico...</Text>
        </View>
      ) : (
        <FlatList
          data={donationHistory}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={false}
              onRefresh={refresh}
              colors={[colors.primaryPressed]}
              tintColor={colors.primaryPressed}
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
    backgroundColor: colors.background,
  },
  header: {
    backgroundColor: colors.surface,
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  headerSubtitle: {
    fontSize: 14,
    color: colors.textSecondary,
    marginTop: 4,
  },
  listContent: {
    padding: 16,
    flexGrow: 1,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    marginBottom: 12,
    shadowColor: colors.black,
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
    backgroundColor: colors.infoLight,
    borderBottomWidth: 1,
    borderBottomColor: colors.infoLight,
  },
  medicationName: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
    flex: 1,
    marginRight: 8,
  },
  statusBadge: {
    backgroundColor: colors.info,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    color: colors.textInverted,
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
    color: colors.textSecondary,
    width: 100,
  },
  infoValue: {
    fontSize: 14,
    color: colors.textPrimary,
    flex: 1,
  },
  divider: {
    height: 1,
    backgroundColor: colors.border,
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
    color: colors.textSecondary,
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
    marginBottom: 8,
  },
  emptyHint: {
    fontSize: 14,
    color: colors.textMuted,
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
