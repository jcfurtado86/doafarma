import { colors, typography } from '@/theme/tokens';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  buttonSecondary: {
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 16,
    borderRadius: 10,
    height: 56,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },

  buttonHoverSecondary: {
    backgroundColor: colors.primaryLight,
  },

  buttonTextSecondary: {
    color: colors.primary,
    fontSize: 16,
    fontFamily: typography.fontFamily.bold,
    lineHeight: 56,
  },

  buttonTextSecondaryHover: {
    color: colors.primaryPressed,
  },

  buttonDisabled: {
    opacity: 0.5,
  },

  buttonTextDisabled: {},
});
