import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  container: {
    flex: 1,
    //justifyContent: 'space-between',
    paddingBottom: 30,
    padding: 20,
    backgroundColor: '#ffffff',
  },

  textContainer: {
    textAlign: 'left',
    marginTop: 80,
    paddingBottom: 50,
  },

  inputContainer: {
    justifyContent: 'space-between',
    gap: 16,
  },

  buttonContainer: {
    marginTop: 'auto',
    alignItems: 'center',
  },

  backButton: {
    position: 'absolute',
    top: 20,
    left: 10,
    zIndex: 10,
    padding: 10,
  },
  backButtonPressed: {
    opacity: 0.7,
  },
});
