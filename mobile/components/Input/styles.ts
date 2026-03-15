import { colors } from '@/theme/tokens';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  container: {
    display: 'flex',
    width: '100%',
  },

  input: {
    display: 'flex',
    width: '100%',
    height: 48,
    borderRadius: 10,
    paddingLeft: 16,
    fontSize: 16,
    outlineColor: colors.primary,
    backgroundColor: colors.surfaceSecondary,
  },

  inputFocused: {
    borderWidth: 1,
    borderColor: colors.primary,
  },

  inputError: {
    borderWidth: 1,
    borderColor: colors.errorBorder,
  },

  errorText: {
    color: colors.errorBorder,
    fontSize: 12,
    marginTop: 2,
    marginLeft: 4,
  },
});
