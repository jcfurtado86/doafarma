import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  imageBackground: {
    flex: 1,
    width: '100%',
    height: '100%',
    resizeMode: 'cover',
    position: 'absolute',
    backgroundColor: '#ffffff',
  },

  container: {
    flex: 1,
    justifyContent: 'space-between',
    marginTop: 50,
    paddingBottom: 50,
    padding: 20,
  },

  backButton: {
    position: 'absolute',
    top: 20,
    left: 10,
    zIndex: 10,
    padding: 10,
  },

  textContainer: {
    textAlign: 'left',
    justifyContent: 'flex-start',
  },

  title: {
    fontSize: 24,
    fontWeight: '500',
    marginBottom: 10,
  },

  subtitle: {
    fontSize: 16,
    color: '#666',
    marginBottom: 10,
  },

  imageContainer: {
    alignItems: 'center',
  },

  image: {
    width: 230,
    height: 230,
    resizeMode: 'contain',
    marginBottom: 16,
    marginTop: 32,
    alignItems: 'center',
  },

  backButtonPressed: {
    opacity: 0.7,
  },
});
