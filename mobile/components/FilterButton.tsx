import React from 'react';
import { Pressable, Text, StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

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
    backgroundColor: Colors.gray_100,
    alignItems: 'center',
  },
  filterButtonActive: {
    backgroundColor: Colors.yellow_green_500,
  },
  filterButtonText: {
    fontSize: 12,
    fontWeight: '600',
    color: Colors.gray_500,
  },
  filterButtonTextActive: {
    color: Colors.white,
  },
});
