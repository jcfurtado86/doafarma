import ArrowBackButton from '@/components/ArrowBackButton';
import {
  DoctorRegistrationFormData,
  useDoctorRegistrationFormStore,
} from '@/stores/doctorRegistrationFormStore';
import { useRouter } from 'expo-router';
import { ActivityIndicator, Text, View } from 'react-native';
import { DoctorPersonalDataStep } from '../steps/DoctorPersonalDataStep';
import { DoctorAddressStep } from '../steps/DoctorAddressStep';
import { styles } from './styles';
import { Colors } from '@/constants/Colors';
import { UseFormSetError } from 'react-hook-form';
import { logger } from '@/utils/logger';

interface DoctorRegistrationFlowProps {
  currentStep: number;
}

export function DoctorRegistrationFlow({ currentStep }: DoctorRegistrationFlowProps) {
  const router = useRouter();
  const {
    updateDoctorRegistrationFormData,
    submitDoctorRegistrationForm,
    isLoading,
    error,
    validationErrors,
  } = useDoctorRegistrationFormStore();

  const handleNextStep = (data: Partial<DoctorRegistrationFormData>) => {
    updateDoctorRegistrationFormData(data);
    router.push(`/register/doctor?step=${currentStep + 1}`);
  };

  const handlePreviousStep = () => {
    if (currentStep > 1) {
      router.push(`/register/doctor?step=${currentStep - 1}`);
    } else {
      router.push('/register');
    }
  };

  const handleFinishRegistration = async (
    data: Partial<DoctorRegistrationFormData>,
    setError?: UseFormSetError<any>
  ) => {
    updateDoctorRegistrationFormData(data);
    const success = await submitDoctorRegistrationForm();

    if (!success && setError && validationErrors && error) {
      logger.log('Erro ao registrar médico:', error);
      logger.error('Erro de validação:', validationErrors);
      setError(Object.keys(validationErrors)[0], { message: error }, { shouldFocus: true });
    }

    if (success) {
      router.replace('/(auth)/dashboard');
    }
  };

  const renderStep = () => {
    switch (currentStep) {
      case 1:
        return <DoctorPersonalDataStep onSubmit={handleNextStep} />;
      case 2:
        return <DoctorAddressStep onSubmit={handleFinishRegistration} />;
      default:
        return null;
    }
  };

  return (
    <View style={styles.container}>
      <ArrowBackButton style={{ marginTop: 56 }} onPress={handlePreviousStep} />

      {error && (
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>{error}</Text>
        </View>
      )}

      {isLoading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={Colors.yellow_green_400} />
          <Text style={styles.loadingText}>Registrando médico...</Text>
        </View>
      ) : (
        renderStep()
      )}
    </View>
  );
}
