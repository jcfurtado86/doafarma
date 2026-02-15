import React from 'react';
import { View, Text, StyleSheet, Pressable } from 'react-native';
import { MedicationOffering } from '@/types/medicationOffering';
import { Colors } from '@/constants/Colors';
import { a11y } from '@/utils/accessibility';

interface MedicationOfferingCardProps {
  offering: MedicationOffering;
  onEdit: () => void;
  onDelete: () => void;
}

export function MedicationOfferingCard({
  offering,
  onEdit,
  onDelete,
}: MedicationOfferingCardProps) {
  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR');
  };

  const isExpired = new Date(offering.expires_at) < new Date();

  return (
    <View style={[styles.card, isExpired && styles.expiredCard]}>
      <View style={styles.header}>
        <Text style={styles.drugName}>{offering.drug?.product_name || 'Medicamento'}</Text>
        {isExpired && <Text style={styles.expiredLabel}>VENCIDO</Text>}
      </View>

      <View style={styles.details}>
        <Text style={styles.detailText}>
          <Text style={styles.label}>Princípio Ativo:</Text> {offering.drug?.substance}
        </Text>
        <Text style={styles.detailText}>
          <Text style={styles.label}>Lote:</Text> {offering.lot_number}
        </Text>
        <Text style={styles.detailText}>
          <Text style={styles.label}>Quantidade:</Text> {offering.quantity} unidades
        </Text>
        <Text style={[styles.detailText, isExpired && styles.expiredText]}>
          <Text style={styles.label}>Vencimento:</Text> {formatDate(offering.expires_at)}
        </Text>
      </View>

      <View style={styles.actions}>
        <Pressable
          style={styles.editButton}
          onPress={onEdit}
          {...a11y.button('Editar medicamento')}
        >
          <Text style={styles.editButtonText}>Editar</Text>
        </Pressable>
        <Pressable
          style={styles.deleteButton}
          onPress={onDelete}
          {...a11y.button('Excluir medicamento')}
        >
          <Text style={styles.deleteButtonText}>Excluir</Text>
        </Pressable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
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
    backgroundColor: '#fef2f2',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  drugName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1f2937',
    flex: 1,
  },
  expiredLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#ef4444',
    backgroundColor: '#fecaca',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  details: {
    marginBottom: 16,
  },
  detailText: {
    fontSize: 14,
    color: '#6b7280',
    marginBottom: 4,
  },
  expiredText: {
    color: '#ef4444',
    fontWeight: 'bold',
  },
  label: {
    fontWeight: '600',
    color: '#374151',
  },
  actions: {
    flexDirection: 'row',
    gap: 12,
  },
  editButton: {
    flex: 1,
    backgroundColor: Colors.yellow_green_400,
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  editButtonText: {
    color: '#ffffff',
    fontWeight: '600',
  },
  deleteButton: {
    flex: 1,
    backgroundColor: '#ef4444',
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  deleteButtonText: {
    color: '#ffffff',
    fontWeight: '600',
  },
});
