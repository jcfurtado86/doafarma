import { StyleSheet } from 'react-native';
import { colors, spacing, typography } from '@/theme/tokens';

export const iconSize = spacing['2xl'];

export const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing['2xl'],
  },
  title: {
    ...typography.heading4,
    color: colors.textPrimary,
    marginTop: spacing.md,
    textAlign: 'center',
  },
  description: {
    ...typography.body,
    color: colors.textSecondary,
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  action: {
    alignSelf: 'stretch',
    marginTop: spacing.lg,
  },
});
