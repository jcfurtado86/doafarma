import React from 'react';
import InitialScreen from './screens/InitialScreen';
import { Stack } from 'expo-router';

export default function Home() {
  return (
    <>
      <Stack.Screen options={{ headerShown: false }} />
      <InitialScreen/>
    </>
  );
}
