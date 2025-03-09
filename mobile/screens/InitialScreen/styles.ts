import { Colors } from '@/constants/Colors';
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

  logo: {
    width: '80%',
    height: undefined,
    resizeMode: 'contain',
    marginTop: 250,
  },

  textContainer: {
    textAlign: 'left',
    justifyContent: 'flex-end',
  },

  title: {
    fontSize: 24,
    fontWeight: '500',
  },

  caption: {
    color: '#AFB2BF',
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 20,
    textAlign: 'left',
  },

  buttonPrimary: {
    backgroundColor: Colors.yellow_green_400,
    padding: 15,
    borderRadius: 10,
    marginBottom: 16,
    height: 56,
    width: '100%',
    alignItems: 'center',
  },

  buttonHoverPrimary: {
    backgroundColor: Colors.yellow_green_500,
  },

  buttonSecondary: {
    backgroundColor: Colors.yellow_green_100,
    paddingHorizontal: 16,
    borderRadius: 10,
    marginBottom: 16,
    height: 56,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },

  buttonHoverSecondary: {
    backgroundColor: Colors.yellow_green_200,
  },

  buttonTextPrimary: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },

  buttonTextSecondary: {
    color: Colors.yellow_green_400,
    fontSize: 16,
    fontWeight: 'bold',
    lineHeight: 56,
  },

  buttonTextSecondaryHover: {
    color: Colors.yellow_green_500,
  },
});
