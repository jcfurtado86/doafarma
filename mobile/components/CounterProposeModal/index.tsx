import React, { useState, useCallback, memo } from 'react';
import { View, Text } from 'react-native';
import { Controller } from 'react-hook-form';
import { toast } from '@/utils/toast';
import { Address, CounterProposeAppointmentData } from '@/types/medicationAppointment';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import {
  counterProposeSchema,
  CounterProposeFormData,
} from '@/utils/validation/appointmentValidation';
import { DateInput } from '@/components/DateInput';
import { TimeInput } from '@/components/TimeInput';
import { AddressSelector } from '@/components/AddressSelector';
import { Button, Modal } from '@/components/ui';
import { styles } from './styles';

const EMPTY_ADDRESSES: Address[] = [];

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

export const CounterProposeModal = memo(function CounterProposeModal({
  visible,
  onClose,
  onSubmit,
  currentDate,
  currentTime,
  currentAddress,
  userRole,
  doctorAddresses = EMPTY_ADDRESSES,
}: CounterProposeModalProps) {
  const [selectedAddressId, setSelectedAddressId] = useState<number | undefined>(
    currentAddress?.id
  );
  const [isSubmitting, setIsSubmitting] = useState(false);

  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useFeatureForm<CounterProposeFormData>({
    schema: counterProposeSchema,
    defaultValues: {
      scheduled_date: currentDate,
      scheduled_time: currentTime.substring(0, 5),
    },
  });

  const handleFormSubmit = useCallback(
    async (formData: CounterProposeFormData) => {
      setIsSubmitting(true);
      try {
        const data: CounterProposeAppointmentData = {
          scheduled_date: formData.scheduled_date,
          scheduled_time: formData.scheduled_time,
        };

        if (userRole === 'doctor' && selectedAddressId) {
          data.address_id = selectedAddressId;
        }

        await onSubmit(data);
        onClose();
      } catch {
        toast.error('Não foi possível fazer a contraproposta. Tente novamente.');
      } finally {
        setIsSubmitting(false);
      }
    },
    [selectedAddressId, userRole, onSubmit, onClose]
  );

  return (
    <Modal
      visible={visible}
      onClose={onClose}
      title="Contrapropor Horário"
      footer={
        <View style={styles.footerActions}>
          <View style={styles.footerButton}>
            <Button
              label="Cancelar"
              variant="secondary"
              size="md"
              onPress={onClose}
              disabled={isSubmitting}
            />
          </View>
          <View style={styles.footerButton}>
            <Button
              label={isSubmitting ? 'Enviando...' : 'Contrapropor'}
              variant="primary"
              size="md"
              onPress={handleSubmit(handleFormSubmit)}
              disabled={isSubmitting}
            />
          </View>
        </View>
      }
    >
      <View style={styles.section}>
        <Text style={styles.label}>Data</Text>
        <Controller
          control={control}
          name="scheduled_date"
          render={({ field }) => (
            <DateInput
              value={field.value}
              onChange={field.onChange}
              error={errors.scheduled_date?.message}
              placeholder="DD/MM/AAAA"
            />
          )}
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.label}>Horário</Text>
        <Controller
          control={control}
          name="scheduled_time"
          render={({ field }) => (
            <TimeInput
              value={field.value}
              onChange={field.onChange}
              error={errors.scheduled_time?.message}
              placeholder="HH:MM"
            />
          )}
        />
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
            Como paciente, você não pode alterar o local de retirada. Apenas o médico pode escolher
            o endereço.
          </Text>
          <Text style={styles.infoText}>Local atual: {currentAddress.location_name}</Text>
        </View>
      )}
    </Modal>
  );
});
