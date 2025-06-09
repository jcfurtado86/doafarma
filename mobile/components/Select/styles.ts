import { Colors } from '@/constants/Colors';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  container: {
    display: 'flex',
    width: '100%',
  },

  selectContainer: {
    display: 'flex',
    justifyContent: 'center',
    width: '100%',
    height: 48,
    borderRadius: 10,
    fontSize: 16,
    outlineColor: Colors.yellow_green_400,
    backgroundColor: '#F7F7F7',
  },

  selectContainerFocused: {
    borderWidth: 1,
    borderColor: Colors.yellow_green_400,
  },

  selectContainerError: {
    borderWidth: 1,
    borderColor: '#E53935',
  },

  errorText: {
    color: '#E53935',
    fontSize: 12,
    marginTop: 2,
    marginLeft: 4,
  },
});
