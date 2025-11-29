import { StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

export const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.yellow_green_50,
    paddingHorizontal: 20,
  },

  textContainer: {
    textAlign: 'left',
    marginTop: 28,
    marginBottom: 24,
    gap: 4,
  },

  inputContainer: {
    justifyContent: 'space-between',
    width: '100%',
  },

  passwordContainer: {
    position: 'relative',
    width: '100%',
  },

  eyeButton: {
    position: 'absolute',
    right: 16,
    top: 12,
    zIndex: 1,
  },

  errorContainer: {
    backgroundColor: '#FEE2E2',
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
  },

  errorText: {
    color: '#DC2626',
    textAlign: 'center',
    fontSize: 14,
  },

  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },

  loadingText: {
    marginTop: 16,
    color: Colors.yellow_green_950,
    fontSize: 16,
  },

  buttonContainer: {
    marginTop: 'auto',
    alignItems: 'center',
    marginBottom: 52,
  },

  termsContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 16,
    paddingHorizontal: 4,
  },

  checkbox: {
    marginRight: 12,
    width: 24,
    height: 24,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: Colors.yellow_green_400,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
  },

  checkboxChecked: {
    backgroundColor: Colors.yellow_green_400,
    borderColor: Colors.yellow_green_400,
  },

  termsText: {
    flex: 1,
    fontSize: 14,
    color: Colors.yellow_green_800,
    lineHeight: 20,
  },

  termsLink: {
    color: Colors.yellow_green_600,
    textDecorationLine: 'underline',
  },

  termsError: {
    color: '#DC2626',
    fontSize: 12,
    marginTop: -8,
    marginBottom: 8,
    paddingHorizontal: 4,
  },
});
