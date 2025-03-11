import { Colors } from '@/constants/Colors';
import { fontFamily } from '@/constants/styles/font-family';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  imageBackground: {
    flex: 1,
    width: '100%',
    height: '100%',
    position: 'absolute',
    backgroundColor: '#ffffff',
  },

  container: {
    flex: 1,
    justifyContent: 'space-between',
    marginTop: 50,
    paddingBottom: 80,
    padding: 20,
  },

  imageContainer: {
    alignItems: 'center',
  },

  actionContainer: {
    gap: 20,
  },

  textContainer: {
    textAlign: 'left',
    justifyContent: 'flex-end',
  },

  buttonContainer: {
    textAlign: 'left',
    justifyContent: 'flex-end',
  },

  logo: {
    width: 165,
    height: 165,
    marginTop: 120,
    alignItems: 'center',
    resizeMode: 'contain',
  },

  buttonSecondary: {
    backgroundColor: Colors.yellow_green_100,
    //paddingHorizontal: 16,
    borderRadius: 10,
    marginBottom: 1,
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
});
