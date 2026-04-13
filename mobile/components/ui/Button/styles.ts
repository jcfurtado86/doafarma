import { StyleSheet, TextStyle, ViewStyle } from 'react-native';
import { colors, radii, spacing, typography } from '@/theme/tokens';

export type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost';
export type ButtonSize = 'sm' | 'md' | 'lg';

type VariantStyles = {
  container: ViewStyle;
  containerPressed: ViewStyle;
  text: TextStyle;
  textPressed: TextStyle;
};

type SizeStyles = {
  container: ViewStyle;
  text: TextStyle;
};

export const baseStyles = StyleSheet.create({
  container: {
    width: '100%',
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  text: {
    fontFamily: typography.fontFamily.bold,
    textAlign: 'center',
  },
  disabled: {
    opacity: 0.5,
  },
});

export const sizeStyles: Record<ButtonSize, SizeStyles> = {
  sm: {
    container: { height: 40, paddingHorizontal: spacing.sm },
    text: { fontSize: 14 },
  },
  md: {
    container: { height: 48, paddingHorizontal: spacing.md },
    text: { fontSize: 16 },
  },
  lg: {
    container: { height: 56, paddingHorizontal: spacing.md },
    text: { fontSize: 16 },
  },
};

export const variantStyles: Record<ButtonVariant, VariantStyles> = {
  primary: {
    container: { backgroundColor: colors.primary },
    containerPressed: { backgroundColor: colors.primaryPressed },
    text: { color: colors.textInverted },
    textPressed: {},
  },
  secondary: {
    container: { backgroundColor: colors.primaryLight },
    containerPressed: { backgroundColor: colors.primaryLight },
    text: { color: colors.primary },
    textPressed: { color: colors.primaryPressed },
  },
  outline: {
    container: {
      backgroundColor: 'transparent',
      borderWidth: 1,
      borderColor: colors.primary,
    },
    containerPressed: { backgroundColor: colors.primaryLight },
    text: { color: colors.primary },
    textPressed: { color: colors.primaryPressed },
  },
  ghost: {
    container: { backgroundColor: 'transparent' },
    containerPressed: { backgroundColor: colors.primaryLight },
    text: { color: colors.primary },
    textPressed: { color: colors.primaryPressed },
  },
};
