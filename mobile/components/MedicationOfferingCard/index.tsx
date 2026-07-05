import React, { memo, useMemo } from 'react';
import { View, Text, StyleSheet, Pressable } from 'react-native';
import { MedicationOffering } from '@/types/medicationOffering';
import { Card } from '@/components/ui';
import { colors } from '@/theme/tokens';
import { a11y } from '@/utils/accessibility';
import { formatShortDate } from '@/utils/dateFormatters';

interface MedicationOfferingCardProps {
  offering: MedicationOffering;
  onEdit: () => void;
  onDelete: () => void;
}

export const MedicationOfferingCard = memo(function MedicationOfferingCard({
  offering,
  onEdit,
  onDelete,
}: MedicationOfferingCardProps) {
  const isExpired = useMemo(
    () => new Date(offering.expires_at) < new Date(),
    [offering.expires_at]
  );

  return (
    <Card
      accent={isExpired ? 'error' : 'primary'}
      style={[styles.container, isExpired && styles.expiredBackground]}
    >
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
          <Text style={styles.label}>Vencimento:</Text> {formatShortDate(offering.expires_at)}
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
    </Card>
  );
});

const styles = StyleSheet.create({
  container: {
    marginBottom: 12,
  },
  expiredBackground: {
    backgroundColor: colors.errorSurface,
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
    color: colors.textPrimary,
    flex: 1,
  },
  expiredLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: colors.error,
    backgroundColor: colors.errorSurface,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  details: {
    marginBottom: 16,
  },
  detailText: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 4,
  },
  expiredText: {
    color: colors.error,
    fontWeight: 'bold',
  },
  label: {
    fontWeight: '600',
    color: colors.textPrimary,
  },
  actions: {
    flexDirection: 'row',
    gap: 12,
  },
  editButton: {
    flex: 1,
    backgroundColor: colors.primary,
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  editButtonText: {
    color: colors.textInverted,
    fontWeight: '600',
  },
  deleteButton: {
    flex: 1,
    backgroundColor: colors.error,
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  deleteButtonText: {
    color: colors.textInverted,
    fontWeight: '600',
  },
});
