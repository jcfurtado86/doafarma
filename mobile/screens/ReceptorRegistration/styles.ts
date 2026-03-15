import { StyleSheet } from 'react-native';
import { colors } from '@/theme/tokens';

export const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.primaryLight,
    paddingHorizontal: 20,
  },

  textContainer: {
    textAlign: 'left',
    marginTop: 28,
    marginBottom: 24,
    gap: 4,
  },

  inputContainer: {
    justifyContent: 'space-between',
    width: '100%',
  },

  passwordContainer: {
    position: 'relative',
    width: '100%',
  },

  eyeButton: {
    position: 'absolute',
    right: 16,
    top: 12,
    zIndex: 1,
  },

  errorContainer: {
    backgroundColor: colors.errorSurface,
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
  },

  errorText: {
    color: colors.error,
    textAlign: 'center',
    fontSize: 14,
  },

  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },

  loadingText: {
    marginTop: 16,
    color: colors.textPrimary,
    fontSize: 16,
  },

  buttonContainer: {
    marginTop: 'auto',
    alignItems: 'center',
    marginBottom: 52,
  },

  termsContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 16,
    paddingHorizontal: 4,
  },

  checkbox: {
    marginRight: 12,
    width: 24,
    height: 24,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.white,
  },

  checkboxChecked: {
    backgroundColor: colors.primary,
    borderColor: colors.primary,
  },

  termsText: {
    flex: 1,
    fontSize: 14,
    color: colors.primaryPressed,
    lineHeight: 20,
  },

  termsLink: {
    color: colors.primaryPressed,
    textDecorationLine: 'underline',
  },

  termsError: {
    color: colors.error,
    fontSize: 12,
    marginTop: -8,
    marginBottom: 8,
    paddingHorizontal: 4,
  },
});
