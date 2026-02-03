import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Alert,
  ActivityIndicator,
  TextInput,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { toast } from '@/utils/toast';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Colors } from '@/constants/Colors';
import { useMedicationAppointmentStore } from '@/stores/medicationAppointmentStore';
import PrimaryButton from '@/components/PrimaryButton';
import { formatDateInput, convertDateToAPI, isDateInPast } from '@/utils/validation/dateHelpers';
import { formatTimeInput, isValidTimeFormat } from '@/utils/validation/timeHelpers';

export default function ScheduleAppointmentScreen() {
  const { requestId } = useLocalSearchParams<{ requestId: string }>();
  const router = useRouter();
  const { createAppointment, isLoading } = useMedicationAppointmentStore();

  const [date, setDate] = useState('');
  const [time, setTime] = useState('');

  const handleDateChange = (text: string) => {
    setDate(formatDateInput(text));
  };

  const handleTimeChange = (text: string) => {
    setTime(formatTimeInput(text));
  };

  const validateInputs = (): boolean => {
    if (date.length !== 10) {
      toast.error('Data inválida. Use o formato DD/MM/AAAA');
      return false;
    }

    if (!isValidTimeFormat(time)) {
      toast.error('Horário inválido. Use o formato HH:MM');
      return false;
    }

    const apiDate = convertDateToAPI(date);
    if (isDateInPast(apiDate)) {
      toast.error('A data não pode ser no passado');
      return false;
    }

    return true;
  };

  const handleSchedule = async () => {
    if (!requestId) return;

    if (!validateInputs()) return;

    try {
      await createAppointment({
        medication_request_id: parseInt(requestId, 10),
        scheduled_date: convertDateToAPI(date),
        scheduled_time: time,
      });

      Alert.alert('Sucesso', 'Agendamento criado com sucesso!', [
        {
          text: 'Ver Agendamentos',
          onPress: () => router.replace('/(auth)/receptor/(tabs)/appointments'),
        },
        {
          text: 'OK',
          onPress: () => router.back(),
        },
      ]);
    } catch (error: any) {
      Alert.alert('Erro', error.message || 'Não foi possível criar o agendamento.');
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.header}>
          <Text style={styles.title}>Agendar Retirada</Text>
          <Text style={styles.subtitle}>Escolha a data e horário para retirar seu medicamento</Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>DATA</Text>
          <TextInput
            style={styles.input}
            placeholder="DD/MM/AAAA"
            placeholderTextColor="#9ca3af"
            value={date}
            onChangeText={handleDateChange}
            keyboardType="numeric"
            maxLength={10}
          />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>HORÁRIO</Text>
          <TextInput
            style={styles.input}
            placeholder="HH:MM"
            placeholderTextColor="#9ca3af"
            value={time}
            onChangeText={handleTimeChange}
            keyboardType="numeric"
            maxLength={5}
          />
        </View>

        <View style={styles.infoBox}>
          <Text style={styles.infoTitle}>Importante</Text>
          <Text style={styles.infoText}>
            {'\u2022'} O local de retirada será o endereço do médico{'\n'}
            {'\u2022'} Após a retirada, você e o médico devem confirmar{'\n'}
            {'\u2022'} O agendamento pode ser para hoje ou datas futuras
          </Text>
        </View>

        <View style={styles.actions}>
          {isLoading ? (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="small" color={Colors.yellow_green_500} />
              <Text style={styles.loadingText}>Criando agendamento...</Text>
            </View>
          ) : (
            <PrimaryButton label="Confirmar Agendamento" onPress={handleSchedule} />
          )}
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  content: {
    padding: 16,
  },
  header: {
    marginBottom: 24,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1f2937',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: '#6b7280',
  },
  section: {
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '600',
    color: '#6b7280',
    letterSpacing: 0.5,
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    fontSize: 18,
    fontWeight: '600',
    color: '#1f2937',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  infoBox: {
    backgroundColor: '#eff6ff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    borderLeftWidth: 4,
    borderLeftColor: '#3b82f6',
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1d4ed8',
    marginBottom: 8,
  },
  infoText: {
    fontSize: 14,
    color: '#1e40af',
    lineHeight: 22,
  },
  actions: {
    marginTop: 8,
  },
  loadingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 16,
    backgroundColor: '#f3f4f6',
    borderRadius: 8,
    gap: 12,
  },
  loadingText: {
    fontSize: 14,
    color: '#6b7280',
  },
});
