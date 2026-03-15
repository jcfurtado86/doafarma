import { colors } from '@/theme/tokens';
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
    backgroundColor: colors.surfaceSecondary,
  },

  triggerFocused: {
    borderWidth: 1,
    borderColor: colors.primary,
  },

  triggerError: {
    borderWidth: 1,
    borderColor: colors.errorBorder,
  },

  triggerDisabled: {
    opacity: 0.5,
  },

  triggerText: {
    fontSize: 16,
    color: colors.textPrimary,
    paddingVertical: 12,
    paddingLeft: 16,
  },

  placeholderText: {
    color: colors.textPlaceholder,
  },

  loadingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingLeft: 16,
    gap: 8,
  },

  loadingText: {
    fontSize: 14,
    color: colors.textPlaceholder,
  },

  errorText: {
    color: colors.errorBorder,
    fontSize: 12,
    marginTop: 2,
    marginLeft: 4,
  },

  loadErrorText: {
    color: colors.errorBorder,
    fontSize: 12,
    marginTop: 2,
    marginLeft: 4,
  },

  modalOverlay: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'center',
    padding: 24,
  },

  modalContent: {
    backgroundColor: colors.white,
    borderRadius: 12,
    maxHeight: '70%',
    overflow: 'hidden',
  },

  searchInput: {
    height: 48,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    paddingHorizontal: 16,
    fontSize: 16,
    color: colors.textPrimary,
  },

  optionItem: {
    paddingVertical: 16,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },

  optionText: {
    fontSize: 16,
    color: colors.textPrimary,
  },

  optionTextSelected: {
    color: colors.primaryPressed,
    fontWeight: 'bold',
  },

  emptyContainer: {
    paddingVertical: 32,
    alignItems: 'center',
  },

  emptyText: {
    fontSize: 14,
    color: colors.textPlaceholder,
  },

  cancelButton: {
    paddingVertical: 16,
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },

  cancelText: {
    color: colors.textPlaceholder,
    fontSize: 14,
  },
});
