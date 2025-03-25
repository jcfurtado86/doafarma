import ArrowBackButton from '@/components/ArrowBackButton';
import {
  DoctorRegistrationFormData,
  useDoctorRegistrationFormStore,
} from '@/stores/doctorRegistrationFormStore';
import { useRouter } from 'expo-router';
import { View } from 'react-native';
import { DoctorPersonalDataStep } from '../steps/DoctorPersonalDataStep';
import { DoctorAddressStep } from '../steps/DoctorAddressStep';
import { styles } from './styles';

interface DoctorRegistrationFlowProps {
  currentStep: number;
}

export function DoctorRegistrationFlow({ currentStep }: DoctorRegistrationFlowProps) {
  const router = useRouter();
  const { updateDoctorRegistrationFormData, submitDoctorRegistrationForm } =
    useDoctorRegistrationFormStore();

  const handleNextStep = (data: DoctorRegistrationFormData) => {
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

  const handleFinishRegistration = async (data: DoctorRegistrationFormData) => {
    updateDoctorRegistrationFormData(data);
    const success = await submitDoctorRegistrationForm();

    if (success) {
      router.push('/dashboard');
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
      {renderStep()}
    </View>
  );
}
