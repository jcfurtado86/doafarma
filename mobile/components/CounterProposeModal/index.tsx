import React, { useState } from 'react';
import { Modal, View, Text, StyleSheet, Pressable, ScrollView, TextInput } from 'react-native';
import { toast } from '@/utils/toast';
import { Address, CounterProposeAppointmentData } from '@/types/medicationAppointment';
import { Colors } from '@/constants/Colors';
import {
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
  const [dateStr, setDateStr] = useState(currentDate);
  const [timeStr, setTimeStr] = useState(currentTime.substring(0, 5));
  const [selectedAddressId, setSelectedAddressId] = useState<number | undefined>(
    currentAddress?.id
  );
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async () => {
    if (!isValidDateFormat(dateStr)) {
      toast.error('Data inválida. Use o formato DD/MM/AAAA.');
      return;
    }

    if (!isValidTimeFormat(timeStr)) {
      toast.error('Horário inválido. Use o formato HH:MM.');
      return;
    }

    if (isDateInPast(dateStr)) {
      toast.error('A data deve ser hoje ou no futuro.');
      return;
    }

    setIsSubmitting(true);
    try {
      const data: CounterProposeAppointmentData = {
        scheduled_date: dateStr,
        scheduled_time: timeStr,
      };

      // Only include address_id if user is doctor and selected an address
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
            {/* Date Input */}
            <View style={styles.section}>
              <Text style={styles.label}>Data</Text>
              <TextInput
                style={styles.input}
                value={convertDateFromAPI(dateStr)}
                onChangeText={(text) => {
                  const numbers = text.replace(/\D/g, '');
                  if (numbers.length >= 8) {
                    const day = numbers.substring(0, 2);
                    const month = numbers.substring(2, 4);
                    const year = numbers.substring(4, 8);
                    setDateStr(`${year}-${month}-${day}`);
                  }
                }}
                placeholder="DD/MM/AAAA"
                keyboardType="numeric"
                maxLength={10}
              />
            </View>

            {/* Time Input */}
            <View style={styles.section}>
              <Text style={styles.label}>Horário</Text>
              <TextInput
                style={styles.input}
                value={timeStr}
                onChangeText={(text) => setTimeStr(formatTimeInput(text))}
                placeholder="HH:MM"
                keyboardType="numeric"
                maxLength={5}
              />
            </View>

            {/* Address Picker (Doctor only) */}
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

            {/* Current Address Info (Receptor) */}
            {userRole === 'receptor' && currentAddress && (
              <View style={styles.infoBox}>
                <Text style={styles.infoTitle}>ℹ️ Informação</Text>
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
    backgroundColor: '#ffffff',
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
    borderBottomColor: '#e5e7eb',
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1f2937',
  },
  closeButton: {
    padding: 4,
  },
  closeButtonText: {
    fontSize: 24,
    color: '#6b7280',
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
    color: '#374151',
    marginBottom: 8,
  },
  hint: {
    fontSize: 12,
    color: '#6b7280',
    marginBottom: 12,
  },
  input: {
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: 8,
    padding: 12,
    backgroundColor: '#ffffff',
    fontSize: 16,
    color: '#1f2937',
  },
  addressOption: {
    flexDirection: 'row',
    padding: 12,
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: 8,
    marginBottom: 8,
    alignItems: 'flex-start',
  },
  addressOptionSelected: {
    borderColor: Colors.yellow_green_500,
    backgroundColor: '#f0fdf4',
  },
  radioButton: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: '#d1d5db',
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
    color: '#1f2937',
    marginBottom: 2,
  },
  addressText: {
    fontSize: 13,
    color: '#6b7280',
  },
  infoBox: {
    backgroundColor: '#eff6ff',
    padding: 12,
    borderRadius: 8,
    marginTop: 8,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1e40af',
    marginBottom: 6,
  },
  infoText: {
    fontSize: 13,
    color: '#1e3a8a',
    marginBottom: 4,
  },
  footer: {
    flexDirection: 'row',
    padding: 16,
    gap: 12,
    borderTopWidth: 1,
    borderTopColor: '#e5e7eb',
  },
  cancelButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#d1d5db',
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#6b7280',
  },
  submitButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    backgroundColor: Colors.yellow_green_500,
    alignItems: 'center',
  },
  submitButtonDisabled: {
    backgroundColor: '#9ca3af',
  },
  submitButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#ffffff',
  },
});
