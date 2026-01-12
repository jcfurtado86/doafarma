import React from 'react';
import { View, Text, StyleSheet, Pressable } from 'react-native';
import { MedicationRequest } from '@/types/medicationRequest';
import { RequestStatusBadge } from '@/components/RequestStatusBadge';
import { Colors } from '@/constants/Colors';

interface MedicationRequestCardProps {
  request: MedicationRequest;
  variant: 'receptor' | 'doctor';
  onConfirm?: () => void;
  onReject?: () => void;
  onSchedule?: () => void;
  hasAppointment?: boolean;
}

export function MedicationRequestCard({
  request,
  variant,
  onConfirm,
  onReject,
  onSchedule,
  hasAppointment = false,
}: MedicationRequestCardProps) {
  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR');
  };

  const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const offering = request.medication_offering;
  const isPending = request.status === 'pending';

  return (
    <View style={styles.card}>
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
          {offering ? formatDate(offering.expires_at) : '-'}
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
          <Pressable style={[styles.button, styles.confirmButton]} onPress={onConfirm}>
            <Text style={styles.buttonText}>Confirmar</Text>
          </Pressable>
          <Pressable style={[styles.button, styles.rejectButton]} onPress={onReject}>
            <Text style={styles.buttonText}>Recusar</Text>
          </Pressable>
        </View>
      )}

      {variant === 'receptor' && request.status === 'confirmed' && !hasAppointment && (
        <View style={styles.actions}>
          <Pressable style={[styles.button, styles.scheduleButton]} onPress={onSchedule}>
            <Text style={styles.buttonText}>Agendar Retirada</Text>
          </Pressable>
        </View>
      )}

      {variant === 'receptor' && request.status === 'confirmed' && hasAppointment && (
        <View style={styles.scheduledBadge}>
          <Text style={styles.scheduledText}>Agendamento realizado</Text>
        </View>
      )}
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
    color: '#1f2937',
    flex: 1,
  },
  details: {
    marginBottom: 4,
  },
  detailText: {
    fontSize: 14,
    color: '#6b7280',
    marginBottom: 4,
  },
  label: {
    fontWeight: '600',
    color: '#374151',
  },
  divider: {
    height: 1,
    backgroundColor: '#e5e7eb',
    marginVertical: 8,
  },
  dateText: {
    fontSize: 12,
    color: '#9ca3af',
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
    backgroundColor: Colors.yellow_green_500,
  },
  rejectButton: {
    backgroundColor: '#ef4444',
  },
  scheduleButton: {
    backgroundColor: '#3b82f6',
  },
  buttonText: {
    color: '#ffffff',
    fontWeight: '600',
    fontSize: 14,
  },
  scheduledBadge: {
    marginTop: 12,
    backgroundColor: '#dbeafe',
    padding: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  scheduledText: {
    color: '#1d4ed8',
    fontWeight: '600',
    fontSize: 13,
  },
});
