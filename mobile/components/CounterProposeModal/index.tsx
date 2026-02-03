import React, { useState } from 'react';
import { Modal, View, Text, Pressable, ScrollView } from 'react-native';
import { toast } from '@/utils/toast';
import { Address, CounterProposeAppointmentData } from '@/types/medicationAppointment';
import { ValidationMessages } from '@/constants/ValidationMessages';
import { isDateInPast, isValidDateFormat } from '@/utils/validation/dateHelpers';
import { isValidTimeFormat } from '@/utils/validation/timeHelpers';
import { DateInput } from '@/components/DateInput';
import { TimeInput } from '@/components/TimeInput';
import { AddressSelector } from '@/components/AddressSelector';
import { styles } from './styles';

interface CounterProposeModalProps {
  visible: boolean;
  onClose: () => void;
  onSubmit: (data: CounterProposeAppointmentData) => Promise<void>;
  currentDate: string;
  currentTime: string;
  currentAddress?: Address;
  userRole: 'doctor' | 'receptor';
  doctorAddresses?: Address[];
}

export function CounterProposeModal({
  visible,
  onClose,
  onSubmit,
  currentDate,
  currentTime,
  currentAddress,
  userRole,
  doctorAddresses = [],
}: CounterProposeModalProps) {
  const [date, setDate] = useState(currentDate);
  const [time, setTime] = useState(currentTime.substring(0, 5));
  const [selectedAddressId, setSelectedAddressId] = useState<number | undefined>(
    currentAddress?.id
  );
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async () => {
    if (!isValidDateFormat(date)) {
      toast.error(ValidationMessages.date.invalid);
      return;
    }

    if (!isValidTimeFormat(time)) {
      toast.error(ValidationMessages.time.invalid);
      return;
    }

    if (isDateInPast(date)) {
      toast.error(ValidationMessages.date.mustBeTodayOrFuture);
      return;
    }

    setIsSubmitting(true);
    try {
      const data: CounterProposeAppointmentData = {
        scheduled_date: date,
        scheduled_time: time,
      };

      if (userRole === 'doctor' && selectedAddressId) {
        data.address_id = selectedAddressId;
      }

      await onSubmit(data);
      onClose();
    } catch (error: any) {
      toast.error(error.message || 'Não foi possível fazer a contraproposta.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.modalContainer}>
          <View style={styles.header}>
            <Text style={styles.title}>Contrapropor Horário</Text>
            <Pressable onPress={onClose} style={styles.closeButton}>
              <Text style={styles.closeButtonText}>✕</Text>
            </Pressable>
          </View>

          <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
            <View style={styles.section}>
              <Text style={styles.label}>Data</Text>
              <DateInput value={date} onChange={setDate} placeholder="DD/MM/AAAA" />
            </View>

            <View style={styles.section}>
              <Text style={styles.label}>Horário</Text>
              <TimeInput value={time} onChange={setTime} placeholder="HH:MM" />
            </View>

            {userRole === 'doctor' && doctorAddresses.length > 0 && (
              <View style={styles.section}>
                <Text style={styles.label}>Local de Retirada (Opcional)</Text>
                <Text style={styles.hint}>
                  Você pode alterar o local de retirada para um dos seus endereços cadastrados.
                </Text>
                <AddressSelector
                  addresses={doctorAddresses}
                  selectedId={selectedAddressId}
                  onSelect={setSelectedAddressId}
                />
              </View>
            )}

            {userRole === 'receptor' && currentAddress && (
              <View style={styles.infoBox}>
                <Text style={styles.infoTitle}>Informação</Text>
                <Text style={styles.infoText}>
                  Como paciente, você não pode alterar o local de retirada. Apenas o médico pode
                  escolher o endereço.
                </Text>
                <Text style={styles.infoText}>Local atual: {currentAddress.location_name}</Text>
              </View>
            )}
          </ScrollView>

          <View style={styles.footer}>
            <Pressable style={styles.cancelButton} onPress={onClose} disabled={isSubmitting}>
              <Text style={styles.cancelButtonText}>Cancelar</Text>
            </Pressable>
            <Pressable
              style={[styles.submitButton, isSubmitting && styles.submitButtonDisabled]}
              onPress={handleSubmit}
              disabled={isSubmitting}
            >
              <Text style={styles.submitButtonText}>
                {isSubmitting ? 'Enviando...' : 'Contrapropor'}
              </Text>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}
