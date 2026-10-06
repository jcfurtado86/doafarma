import { StyleSheet } from 'react-native';
import { spacing } from '@/theme/tokens';

export const styles = StyleSheet.create({
  textContainer: {
    textAlign: 'left',
    marginTop: 28,
    marginBottom: 24,
    gap: 4,
  },

  inputContainer: {
    width: '100%',
    gap: spacing.md,
  },

  buttonContainer: {
    marginTop: 'auto',
    alignItems: 'center',
    marginBottom: 52,
  },
});
