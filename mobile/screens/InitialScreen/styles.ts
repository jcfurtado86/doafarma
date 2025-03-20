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
    justifyContent: 'flex-end',
    paddingBottom: 32,
    paddingHorizontal: 20,
  },

  imageContainer: {
    alignItems: 'center',
    marginBottom: 160,
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
    gap: 16,
  },

  logo: {
    width: 165,
    height: 165,
    alignItems: 'center',
    resizeMode: 'contain',
  },
});
