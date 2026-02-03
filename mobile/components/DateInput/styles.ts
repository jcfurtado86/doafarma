import { StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

export const styles = StyleSheet.create({
  container: {
    width: '100%',
  },
  input: {
    borderWidth: 1,
    borderColor: Colors.gray_300,
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    color: Colors.gray_800,
    backgroundColor: Colors.white,
  },
  inputError: {
    borderColor: Colors.red_500,
  },
  errorText: {
    color: Colors.red_500,
    fontSize: 12,
    marginTop: 4,
  },
});
