import { StyleSheet, TextStyle, ViewStyle } from 'react-native';
import { colors, radii, spacing, typography } from '@/theme/tokens';

type Variant = 'neutral' | 'info' | 'success' | 'warning' | 'error';
type Size = 'sm' | 'md';

const variantColors: Record<Variant, { bg: string; text: string }> = {
  neutral: { bg: colors.surfaceSecondary, text: colors.textSecondary },
  info: { bg: colors.infoLight, text: colors.info },
  success: { bg: colors.successSurface, text: colors.success },
  warning: { bg: colors.warningSurface, text: colors.warningText },
  error: { bg: colors.errorSurface, text: colors.error },
};

const sizeStyles: Record<Size, { container: ViewStyle; text: TextStyle }> = {
  sm: {
    container: {
      paddingHorizontal: spacing.sm,
      paddingVertical: 2,
      borderRadius: radii.md,
    },
    text: {
      fontSize: typography.caption.fontSize,
      lineHeight: typography.caption.lineHeight,
      fontFamily: typography.fontFamily.medium,
    },
  },
  md: {
    container: {
      paddingHorizontal: spacing.md,
      paddingVertical: spacing.xs,
      borderRadius: radii.lg,
    },
    text: {
      fontSize: typography.label.fontSize,
      lineHeight: typography.label.lineHeight,
      fontFamily: typography.fontFamily.medium,
    },
  },
};

const base = StyleSheet.create({
  container: { alignSelf: 'flex-start' },
});

export function getBadgeStyles(variant: Variant, size: Size) {
  const v = variantColors[variant];
  const s = sizeStyles[size];
  return {
    container: [base.container, s.container, { backgroundColor: v.bg }],
    text: [s.text, { color: v.text }],
  };
}

export type { Variant as BadgeVariant, Size as BadgeSize };
