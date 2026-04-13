import React, { useMemo, useState } from 'react';
import { getErrorMessage } from '@/types/errors';
import { View, Text, StyleSheet, ScrollView, Alert, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { colors } from '@/theme/tokens';
import { formatShortDate } from '@/utils/dateFormatters';
import { MedicationOfferingSearchResult } from '@/types/medicationOffering';
import { useMedicationRequestStore } from '@/stores/medicationRequestStore';
import { Button } from '@/components/ui';

export default function OfferingDetailScreen() {
  const { offering: offeringParam } = useLocalSearchParams<{ id: string; offering: string }>();
  const router = useRouter();
  const { createRequest } = useMedicationRequestStore();
  const [isRequesting, setIsRequesting] = useState(false);

  const offering = useMemo<MedicationOfferingSearchResult | null>(() => {
    if (!offeringParam) return null;
    try {
      return JSON.parse(offeringParam);
    } catch {
      return null;
    }
  }, [offeringParam]);

  const handleRequestMedication = async () => {
    if (!offering) return;

    Alert.alert(
      'Confirmar Solicitação',
      `Deseja solicitar o medicamento "${offering.drug.product_name}"?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Solicitar',
          onPress: async () => {
            setIsRequesting(true);
            try {
              await createRequest(offering.id);
              Alert.alert(
                'Sucesso',
                'Solicitação enviada com sucesso! Aguarde a confirmação do médico.',
                [
                  {
                    text: 'Ver Minhas Solicitações',
                    onPress: () => router.push('/(auth)/receptor/(tabs)/requests'),
                  },
                  { text: 'OK', onPress: () => router.back() },
                ]
              );
            } catch (error: unknown) {
              Alert.alert('Erro', getErrorMessage(error, 'Não foi possível enviar a solicitação.'));
            } finally {
              setIsRequesting(false);
            }
          },
        },
      ]
    );
  };

  const isAvailable = offering?.status === 'available';

  if (!offering) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>⚠️</Text>
        <Text style={styles.errorText}>Não foi possível carregar os detalhes do medicamento</Text>
      </View>
    );
  }

  const isExpired = new Date(offering.expires_at) < new Date();

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Seção: Medicamento */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>MEDICAMENTO</Text>
        <View style={[styles.card, isExpired && styles.expiredCard]}>
          <Text style={styles.drugName}>{offering.drug.product_name}</Text>
          <Text style={styles.drugSubstance}>{offering.drug.substance}</Text>
          <Text style={styles.drugDetail}>{offering.drug.presentation}</Text>
          <Text style={styles.drugDetail}>{offering.drug.laboratory}</Text>
          {isExpired && (
            <View style={styles.expiredBadge}>
              <Text style={styles.expiredBadgeText}>MEDICAMENTO VENCIDO</Text>
            </View>
          )}
        </View>
      </View>

      {/* Seção: Disponibilidade */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>DISPONIBILIDADE</Text>
        <View style={styles.card}>
          <View style={styles.detailRow}>
            <Text style={styles.detailLabel}>Quantidade:</Text>
            <Text style={styles.detailValue}>{offering.quantity} unidades</Text>
          </View>
          <View style={styles.detailRow}>
            <Text style={styles.detailLabel}>Lote:</Text>
            <Text style={styles.detailValue}>{offering.lot_number}</Text>
          </View>
          <View style={styles.detailRow}>
            <Text style={styles.detailLabel}>Validade:</Text>
            <Text style={[styles.detailValue, isExpired && styles.expiredText]}>
              {formatShortDate(offering.expires_at)}
            </Text>
          </View>
        </View>
      </View>

      {/* Seção: Oferecido por */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>OFERECIDO POR</Text>
        <View style={styles.card}>
          <Text style={styles.doctorName}>{offering.doctor.name}</Text>
        </View>
      </View>

      {/* Ação: Solicitar Medicamento */}
      <View style={styles.actionsContainer}>
        {isAvailable ? (
          isRequesting ? (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="small" color={colors.primaryPressed} />
              <Text style={styles.loadingText}>Enviando solicitação...</Text>
            </View>
          ) : (
            <Button label="Solicitar Medicamento" onPress={handleRequestMedication} />
          )
        ) : (
          <View style={styles.unavailableBadge}>
            <Text style={styles.unavailableText}>
              {offering?.status === 'reserved'
                ? 'Medicamento já reservado'
                : 'Medicamento indisponível'}
            </Text>
          </View>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  content: {
    padding: 16,
  },
  section: {
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
    letterSpacing: 0.5,
    marginBottom: 8,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 16,
    shadowColor: colors.black,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    borderLeftWidth: 4,
    borderLeftColor: colors.primary,
  },
  expiredCard: {
    borderLeftColor: colors.error,
  },
  drugName: {
    fontSize: 20,
    fontWeight: 'bold',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  drugSubstance: {
    fontSize: 16,
    color: colors.textPrimary,
    marginBottom: 8,
  },
  drugDetail: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 2,
  },
  expiredBadge: {
    marginTop: 12,
    backgroundColor: colors.errorSurface,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 4,
    alignSelf: 'flex-start',
  },
  expiredBadgeText: {
    fontSize: 12,
    fontWeight: 'bold',
    color: colors.error,
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: colors.background,
  },
  detailLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  detailValue: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  expiredText: {
    color: colors.error,
    fontWeight: '600',
  },
  doctorName: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  actionsContainer: {
    marginTop: 8,
    marginBottom: 20,
  },
  loadingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 16,
    backgroundColor: colors.background,
    borderRadius: 8,
    gap: 12,
  },
  loadingText: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  unavailableBadge: {
    padding: 16,
    backgroundColor: colors.errorSurface,
    borderRadius: 8,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.errorSurface,
  },
  unavailableText: {
    fontSize: 14,
    color: colors.error,
    fontWeight: '500',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
    backgroundColor: colors.background,
  },
  errorIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 16,
    color: colors.textSecondary,
    textAlign: 'center',
  },
});
