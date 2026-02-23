import { Colors } from '@/constants/Colors';
import { fontFamily } from '@/constants/styles/font-family';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  buttonSecondary: {
    backgroundColor: Colors.yellow_green_100,
    paddingHorizontal: 16,
    borderRadius: 10,
    height: 56,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },

  buttonHoverSecondary: {
    backgroundColor: Colors.yellow_green_200,
  },

  buttonTextSecondary: {
    color: Colors.yellow_green_400,
    fontSize: 16,
    fontFamily: fontFamily.bold,
    lineHeight: 56,
  },

  buttonTextSecondaryHover: {
    color: Colors.yellow_green_500,
  },

  buttonDisabled: {
    opacity: 0.5,
  },

  buttonTextDisabled: {},
});
