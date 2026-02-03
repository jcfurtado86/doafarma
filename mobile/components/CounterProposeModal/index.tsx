import React, { useState } from 'react';
import { Modal, View, Text, StyleSheet, Pressable, ScrollView, TextInput } from 'react-native';
import { toast } from '@/utils/toast';
import { Address, CounterProposeAppointmentData } from '@/types/medicationAppointment';
import { Colors } from '@/constants/Colors';
import { ValidationMessages } from '@/constants/ValidationMessages';
import {
  formatDateInput,
  convertDateToAPI,
  convertDateFromAPI,
  isDateInPast,
  isValidDateFormat,
} from '@/utils/validation/dateHelpers';
import { formatTimeInput, isValidTimeFormat } from '@/utils/validation/timeHelpers';

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

  const handleDateChange = (text: string) => {
    const formatted = formatDateInput(text);
    if (formatted.length === 10) {
      setDate(convertDateToAPI(formatted));
    }
  };

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
              <TextInput
                style={styles.input}
                value={convertDateFromAPI(date)}
                onChangeText={handleDateChange}
                placeholder="DD/MM/AAAA"
                keyboardType="numeric"
                maxLength={10}
              />
            </View>

            <View style={styles.section}>
              <Text style={styles.label}>Horário</Text>
              <TextInput
                style={styles.input}
                value={time}
                onChangeText={(text) => setTime(formatTimeInput(text))}
                placeholder="HH:MM"
                keyboardType="numeric"
                maxLength={5}
              />
            </View>

            {userRole === 'doctor' && doctorAddresses.length > 0 && (
              <View style={styles.section}>
                <Text style={styles.label}>Local de Retirada (Opcional)</Text>
                <Text style={styles.hint}>
                  Você pode alterar o local de retirada para um dos seus endereços cadastrados.
                </Text>
                {doctorAddresses.map((address) => (
                  <Pressable
                    key={address.id}
                    style={[
                      styles.addressOption,
                      selectedAddressId === address.id && styles.addressOptionSelected,
                    ]}
                    onPress={() => setSelectedAddressId(address.id)}
                  >
                    <View style={styles.radioButton}>
                      {selectedAddressId === address.id && <View style={styles.radioButtonInner} />}
                    </View>
                    <View style={styles.addressInfo}>
                      <Text style={styles.addressName}>{address.location_name}</Text>
                      <Text style={styles.addressText}>{address.full_address}</Text>
                    </View>
                  </Pressable>
                ))}
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

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContainer: {
    backgroundColor: Colors.white,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '90%',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: Colors.gray_200,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: Colors.gray_800,
  },
  closeButton: {
    padding: 4,
  },
  closeButtonText: {
    fontSize: 24,
    color: Colors.gray_500,
  },
  content: {
    padding: 20,
  },
  section: {
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.gray_700,
    marginBottom: 8,
  },
  hint: {
    fontSize: 12,
    color: Colors.gray_500,
    marginBottom: 12,
  },
  input: {
    borderWidth: 1,
    borderColor: Colors.gray_300,
    borderRadius: 8,
    padding: 12,
    backgroundColor: Colors.white,
    fontSize: 16,
    color: Colors.gray_800,
  },
  addressOption: {
    flexDirection: 'row',
    padding: 12,
    borderWidth: 1,
    borderColor: Colors.gray_300,
    borderRadius: 8,
    marginBottom: 8,
    alignItems: 'flex-start',
  },
  addressOptionSelected: {
    borderColor: Colors.yellow_green_500,
    backgroundColor: Colors.green_50,
  },
  radioButton: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: Colors.gray_300,
    marginRight: 12,
    marginTop: 2,
    justifyContent: 'center',
    alignItems: 'center',
  },
  radioButtonInner: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: Colors.yellow_green_500,
  },
  addressInfo: {
    flex: 1,
  },
  addressName: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.gray_800,
    marginBottom: 2,
  },
  addressText: {
    fontSize: 13,
    color: Colors.gray_500,
  },
  infoBox: {
    backgroundColor: Colors.blue_50,
    padding: 12,
    borderRadius: 8,
    marginTop: 8,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.blue_800,
    marginBottom: 6,
  },
  infoText: {
    fontSize: 13,
    color: Colors.blue_900,
    marginBottom: 4,
  },
  footer: {
    flexDirection: 'row',
    padding: 16,
    gap: 12,
    borderTopWidth: 1,
    borderTopColor: Colors.gray_200,
  },
  cancelButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: Colors.gray_300,
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: Colors.gray_500,
  },
  submitButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    backgroundColor: Colors.yellow_green_500,
    alignItems: 'center',
  },
  submitButtonDisabled: {
    backgroundColor: Colors.gray_400,
  },
  submitButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: Colors.white,
  },
});
