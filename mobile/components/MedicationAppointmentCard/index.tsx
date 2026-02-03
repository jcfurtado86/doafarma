import React, { useState, useMemo, memo } from 'react';
import { View, Text, Pressable } from 'react-native';
import { MedicationAppointment, Address } from '@/types/medicationAppointment';
import { AppointmentStatusBadge } from '@/components/AppointmentStatusBadge';
import { CounterProposeModal } from '@/components/CounterProposeModal';
import {
  canRespond,
  canConfirm,
  getProposalStatus,
  getConfirmationStatus,
  formatAppointmentDate,
  formatAppointmentTime,
} from '@/utils/businessRules/appointmentRules';
import { styles } from './styles';

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

export const MedicationAppointmentCard = memo(function MedicationAppointmentCard({
  appointment,
  variant,
  onConfirmDelivery,
  onAccept,
  onCounterPropose,
  doctorAddresses = [],
}: MedicationAppointmentCardProps) {
  const [showCounterProposeModal, setShowCounterProposeModal] = useState(false);

  const offering = appointment.medication_request?.medication_offering;
  const receptor = appointment.medication_request?.receptor;
  const doctor = offering?.doctor;

  const proposalStatus = useMemo(
    () => getProposalStatus(appointment, variant),
    [appointment, variant]
  );
  const confirmationStatus = useMemo(
    () => getConfirmationStatus(appointment, variant),
    [appointment, variant]
  );
  const showActions = useMemo(() => canRespond(appointment, variant), [appointment, variant]);
  const showConfirm = useMemo(() => canConfirm(appointment, variant), [appointment, variant]);

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
          <Text style={styles.scheduleDate}>
            {formatAppointmentDate(appointment.scheduled_date)}
          </Text>
          <Text style={styles.scheduleTime}>
            {formatAppointmentTime(appointment.scheduled_time)}
          </Text>
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

        {proposalStatus && (
          <View style={[styles.statusContainer, styles.proposalStatusContainer]}>
            <Text style={styles.proposalStatusText}>{proposalStatus}</Text>
          </View>
        )}

        {showActions && (
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

        {confirmationStatus && (
          <View style={styles.statusContainer}>
            <Text style={styles.statusText}>{confirmationStatus}</Text>
          </View>
        )}

        {showConfirm && (
          <View style={styles.actions}>
            <Pressable style={[styles.button, styles.confirmButton]} onPress={onConfirmDelivery}>
              <Text style={styles.buttonText}>Confirmar Entrega</Text>
            </Pressable>
          </View>
        )}
      </View>

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
});
