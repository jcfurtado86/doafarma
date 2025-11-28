import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { useEffect } from 'react';
import 'react-native-reanimated';

import {
  Roboto_400Regular,
  Roboto_500Medium,
  Roboto_700Bold,
  useFonts,
} from '@expo-google-fonts/roboto';
import { StatusBar } from 'expo-status-bar';
import { useAuthStore } from '@/stores/authStore';
import Toast from 'react-native-toast-message';

export {
  // Catch any errors thrown by the Layout component.
  ErrorBoundary,
} from 'expo-router';

export const unstable_settings = {
  // Ensure that reloading on `/modal` keeps a back button present.
  initialRouteName: 'index',
};

// Prevent the splash screen from auto-hiding before asset loading is complete.
SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const [loaded, error] = useFonts({
    Roboto_500Medium,
    Roboto_400Regular,
    Roboto_700Bold,
  });

  const checkAuth = useAuthStore((state) => state.checkAuth);

  useEffect(() => {
    if (error) throw error;
  }, [error]);

  useEffect(() => {
    async function prepare() {
      if (loaded) {
        await checkAuth();
        await SplashScreen.hideAsync();
      }
    }
    prepare();
  }, [loaded, checkAuth]);

  if (!loaded) {
    return null;
  }

  return <RootLayoutNav />;
}

function RootLayoutNav() {
  return (
    <>
      <Stack
        screenOptions={{
          headerShown: false,
          animation: 'ios_from_right',
          navigationBarHidden: true,
        }}
      />
      <StatusBar style="auto" />
      <Toast />
    </>
  );
}
