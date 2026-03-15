import ArrowBackButton from '@/components/ArrowBackButton';
import {
  DoctorRegistrationFormData,
  useDoctorRegistrationFormStore,
} from '@/stores/doctorRegistrationFormStore';
import { useRouter, Href } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { isUserBlocked } from '@/types/user';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Text,
  View,
} from 'react-native';
import { DoctorPersonalDataStep } from '../steps/DoctorPersonalDataStep';
import { DoctorAddressStep } from '../steps/DoctorAddressStep';
import { styles } from './styles';
import { colors } from '@/theme/tokens';
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
      const user = useAuthStore.getState().user;
      if (user && isUserBlocked(user.status)) {
        router.replace('/(auth)/pending-approval' as Href);
      } else {
        router.replace('/(auth)/dashboard' as Href);
      }
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
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ArrowBackButton style={{ marginTop: 56 }} onPress={handlePreviousStep} />

      {error && (
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>{error}</Text>
        </View>
      )}

      {isLoading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
          <Text style={styles.loadingText}>Registrando médico...</Text>
        </View>
      ) : (
        <ScrollView
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
          contentContainerStyle={{ flexGrow: 1 }}
        >
          {renderStep()}
        </ScrollView>
      )}
    </KeyboardAvoidingView>
  );
}
