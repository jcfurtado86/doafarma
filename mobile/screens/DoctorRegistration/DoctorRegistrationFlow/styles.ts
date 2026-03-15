import { StyleSheet } from 'react-native';
import { colors } from '@/theme/tokens';

export const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingHorizontal: 20,
    backgroundColor: colors.surface,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: colors.textPrimary,
  },
  errorContainer: {
    backgroundColor: colors.errorSurface,
    padding: 10,
    borderRadius: 8,
    marginVertical: 12,
  },
  errorText: {
    color: colors.errorBorder,
    fontSize: 14,
    textAlign: 'center',
  },
});
