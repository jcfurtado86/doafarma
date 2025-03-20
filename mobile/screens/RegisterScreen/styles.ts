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
    gap: 32,
    marginTop: 28,
    paddingHorizontal: 20,
  },

  textContainer: {
    textAlign: 'left',
    justifyContent: 'flex-start',
    gap: 4,
  },

  choiceContainer: {
    alignItems: 'center',
    gap: 32,
  },

  imageContainer: {
    width: '100%',
    alignItems: 'center',
  },

  patientImage: {
    height: 213,
    resizeMode: 'contain',
    marginBottom: 16,
    alignItems: 'center',
  },

  doctorImage: {
    height: 204,
    resizeMode: 'contain',
    marginBottom: 16,
    alignItems: 'center',
  },
});
