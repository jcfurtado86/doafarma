import React, { memo } from 'react';
import { View, Text, StyleSheet, Pressable } from 'react-native';
import { Address } from '@/types/address';
import { Badge, Card } from '@/components/ui';
import { colors } from '@/theme/tokens';
import { a11y } from '@/utils/accessibility';

interface AddressCardProps {
  address: Address;
  onEdit: () => void;
  onDelete: () => void;
  onSetDefault: () => void;
}

export const AddressCard = memo(function AddressCard({
  address,
  onEdit,
  onDelete,
  onSetDefault,
}: AddressCardProps) {
  return (
    <Card accent="primary" style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.label} numberOfLines={1}>
          {address.label}
        </Text>
        {address.is_default && <Badge label="Padrão" variant="success" size="sm" />}
      </View>

      <Text style={styles.addressText}>{address.formatted_address}</Text>

      <View style={styles.actions}>
        <Pressable style={styles.editButton} onPress={onEdit} {...a11y.button('Editar endereço')}>
          <Text style={styles.editButtonText}>Editar</Text>
        </Pressable>
        <Pressable
          style={styles.deleteButton}
          onPress={onDelete}
          {...a11y.button('Excluir endereço')}
        >
          <Text style={styles.deleteButtonText}>Excluir</Text>
        </Pressable>
      </View>

      {!address.is_default && (
        <Pressable
          style={styles.setDefaultButton}
          onPress={onSetDefault}
          {...a11y.button('Definir como endereço padrão')}
        >
          <Text style={styles.setDefaultButtonText}>Definir como padrão</Text>
        </Pressable>
      )}
    </Card>
  );
});

const styles = StyleSheet.create({
  container: {
    marginBottom: 12,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 8,
    marginBottom: 8,
  },
  label: {
    fontSize: 18,
    fontWeight: 'bold',
    color: colors.textPrimary,
    flexShrink: 1,
  },
  addressText: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 16,
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
  setDefaultButton: {
    marginTop: 12,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.primary,
    alignItems: 'center',
  },
  setDefaultButtonText: {
    color: colors.primary,
    fontWeight: '600',
  },
});
