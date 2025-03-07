import React from 'react';
import { Stack } from 'expo-router';
import DoctorFormScreen from '@/screens/DoctorFormScreen';

export default function Home() {
  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <DoctorFormScreen />
    </>
  );
}