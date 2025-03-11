import React from 'react';
import { Stack } from 'expo-router';
import DoctorAddressScreen from '../../screens/DoctorAddressScreen';

export default function Home() {
  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <DoctorAddressScreen />
    </>
  );
}
