import React, { useMemo } from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import { useLocalSearchParams } from 'expo-router';
import { Colors } from '@/constants/Colors';
import { MedicationOfferingSearchResult } from '@/types/medicationOffering';

export default function OfferingDetailScreen() {
  const { offering: offeringParam } = useLocalSearchParams<{ id: string; offering: string }>();

  const offering = useMemo<MedicationOfferingSearchResult | null>(() => {
    if (!offeringParam) return null;
    try {
      return JSON.parse(offeringParam);
    } catch {
      return null;
    }
  }, [offeringParam]);

  if (!offering) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>⚠️</Text>
        <Text style={styles.errorText}>Não foi possível carregar os detalhes do medicamento</Text>
      </View>
    );
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR');
  };

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
              {formatDate(offering.expires_at)}
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

      {/* Espaço para futuras ações */}
      <View style={styles.actionsPlaceholder}>
        <Text style={styles.actionsPlaceholderText}>Em breve: solicitar doação</Text>
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
  actionsPlaceholder: {
    marginTop: 8,
    padding: 16,
    backgroundColor: '#e5e7eb',
    borderRadius: 8,
    alignItems: 'center',
  },
  actionsPlaceholderText: {
    fontSize: 14,
    color: '#9ca3af',
    fontStyle: 'italic',
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
