import { StyleSheet } from 'react-native';

import { colors, elevation, radii, spacing } from '@/theme/tokens';

export const styles = StyleSheet.create({
  base: {
    backgroundColor: colors.surface,
    borderRadius: radii.lg,
    padding: spacing.md,
  },
  default: {
    ...elevation.md,
  },
  elevated: {
    ...elevation.lg,
  },
  outlined: {
    borderWidth: 1,
    borderColor: colors.border,
  },
  accent_primary: {
    borderLeftWidth: 4,
    borderLeftColor: colors.primary,
  },
  accent_error: {
    borderLeftWidth: 4,
    borderLeftColor: colors.error,
  },
  accent_info: {
    borderLeftWidth: 4,
    borderLeftColor: colors.info,
  },
});
