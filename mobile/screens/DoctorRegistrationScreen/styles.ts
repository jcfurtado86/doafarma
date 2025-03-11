import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'space-between',
    paddingBottom: 30,
    padding: 20,
    backgroundColor: '#ffffff',
  },

  textContainer: {
    textAlign: 'left',
    //justifyContent: 'space-between',
    marginTop: 80,
    paddingBottom: 50,
  },

  inputContainer: {
    justifyContent: 'space-between',
  },

  buttonContainer: {
    alignItems: 'center',
    justifyContent: 'flex-end',
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
