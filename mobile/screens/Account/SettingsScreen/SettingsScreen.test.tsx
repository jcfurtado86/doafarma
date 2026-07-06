import React from 'react';
import { Alert } from 'react-native';

import { fireEvent, render } from '@testing-library/react-native';

import { useAuthStore } from '@/stores/authStore';

import { SettingsScreen } from '.';

describe('SettingsScreen', () => {
  const logoutMock = jest.fn().mockResolvedValue(undefined);

  beforeEach(() => {
    jest.clearAllMocks();
    useAuthStore.setState({ logout: logoutMock });
  });

  it('renders the logout button', () => {
    const { getByTestId } = render(<SettingsScreen />);
    expect(getByTestId('settings-logout')).toBeTruthy();
  });

  it('asks for confirmation before logging out (session stays active)', () => {
    const alertSpy = jest.spyOn(Alert, 'alert');
    const { getByTestId } = render(<SettingsScreen />);

    fireEvent.press(getByTestId('settings-logout'));

    expect(alertSpy).toHaveBeenCalled();
    expect(logoutMock).not.toHaveBeenCalled();
  });

  it('logs out when the user confirms', () => {
    const alertSpy = jest.spyOn(Alert, 'alert');
    const { getByTestId } = render(<SettingsScreen />);

    fireEvent.press(getByTestId('settings-logout'));

    const buttons = alertSpy.mock.calls[0][2] ?? [];
    const confirm = buttons.find((b) => b.style === 'destructive');
    confirm?.onPress?.();

    expect(logoutMock).toHaveBeenCalledTimes(1);
  });

  it('does not log out when the user cancels', () => {
    const alertSpy = jest.spyOn(Alert, 'alert');
    const { getByTestId } = render(<SettingsScreen />);

    fireEvent.press(getByTestId('settings-logout'));

    const buttons = alertSpy.mock.calls[0][2] ?? [];
    const cancel = buttons.find((b) => b.style === 'cancel');
    cancel?.onPress?.();

    expect(logoutMock).not.toHaveBeenCalled();
  });
});
