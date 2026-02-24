import React, { useMemo, useState } from 'react';
import { getErrorMessage } from '@/types/errors';
import { View, Text, StyleSheet, ScrollView, Alert, ActivityIndicator } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Colors } from '@/constants/Colors';
import { formatShortDate } from '@/utils/dateFormatters';
import { MedicationOfferingSearchResult } from '@/types/medicationOffering';
import { useMedicationRequestStore } from '@/stores/medicationRequestStore';
import PrimaryButton from '@/components/PrimaryButton';

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
              <ActivityIndicator size="small" color={Colors.yellow_green_500} />
              <Text style={styles.loadingText}>Enviando solicitação...</Text>
            </View>
          ) : (
            <PrimaryButton label="Solicitar Medicamento" onPress={handleRequestMedication} />
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
    backgroundColor: '#f3f4f6',
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
    color: '#6b7280',
    letterSpacing: 0.5,
    marginBottom: 8,
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    borderLeftWidth: 4,
    borderLeftColor: Colors.yellow_green_400,
  },
  expiredCard: {
    borderLeftColor: '#ef4444',
  },
  drugName: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1f2937',
    marginBottom: 4,
  },
  drugSubstance: {
    fontSize: 16,
    color: '#374151',
    marginBottom: 8,
  },
  drugDetail: {
    fontSize: 14,
    color: '#6b7280',
    marginBottom: 2,
  },
  expiredBadge: {
    marginTop: 12,
    backgroundColor: '#fecaca',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 4,
    alignSelf: 'flex-start',
  },
  expiredBadgeText: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#ef4444',
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#f3f4f6',
  },
  detailLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  detailValue: {
    fontSize: 14,
    color: '#6b7280',
  },
  expiredText: {
    color: '#ef4444',
    fontWeight: '600',
  },
  doctorName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
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
    backgroundColor: '#f3f4f6',
    borderRadius: 8,
    gap: 12,
  },
  loadingText: {
    fontSize: 14,
    color: '#6b7280',
  },
  unavailableBadge: {
    padding: 16,
    backgroundColor: '#fef2f2',
    borderRadius: 8,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#fecaca',
  },
  unavailableText: {
    fontSize: 14,
    color: '#ef4444',
    fontWeight: '500',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
    backgroundColor: '#f3f4f6',
  },
  errorIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 16,
    color: '#6b7280',
    textAlign: 'center',
  },
});
