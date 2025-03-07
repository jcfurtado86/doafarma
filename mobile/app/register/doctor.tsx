import React from 'react';
import { Stack } from 'expo-router';
import DoctorRegistrationScreen from '../../screens/DoctorRegistrationScreen';

export default function Home() {
  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <DoctorRegistrationScreen />
    </>
  );
}
