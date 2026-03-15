import { StyleSheet } from 'react-native';
import { colors } from '@/theme/tokens';

export const styles = StyleSheet.create({
  banner: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: colors.error,
    paddingVertical: 8,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    zIndex: 9999,
  },
  text: {
    color: colors.white,
    fontSize: 13,
    fontWeight: '500',
  },
});
