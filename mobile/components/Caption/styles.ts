import { colors, typography } from '@/theme/tokens';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  caption: {
    color: colors.textPlaceholder,
    fontSize: 16,
    fontFamily: typography.fontFamily.regular,
  },
});
