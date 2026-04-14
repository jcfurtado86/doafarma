import { StyleSheet } from 'react-native';
import { colors, spacing } from '@/theme/tokens';

export const styles = StyleSheet.create({
  section: {
    marginBottom: spacing.lg,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
    marginBottom: spacing.sm,
  },
  hint: {
    fontSize: 12,
    color: colors.textSecondary,
    marginBottom: spacing.sm,
  },
  infoBox: {
    backgroundColor: colors.infoLight,
    padding: spacing.sm,
    borderRadius: 8,
    marginTop: spacing.sm,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.info,
    marginBottom: spacing.xs,
  },
  infoText: {
    fontSize: 13,
    color: colors.info,
    marginBottom: spacing.xs,
  },
  footerActions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  footerButton: {
    flex: 1,
  },
});
