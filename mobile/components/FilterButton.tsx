import React from 'react';
import { Pressable, Text, StyleSheet } from 'react-native';
import { colors } from '@/theme/tokens';

interface FilterButtonProps<T extends string> {
  value: T;
  label: string;
  isActive: boolean;
  onPress: (value: T) => void;
}

export function FilterButton<T extends string>({
  value,
  label,
  isActive,
  onPress,
}: FilterButtonProps<T>) {
  return (
    <Pressable
      style={[styles.filterButton, isActive && styles.filterButtonActive]}
      onPress={() => onPress(value)}
      accessibilityRole="button"
      accessibilityLabel={`Filtro: ${label}`}
      accessibilityState={{ selected: isActive }}
    >
      <Text style={[styles.filterButtonText, isActive && styles.filterButtonTextActive]}>
        {label}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  filterButton: {
    flex: 1,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    backgroundColor: colors.background,
    alignItems: 'center',
  },
  filterButtonActive: {
    backgroundColor: colors.primaryPressed,
  },
  filterButtonText: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  filterButtonTextActive: {
    color: colors.textInverted,
  },
});
