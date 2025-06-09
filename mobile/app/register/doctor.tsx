import React from 'react';
import { useLocalSearchParams } from 'expo-router';
import { DoctorRegistrationFlow } from '@/screens/DoctorRegistration/DoctorRegistrationFlow';

export default function DoctorRegistration() {
  const { step } = useLocalSearchParams<{ step: string }>();

  const currentStep = step ? parseInt(step, 10) : 0;

  return <DoctorRegistrationFlow currentStep={currentStep} />;
}
