import { Colors } from '@/constants/Colors';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
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
});
