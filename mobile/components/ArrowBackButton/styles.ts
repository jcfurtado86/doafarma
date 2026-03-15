import { colors } from '@/theme/tokens';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  arrowBackButton: {
    width: 32,
    height: 32,
    zIndex: 10,
  },

  arrowBackIcon: {
    color: colors.primary,
    alignSelf: 'center',
    marginVertical: 'auto',
  },

  arrowBackButtonPressed: {
    color: colors.primaryPressed,
  },
});
