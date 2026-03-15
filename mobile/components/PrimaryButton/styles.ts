import { colors, typography } from '@/theme/tokens';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  buttonPrimary: {
    backgroundColor: colors.primary,
    paddingHorizontal: 16,
    borderRadius: 10,
    height: 56,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },

  buttonHover: {
    backgroundColor: colors.primaryPressed,
  },

  buttonText: {
    color: colors.textInverted,
    fontSize: 16,
    fontWeight: 'bold',
    fontFamily: typography.fontFamily.bold,
    lineHeight: 56,
  },

  buttonDisabled: {
    opacity: 0.5,
  },

  buttonTextDisabled: {},
});
