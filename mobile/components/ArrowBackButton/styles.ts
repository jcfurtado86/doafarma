import { Colors } from '@/constants/Colors';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  arrowBackButton: {
    width: 32,
    height: 32,
    zIndex: 10,
  },

  arrowBackIcon: {
    color: Colors.yellow_green_400,
    alignSelf: 'center',
    marginVertical: 'auto',
  },

  arrowBackButtonPressed: {
    color: Colors.yellow_green_500,
  },
});
