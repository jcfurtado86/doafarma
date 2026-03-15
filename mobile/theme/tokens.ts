export const colors = {
  primary: '#A4C457',
  primaryPressed: '#86A939',
  primaryLight: '#F6FAEB',

  info: '#4563EA',
  infoLight: '#EEF1FD',
  accent: '#FF9A6C',

  background: '#F8F9FA',
  surface: '#FFFFFF',
  surfaceSecondary: '#F7F7F7',

  textPrimary: '#1f2937',
  textSecondary: '#6b7280',
  textMuted: '#9ca3af',
  textPlaceholder: '#AFB2BF',
  textInverted: '#FFFFFF',

  border: '#e5e7eb',
  borderFocus: '#A4C457',

  error: '#DC2626',
  errorSurface: '#FEE2E2',
  errorBorder: '#E53935',
  success: '#16a34a',
  successSurface: '#f0fdf4',
  warning: '#f59e0b',
  warningSurface: '#fefce8',

  statusProposed: '#f59e0b',
  statusConfirmed: '#4563EA',
  statusCompleted: '#16a34a',

  overlay: 'rgba(0, 0, 0, 0.5)',
  white: '#FFFFFF',
  black: '#000000',
} as const;

export const typography = {
  fontFamily: {
    regular: 'Inter_400Regular',
    medium: 'Inter_500Medium',
    semibold: 'Inter_600SemiBold',
    bold: 'Inter_700Bold',
  },
  heading1: { fontSize: 32, lineHeight: 40, fontFamily: 'Inter_700Bold' },
  heading2: { fontSize: 28, lineHeight: 36, fontFamily: 'Inter_700Bold' },
  heading3: { fontSize: 24, lineHeight: 32, fontFamily: 'Inter_600SemiBold' },
  heading4: { fontSize: 20, lineHeight: 28, fontFamily: 'Inter_600SemiBold' },
  body: { fontSize: 16, lineHeight: 24, fontFamily: 'Inter_400Regular' },
  bodyMedium: { fontSize: 16, lineHeight: 24, fontFamily: 'Inter_500Medium' },
  label: { fontSize: 14, lineHeight: 20, fontFamily: 'Inter_500Medium' },
  caption: { fontSize: 12, lineHeight: 16, fontFamily: 'Inter_400Regular' },
} as const;

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  '2xl': 48,
} as const;

export const radii = {
  none: 0,
  sm: 4,
  md: 8,
  lg: 12,
  xl: 16,
  full: 9999,
} as const;

export const elevation = {
  none: {
    shadowColor: 'transparent',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0,
    shadowRadius: 0,
    elevation: 0,
  },
  sm: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  md: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  lg: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
    elevation: 6,
  },
} as const;

export const tokens = { colors, typography, spacing, radii, elevation } as const;
