import { Stack, router } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { useEffect, useRef } from 'react';
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
import type { Subscription } from 'expo-notifications';
import {
  addNotificationReceivedListener,
  addNotificationResponseReceivedListener,
} from '@/services/pushNotificationService';

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
  const notificationListener = useRef<Subscription>();
  const responseListener = useRef<Subscription>();

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

  // Setup notification listeners
  useEffect(() => {
    // When a notification is received while app is in foreground
    notificationListener.current = addNotificationReceivedListener((notification) => {
      const { title, body } = notification.request.content;
      Toast.show({
        type: 'info',
        text1: title || 'Notificação',
        text2: body || '',
        visibilityTime: 4000,
      });
    });

    // When user taps on a notification
    responseListener.current = addNotificationResponseReceivedListener((response) => {
      const data = response.notification.request.content.data;

      // Navigate to appointments if it's an appointment reminder
      if (data?.type === 'appointment_reminder') {
        const user = useAuthStore.getState().user;
        if (user?.role === 'doctor') {
          router.push('/(auth)/appointments/received');
        } else {
          router.push('/(auth)/receptor/appointments');
        }
      }
    });

    return () => {
      if (notificationListener.current) {
        notificationListener.current.remove();
      }
      if (responseListener.current) {
        responseListener.current.remove();
      }
    };
  }, []);

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
