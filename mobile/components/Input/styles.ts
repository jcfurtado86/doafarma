import { Colors } from '@/constants/Colors';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  group: {
    width: '100%',
    height: 48,
    borderRadius: 10,
    alignItems: 'center',
  },
  input: {
    flex: 1,
    height: 48,
    width: '100%',
    borderRadius: 10,
    paddingLeft: 16,
    fontSize: 16,
    outlineColor: Colors.yellow_green_400,
    backgroundColor: '#F7F7F7',
  },
});
