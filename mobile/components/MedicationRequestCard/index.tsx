import React, { memo, useMemo } from 'react';
import { View, Text, StyleSheet, Pressable } from 'react-native';
import { MedicationRequest } from '@/types/medicationRequest';
import { RequestStatusBadge } from '@/components/RequestStatusBadge';
import { Card } from '@/components/ui';
import { colors } from '@/theme/tokens';
import { a11y } from '@/utils/accessibility';
import { formatShortDate, formatDateTime } from '@/utils/dateFormatters';

interface MedicationRequestCardProps {
  request: MedicationRequest;
  variant: 'receptor' | 'doctor';
  onConfirm?: () => void;
  onReject?: () => void;
  onSchedule?: () => void;
  hasAppointment?: boolean;
}

export const MedicationRequestCard = memo(function MedicationRequestCard({
  request,
  variant,
  onConfirm,
  onReject,
  onSchedule,
  hasAppointment = false,
}: MedicationRequestCardProps) {
  const offering = request.medication_offering;
  const isPending = useMemo(() => request.status === 'pending', [request.status]);

  return (
    <Card accent="primary" style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.drugName} numberOfLines={1}>
          {offering?.drug?.product_name || 'Medicamento'}
        </Text>
        <RequestStatusBadge status={request.status} />
      </View>

      <View style={styles.details}>
        <Text style={styles.detailText}>
          <Text style={styles.label}>Princípio Ativo:</Text> {offering?.drug?.substance}
        </Text>
        <Text style={styles.detailText}>
          <Text style={styles.label}>Quantidade:</Text> {offering?.quantity} unidades
        </Text>
        <Text style={styles.detailText}>
          <Text style={styles.label}>Validade:</Text>{' '}
          {offering ? formatShortDate(offering.expires_at) : '-'}
        </Text>

        {variant === 'doctor' && request.receptor && (
          <>
            <View style={styles.divider} />
            <Text style={styles.detailText}>
              <Text style={styles.label}>Solicitante:</Text> {request.receptor.name}
            </Text>
            {request.receptor.phone_number && (
              <Text style={styles.detailText}>
                <Text style={styles.label}>Telefone:</Text> {request.receptor.phone_number}
              </Text>
            )}
          </>
        )}

        {variant === 'receptor' && offering?.doctor && (
          <>
            <View style={styles.divider} />
            <Text style={styles.detailText}>
              <Text style={styles.label}>Médico:</Text> {offering.doctor.name}
            </Text>
          </>
        )}

        <Text style={styles.dateText}>Solicitado em: {formatDateTime(request.created_at)}</Text>
      </View>

      {variant === 'doctor' && isPending && (
        <View style={styles.actions}>
          <Pressable
            style={[styles.button, styles.confirmButton]}
            onPress={onConfirm}
            {...a11y.button('Confirmar solicitação')}
          >
            <Text style={styles.buttonText}>Confirmar</Text>
          </Pressable>
          <Pressable
            style={[styles.button, styles.rejectButton]}
            onPress={onReject}
            {...a11y.button('Recusar solicitação')}
          >
            <Text style={styles.buttonText}>Recusar</Text>
          </Pressable>
        </View>
      )}

      {variant === 'receptor' && request.status === 'confirmed' && !hasAppointment && (
        <View style={styles.actions}>
          <Pressable
            style={[styles.button, styles.scheduleButton]}
            onPress={onSchedule}
            {...a11y.button('Agendar retirada do medicamento')}
          >
            <Text style={styles.buttonText}>Agendar Retirada</Text>
          </Pressable>
        </View>
      )}

      {variant === 'receptor' && request.status === 'confirmed' && hasAppointment && (
        <View style={styles.scheduledBadge}>
          <Text style={styles.scheduledText}>Agendamento realizado</Text>
        </View>
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
    marginBottom: 12,
    gap: 12,
  },
  drugName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: colors.textPrimary,
    flex: 1,
  },
  details: {
    marginBottom: 4,
  },
  detailText: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 4,
  },
  label: {
    fontWeight: '600',
    color: colors.textPrimary,
  },
  divider: {
    height: 1,
    backgroundColor: colors.border,
    marginVertical: 8,
  },
  dateText: {
    fontSize: 12,
    color: colors.textMuted,
    marginTop: 8,
  },
  actions: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 12,
  },
  button: {
    flex: 1,
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  confirmButton: {
    backgroundColor: colors.primaryPressed,
  },
  rejectButton: {
    backgroundColor: colors.error,
  },
  scheduleButton: {
    backgroundColor: colors.info,
  },
  buttonText: {
    color: colors.textInverted,
    fontWeight: '600',
    fontSize: 14,
  },
  scheduledBadge: {
    marginTop: 12,
    backgroundColor: colors.infoLight,
    padding: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  scheduledText: {
    color: colors.info,
    fontWeight: '600',
    fontSize: 13,
  },
});
