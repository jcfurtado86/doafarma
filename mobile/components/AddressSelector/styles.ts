import { StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

export const styles = StyleSheet.create({
  container: {
    gap: 8,
  },
  option: {
    flexDirection: 'row',
    padding: 12,
    borderWidth: 1,
    borderColor: Colors.gray_300,
    borderRadius: 8,
    alignItems: 'flex-start',
  },
  optionSelected: {
    borderColor: Colors.yellow_green_500,
    backgroundColor: Colors.green_50,
  },
  radioButton: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: Colors.gray_300,
    marginRight: 12,
    marginTop: 2,
    justifyContent: 'center',
    alignItems: 'center',
  },
  radioButtonSelected: {
    borderColor: Colors.yellow_green_500,
  },
  radioButtonInner: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: Colors.yellow_green_500,
  },
  addressInfo: {
    flex: 1,
  },
  addressName: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.gray_800,
    marginBottom: 2,
  },
  addressText: {
    fontSize: 13,
    color: Colors.gray_500,
  },
});
