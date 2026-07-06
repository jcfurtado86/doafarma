import React from 'react';
import { ProfileScreen, type ProfileMenuItem } from '@/screens/Account/ProfileScreen';

const RECEPTOR_MENU: ProfileMenuItem[] = [
  {
    label: 'Configurações',
    icon: 'settings-outline',
    href: '/(auth)/receptor/settings',
    testID: 'profile-settings',
  },
];

export default function ReceptorProfileRoute() {
  return <ProfileScreen items={RECEPTOR_MENU} />;
}
