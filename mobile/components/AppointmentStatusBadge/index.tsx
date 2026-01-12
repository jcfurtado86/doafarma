import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import {
  MedicationAppointmentStatus,
  APPOINTMENT_STATUS_LABELS,
  APPOINTMENT_STATUS_COLORS,
} from '@/types/medicationAppointment';

interface AppointmentStatusBadgeProps {
  status: MedicationAppointmentStatus;
}

export function AppointmentStatusBadge({ status }: AppointmentStatusBadgeProps) {
  const backgroundColor = APPOINTMENT_STATUS_COLORS[status] || '#9ca3af';
  const label = APPOINTMENT_STATUS_LABELS[status] || status;

  return (
    <View style={[styles.badge, { backgroundColor }]}>
      <Text style={styles.text}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  text: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '600',
  },
});
