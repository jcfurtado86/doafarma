import React from 'react';
import { Stack } from 'expo-router';
import RegisterScreen from '@/screens/RegisterScreen';

export default function Home() {
  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <RegisterScreen />
    </>
  );
}
