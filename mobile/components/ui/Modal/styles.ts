import { StyleSheet } from 'react-native';

import { colors, radii, spacing, typography } from '@/theme/tokens';

export type ModalAnimation = 'slide' | 'fade';

export const styles = StyleSheet.create({
  overlayBase: {
    flex: 1,
    backgroundColor: colors.overlay,
  },
  overlaySlide: {
    justifyContent: 'flex-end',
  },
  overlayFade: {
    justifyContent: 'center',
    alignItems: 'center',
    padding: spacing.lg,
  },
  containerBase: {
    backgroundColor: colors.surface,
  },
  containerSlide: {
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
    maxHeight: '90%',
  },
  containerFade: {
    borderRadius: radii.lg,
    maxHeight: '80%',
    width: '100%',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  title: {
    ...typography.heading4,
    color: colors.textPrimary,
    flex: 1,
  },
  closeButton: {
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    marginLeft: spacing.sm,
  },
  body: {
    flexShrink: 1,
  },
  bodyContent: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  footer: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
});
