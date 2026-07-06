import { StyleSheet } from 'react-native';

import { colors, radii, spacing, typography } from '@/theme/tokens';

export const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  content: {
    padding: spacing.md,
    gap: spacing.lg,
  },
  identity: {
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.lg,
  },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: radii.full,
    backgroundColor: colors.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    ...typography.heading3,
    color: colors.primaryPressed,
  },
  name: {
    ...typography.heading4,
    color: colors.textPrimary,
  },
  email: {
    ...typography.body,
    color: colors.textSecondary,
  },
  menu: {
    gap: spacing.sm,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: colors.surface,
    borderRadius: radii.md,
    padding: spacing.md,
  },
  menuItemLabel: {
    ...typography.bodyMedium,
    color: colors.textPrimary,
    flex: 1,
  },
});
