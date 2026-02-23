import { Colors } from '@/constants/Colors';
import { StyleSheet } from 'react-native';

export const styles = StyleSheet.create({
  container: {
    width: '100%',
  },

  trigger: {
    justifyContent: 'center',
    width: '100%',
    height: 48,
    borderRadius: 10,
    backgroundColor: '#F7F7F7',
  },

  triggerFocused: {
    borderWidth: 1,
    borderColor: Colors.yellow_green_400,
  },

  triggerError: {
    borderWidth: 1,
    borderColor: '#E53935',
  },

  triggerDisabled: {
    opacity: 0.5,
  },

  triggerText: {
    fontSize: 16,
    color: '#222',
    paddingVertical: 12,
    paddingLeft: 16,
  },

  placeholderText: {
    color: '#AFB2BF',
  },

  loadingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingLeft: 16,
    gap: 8,
  },

  loadingText: {
    fontSize: 14,
    color: '#AFB2BF',
  },

  errorText: {
    color: '#E53935',
    fontSize: 12,
    marginTop: 2,
    marginLeft: 4,
  },

  loadErrorText: {
    color: '#E53935',
    fontSize: 12,
    marginTop: 2,
    marginLeft: 4,
  },

  modalOverlay: {
    flex: 1,
    backgroundColor: Colors.overlay,
    justifyContent: 'center',
    padding: 24,
  },

  modalContent: {
    backgroundColor: Colors.white,
    borderRadius: 12,
    maxHeight: '70%',
    overflow: 'hidden',
  },

  searchInput: {
    height: 48,
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
    paddingHorizontal: 16,
    fontSize: 16,
    color: '#222',
  },

  optionItem: {
    paddingVertical: 16,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
  },

  optionText: {
    fontSize: 16,
    color: '#222',
  },

  optionTextSelected: {
    color: Colors.yellow_green_500,
    fontWeight: 'bold',
  },

  emptyContainer: {
    paddingVertical: 32,
    alignItems: 'center',
  },

  emptyText: {
    fontSize: 14,
    color: '#AFB2BF',
  },

  cancelButton: {
    paddingVertical: 16,
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },

  cancelText: {
    color: '#AFB2BF',
    fontSize: 14,
  },
});
