import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import {
  MedicationRequestStatus,
  REQUEST_STATUS_LABELS,
  REQUEST_STATUS_COLORS,
} from '@/types/medicationRequest';
import { a11y } from '@/utils/accessibility';

interface RequestStatusBadgeProps {
  status: MedicationRequestStatus;
}

export function RequestStatusBadge({ status }: RequestStatusBadgeProps) {
  const backgroundColor = `${REQUEST_STATUS_COLORS[status]}20`; // 20% opacity
  const textColor = REQUEST_STATUS_COLORS[status];
  const label = REQUEST_STATUS_LABELS[status];

  return (
    <View style={[styles.badge, { backgroundColor }]} {...a11y.badge(label)}>
      <Text style={[styles.text, { color: textColor }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: 12,
    fontWeight: '600',
  },
});
