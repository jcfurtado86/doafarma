import { AccessibilityRole } from 'react-native';

type A11yProps = {
  accessibilityRole?: AccessibilityRole;
  accessibilityLabel: string;
  accessibilityHint?: string;
  accessibilityState?: { disabled?: boolean; selected?: boolean };
};

export const a11y = {
  button: (label: string, disabled?: boolean): A11yProps => ({
    accessibilityRole: 'button',
    accessibilityLabel: label,
    ...(disabled !== undefined && { accessibilityState: { disabled } }),
  }),

  input: (label: string, hint?: string): A11yProps => ({
    accessibilityLabel: label,
    ...(hint && { accessibilityHint: hint }),
  }),

  link: (label: string): A11yProps => ({
    accessibilityRole: 'link',
    accessibilityLabel: label,
  }),

  card: (summary: string): A11yProps => ({
    accessibilityLabel: summary,
  }),

  badge: (status: string): A11yProps => ({
    accessibilityLabel: `Status: ${status}`,
  }),

  radioButton: (label: string, selected: boolean): A11yProps => ({
    accessibilityRole: 'radio',
    accessibilityLabel: label,
    accessibilityState: { selected },
  }),

  backButton: (): A11yProps => ({
    accessibilityRole: 'button',
    accessibilityLabel: 'Voltar',
  }),

  closeButton: (): A11yProps => ({
    accessibilityRole: 'button',
    accessibilityLabel: 'Fechar',
  }),
};
