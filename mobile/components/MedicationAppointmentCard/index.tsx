import React, { useState } from 'react';
import { View, Text, StyleSheet, Pressable } from 'react-native';
import { MedicationAppointment, Address } from '@/types/medicationAppointment';
import { AppointmentStatusBadge } from '@/components/AppointmentStatusBadge';
import { CounterProposeModal } from '@/components/CounterProposeModal';
import { Colors } from '@/constants/Colors';

interface MedicationAppointmentCardProps {
  appointment: MedicationAppointment;
  variant: 'receptor' | 'doctor';
  onConfirmDelivery?: () => void;
  onAccept?: () => void;
  onCounterPropose?: (data: {
    scheduled_date: string;
    scheduled_time: string;
    address_id?: number;
  }) => void;
  doctorAddresses?: Address[];
}

export function MedicationAppointmentCard({
  appointment,
  variant,
  onConfirmDelivery,
  onAccept,
  onCounterPropose,
  doctorAddresses = [],
}: MedicationAppointmentCardProps) {
  const [showCounterProposeModal, setShowCounterProposeModal] = useState(false);

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR');
  };

  const formatTime = (timeString: string) => {
    return timeString.substring(0, 5);
  };

  const isScheduledDatePassed = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const scheduledDate = new Date(appointment.scheduled_date);
    scheduledDate.setHours(0, 0, 0, 0);
    return scheduledDate <= today;
  };

  // Check if user can respond (accept or counter-propose) to a proposal
  const canRespond = () => {
    if (appointment.status !== 'proposed') return false;
    // User can respond if they did NOT make the last proposal
    if (variant === 'doctor') {
      return appointment.proposed_by === 'receptor';
    } else {
      return appointment.proposed_by === 'doctor';
    }
  };

  const canConfirm = () => {
    if (appointment.status !== 'confirmed') return false;
    if (!isScheduledDatePassed()) return false;
    if (variant === 'receptor' && appointment.receptor_confirmed) return false;
    if (variant === 'doctor' && appointment.doctor_confirmed) return false;
    return true;
  };

  const getProposalStatus = () => {
    if (appointment.status !== 'proposed') return null;

    if (canRespond()) {
      return variant === 'doctor'
        ? 'Proposta do paciente - Responda abaixo'
        : 'Proposta do médico - Responda abaixo';
    } else {
      return variant === 'doctor'
        ? 'Aguardando resposta do paciente'
        : 'Aguardando resposta do médico';
    }
  };

  const getConfirmationStatus = () => {
    if (appointment.status === 'completed') {
      return 'Entrega concluída';
    }
    if (appointment.status !== 'confirmed') return null;

    if (variant === 'receptor') {
      if (appointment.receptor_confirmed) {
        return appointment.doctor_confirmed
          ? 'Aguardando...'
          : 'Você confirmou, aguardando o médico';
      }
      return appointment.doctor_confirmed ? 'Médico confirmou, confirme sua entrega' : null;
    }
    if (variant === 'doctor') {
      if (appointment.doctor_confirmed) {
        return appointment.receptor_confirmed
          ? 'Aguardando...'
          : 'Você confirmou, aguardando o paciente';
      }
      return appointment.receptor_confirmed ? 'Paciente confirmou, confirme a entrega' : null;
    }
    return null;
  };

  const offering = appointment.medication_request?.medication_offering;
  const receptor = appointment.medication_request?.receptor;
  const doctor = offering?.doctor;
  const proposalStatus = getProposalStatus();
  const confirmationStatus = getConfirmationStatus();

  return (
    <>
      <View style={styles.card}>
        <View style={styles.header}>
          <Text style={styles.drugName} numberOfLines={1}>
            {offering?.drug?.product_name || 'Medicamento'}
          </Text>
          <AppointmentStatusBadge status={appointment.status} />
        </View>

        <View style={styles.scheduleContainer}>
          <Text style={styles.scheduleDate}>{formatDate(appointment.scheduled_date)}</Text>
          <Text style={styles.scheduleTime}>{formatTime(appointment.scheduled_time)}</Text>
        </View>

        <View style={styles.details}>
          {appointment.address && (
            <View style={styles.addressContainer}>
              <Text style={styles.addressTitle}>Local de retirada:</Text>
              <Text style={styles.addressName}>{appointment.address.location_name}</Text>
              <Text style={styles.addressText}>{appointment.address.full_address}</Text>
              {appointment.address.complement && (
                <Text style={styles.addressText}>{appointment.address.complement}</Text>
              )}
            </View>
          )}

          <View style={styles.divider} />

          <Text style={styles.detailText}>
            <Text style={styles.label}>Medicamento:</Text> {offering?.drug?.product_name}
          </Text>
          <Text style={styles.detailText}>
            <Text style={styles.label}>Quantidade:</Text> {offering?.quantity} unidades
          </Text>

          {variant === 'doctor' && receptor && (
            <>
              <View style={styles.divider} />
              <Text style={styles.detailText}>
                <Text style={styles.label}>Paciente:</Text> {receptor.name}
              </Text>
              {receptor.phone_number && (
                <Text style={styles.detailText}>
                  <Text style={styles.label}>Telefone:</Text> {receptor.phone_number}
                </Text>
              )}
            </>
          )}

          {variant === 'receptor' && doctor && (
            <>
              <View style={styles.divider} />
              <Text style={styles.detailText}>
                <Text style={styles.label}>Médico:</Text> {doctor.name}
              </Text>
            </>
          )}
        </View>

        {/* Proposal Status */}
        {proposalStatus && (
          <View style={[styles.statusContainer, styles.proposalStatusContainer]}>
            <Text style={styles.proposalStatusText}>{proposalStatus}</Text>
          </View>
        )}

        {/* Negotiation Actions */}
        {canRespond() && (
          <View style={styles.actions}>
            <Pressable style={[styles.button, styles.acceptButton]} onPress={onAccept}>
              <Text style={styles.buttonText}>Aceitar</Text>
            </Pressable>
            <Pressable
              style={[styles.button, styles.counterProposeButton]}
              onPress={() => setShowCounterProposeModal(true)}
            >
              <Text style={styles.buttonText}>Contrapropor</Text>
            </Pressable>
          </View>
        )}

        {/* Confirmation Status */}
        {confirmationStatus && (
          <View style={styles.statusContainer}>
            <Text style={styles.statusText}>{confirmationStatus}</Text>
          </View>
        )}

        {/* Delivery Confirmation */}
        {canConfirm() && (
          <View style={styles.actions}>
            <Pressable style={[styles.button, styles.confirmButton]} onPress={onConfirmDelivery}>
              <Text style={styles.buttonText}>Confirmar Entrega</Text>
            </Pressable>
          </View>
        )}
      </View>

      {/* Counter Propose Modal */}
      <CounterProposeModal
        visible={showCounterProposeModal}
        onClose={() => setShowCounterProposeModal(false)}
        onSubmit={async (data) => {
          if (onCounterPropose) {
            await onCounterPropose(data);
          }
        }}
        currentDate={appointment.scheduled_date}
        currentTime={appointment.scheduled_time}
        currentAddress={appointment.address}
        userRole={variant}
        doctorAddresses={doctorAddresses}
      />
    </>
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
    borderLeftColor: '#3b82f6',
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
  scheduleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    padding: 12,
    borderRadius: 8,
    marginBottom: 12,
    gap: 16,
  },
  scheduleDate: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1d4ed8',
  },
  scheduleTime: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1d4ed8',
  },
  addressContainer: {
    marginBottom: 8,
  },
  addressTitle: {
    fontSize: 12,
    color: '#6b7280',
    marginBottom: 4,
  },
  addressName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1f2937',
  },
  addressText: {
    fontSize: 13,
    color: '#4b5563',
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
  statusContainer: {
    backgroundColor: '#fef3c7',
    padding: 10,
    borderRadius: 8,
    marginTop: 8,
  },
  statusText: {
    fontSize: 13,
    color: '#92400e',
    textAlign: 'center',
    fontWeight: '500',
  },
  proposalStatusContainer: {
    backgroundColor: '#fff7ed',
    borderLeftWidth: 3,
    borderLeftColor: '#f59e0b',
  },
  proposalStatusText: {
    fontSize: 13,
    color: '#92400e',
    textAlign: 'center',
    fontWeight: '600',
  },
  actions: {
    marginTop: 12,
    flexDirection: 'row',
    gap: 8,
  },
  button: {
    flex: 1,
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  acceptButton: {
    backgroundColor: Colors.yellow_green_500,
  },
  counterProposeButton: {
    backgroundColor: '#3b82f6',
  },
  confirmButton: {
    backgroundColor: Colors.yellow_green_500,
  },
  buttonText: {
    color: '#ffffff',
    fontWeight: '600',
    fontSize: 14,
  },
});
