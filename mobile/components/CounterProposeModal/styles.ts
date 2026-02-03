import { StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

export const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContainer: {
    backgroundColor: Colors.white,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '90%',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: Colors.gray_200,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: Colors.gray_800,
  },
  closeButton: {
    padding: 4,
  },
  closeButtonText: {
    fontSize: 24,
    color: Colors.gray_500,
  },
  content: {
    padding: 20,
  },
  section: {
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.gray_700,
    marginBottom: 8,
  },
  hint: {
    fontSize: 12,
    color: Colors.gray_500,
    marginBottom: 12,
  },
  input: {
    borderWidth: 1,
    borderColor: Colors.gray_300,
    borderRadius: 8,
    padding: 12,
    backgroundColor: Colors.white,
    fontSize: 16,
    color: Colors.gray_800,
  },
  addressOption: {
    flexDirection: 'row',
    padding: 12,
    borderWidth: 1,
    borderColor: Colors.gray_300,
    borderRadius: 8,
    marginBottom: 8,
    alignItems: 'flex-start',
  },
  addressOptionSelected: {
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
  infoBox: {
    backgroundColor: Colors.blue_50,
    padding: 12,
    borderRadius: 8,
    marginTop: 8,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.blue_800,
    marginBottom: 6,
  },
  infoText: {
    fontSize: 13,
    color: Colors.blue_900,
    marginBottom: 4,
  },
  footer: {
    flexDirection: 'row',
    padding: 16,
    gap: 12,
    borderTopWidth: 1,
    borderTopColor: Colors.gray_200,
  },
  cancelButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: Colors.gray_300,
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: Colors.gray_500,
  },
  submitButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    backgroundColor: Colors.yellow_green_500,
    alignItems: 'center',
  },
  submitButtonDisabled: {
    backgroundColor: Colors.gray_400,
  },
  submitButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: Colors.white,
  },
});
