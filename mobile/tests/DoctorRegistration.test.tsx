import { describe, expect, it, jest } from '@jest/globals';
import { DoctorRegistrationFlow } from '@/screens/DoctorRegistration/DoctorRegistrationFlow';
import { render, screen } from '@testing-library/react-native';

jest.mock('@expo/vector-icons', () => {
  const { View } = require('react-native');
  return {
    Ionicons: View,
  };
});

jest.mock('@/stores/authStore', () => ({
  useAuthStore: jest.fn(),
}));

describe('Validation Errors', () => {
  it('should render validation error messages from backend response on the form input', () => {
    render(<DoctorRegistrationFlow currentStep={1} />);

    expect(screen.getByPlaceholderText('Nome Completo')).toBeTruthy();
    expect(screen.getByPlaceholderText('CRM')).toBeTruthy();
    expect(screen.getByPlaceholderText('DDD')).toBeTruthy();
    expect(screen.getByPlaceholderText('Telefone')).toBeTruthy();
    expect(screen.getByPlaceholderText('Email')).toBeTruthy();
    expect(screen.getByPlaceholderText('Senha')).toBeTruthy();
    expect(screen.getByPlaceholderText('Confirmar Senha')).toBeTruthy();
  });
});
