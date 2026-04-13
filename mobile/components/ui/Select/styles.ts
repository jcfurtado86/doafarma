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

  trigger: {
    width: '100%',
    justifyContent: 'center',
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: 'transparent',
    backgroundColor: colors.surfaceSecondary,
  },

  triggerSm: {
    height: SIZE_HEIGHTS.sm,
    paddingHorizontal: spacing.sm,
  },

  triggerMd: {
    height: SIZE_HEIGHTS.md,
    paddingHorizontal: spacing.md,
  },

  triggerLg: {
    height: SIZE_HEIGHTS.lg,
    paddingHorizontal: spacing.md,
  },

  triggerFocused: {
    borderColor: colors.primary,
  },

  triggerError: {
    borderColor: colors.errorBorder,
  },

  triggerDisabled: {
    opacity: 0.6,
  },

  triggerText: {
    fontSize: 16,
    color: colors.textPrimary,
  },

  triggerTextPlaceholder: {
    color: colors.textPlaceholder,
  },

  triggerTextDisabled: {
    color: colors.textMuted,
  },

  helperText: {
    ...typography.caption,
    color: colors.textMuted,
    marginTop: 2,
    marginLeft: spacing.xs,
  },

  errorText: {
    ...typography.caption,
    color: colors.errorBorder,
    marginTop: 2,
    marginLeft: spacing.xs,
  },

  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.3)',
    justifyContent: 'center',
    padding: spacing.lg,
  },

  modalContent: {
    backgroundColor: colors.surface,
    borderRadius: radii.lg,
    maxHeight: '70%',
    paddingVertical: spacing.xs,
  },

  modalItem: {
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },

  modalItemText: {
    fontSize: 16,
    color: colors.textPrimary,
  },

  modalItemTextSelected: {
    color: colors.primaryPressed,
    fontWeight: 'bold',
  },

  modalCancelButton: {
    paddingVertical: spacing.md,
    alignItems: 'center',
  },

  modalCancelText: {
    color: colors.textPlaceholder,
  },
});
