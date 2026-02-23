import { Colors } from '@/constants/Colors';
import { fontFamily } from '@/constants/styles/font-family';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  buttonPrimary: {
    backgroundColor: Colors.yellow_green_400,
    paddingHorizontal: 16,
    borderRadius: 10,
    height: 56,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },

  buttonHover: {
    backgroundColor: Colors.yellow_green_500,
  },

  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
    fontFamily: fontFamily.bold,
    lineHeight: 56,
  },

  buttonDisabled: {
    opacity: 0.5,
  },

  buttonTextDisabled: {},
});
