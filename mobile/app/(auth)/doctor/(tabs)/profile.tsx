import React from 'react';
import { ProfileScreen, type ProfileMenuItem } from '@/screens/Account/ProfileScreen';

const DOCTOR_MENU: ProfileMenuItem[] = [
  {
    label: 'Minhas Avaliações',
    icon: 'star-outline',
    href: '/(auth)/doctor/ratings',
    testID: 'profile-ratings',
  },
  {
    label: 'Configurações',
    icon: 'settings-outline',
    href: '/(auth)/doctor/settings',
    testID: 'profile-settings',
  },
];

export default function DoctorProfileRoute() {
  return <ProfileScreen items={DOCTOR_MENU} />;
}
