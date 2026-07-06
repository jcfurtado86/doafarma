import React from 'react';
import { fireEvent, render } from '@testing-library/react-native';
import { type Href } from 'expo-router';

import { ProfileScreen, type ProfileMenuItem } from '.';
import { useAuthStore, type User } from '@/stores/authStore';

const mockPush = jest.fn();
jest.mock('expo-router', () => ({
  ...jest.requireActual('expo-router'),
  useRouter: () => ({ push: mockPush }),
}));

const doctor: User = {
  id: 1,
  name: 'Ana Souza',
  email: 'ana@doafarma.com',
  role: 'doctor',
  status: 'approved',
};

const items: ProfileMenuItem[] = [
  {
    label: 'Minhas Avaliações',
    icon: 'star-outline',
    // rota criada na Task 4; cast temporário até lá
    href: '/(auth)/doctor/ratings' as Href,
    testID: 'profile-ratings',
  },
  {
    label: 'Configurações',
    icon: 'settings-outline',
    // rota criada na Task 4; cast temporário até lá
    href: '/(auth)/doctor/settings' as Href,
    testID: 'profile-settings',
  },
];

describe('ProfileScreen', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    useAuthStore.setState({ user: doctor });
  });

  it('shows the user identity (name, email, role label)', () => {
    const { getByText } = render(<ProfileScreen items={items} />);
    expect(getByText('Ana Souza')).toBeTruthy();
    expect(getByText('ana@doafarma.com')).toBeTruthy();
    expect(getByText('Médico')).toBeTruthy();
  });

  it('shows the receptor role label for receptors', () => {
    useAuthStore.setState({ user: { ...doctor, role: 'receptor' } });
    const { getByText } = render(<ProfileScreen items={[]} />);
    expect(getByText('Receptor')).toBeTruthy();
  });

  it('renders exactly the menu items it receives', () => {
    const { getByTestId, queryByTestId } = render(<ProfileScreen items={[items[1]]} />);
    expect(getByTestId('profile-settings')).toBeTruthy();
    expect(queryByTestId('profile-ratings')).toBeNull();
  });

  it('navigates to the item href on press', () => {
    const { getByTestId } = render(<ProfileScreen items={items} />);
    fireEvent.press(getByTestId('profile-ratings'));
    expect(mockPush).toHaveBeenCalledWith('/(auth)/doctor/ratings');
  });
});
