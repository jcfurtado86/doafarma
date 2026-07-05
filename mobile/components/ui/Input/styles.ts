import { StyleSheet } from 'react-native';
import { colors, radii, spacing, typography } from '@/theme/tokens';

export const SIZE_HEIGHTS = { sm: 40, md: 48, lg: 56 } as const;

export const styles = StyleSheet.create({
  container: {
    width: '100%',
  },

  label: {
    ...typography.label,
    color: colors.textSecondary,
    marginBottom: spacing.xs,
  },

  input: {
    width: '100%',
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: 'transparent',
    backgroundColor: colors.surfaceSecondary,
    color: colors.textPrimary,
  },

  sizeSm: {
    height: SIZE_HEIGHTS.sm,
    paddingHorizontal: spacing.sm,
    fontSize: typography.label.fontSize,
  },

  sizeMd: {
    height: SIZE_HEIGHTS.md,
    paddingHorizontal: spacing.md,
    fontSize: typography.body.fontSize,
  },

  sizeLg: {
    height: SIZE_HEIGHTS.lg,
    paddingHorizontal: spacing.md,
    fontSize: typography.body.fontSize,
  },

  focused: {
    borderColor: colors.primary,
  },

  error: {
    borderColor: colors.errorBorder,
  },

  disabled: {
    backgroundColor: colors.surfaceSecondary,
    color: colors.textMuted,
  },

  helperText: {
    ...typography.caption,
    color: colors.textMuted,
    marginTop: spacing['2xs'],
    marginLeft: spacing.xs,
  },

  errorText: {
    ...typography.caption,
    color: colors.errorBorder,
    marginTop: spacing['2xs'],
    marginLeft: spacing.xs,
  },
});
