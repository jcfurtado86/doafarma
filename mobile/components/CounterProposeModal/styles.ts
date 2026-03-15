import { StyleSheet } from 'react-native';
import { colors } from '@/theme/tokens';

export const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'flex-end',
  },
  modalContainer: {
    backgroundColor: colors.surface,
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
    borderBottomColor: colors.border,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: colors.textPrimary,
  },
  closeButton: {
    padding: 4,
  },
  closeButtonText: {
    fontSize: 24,
    color: colors.textSecondary,
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
    color: colors.textPrimary,
    marginBottom: 8,
  },
  hint: {
    fontSize: 12,
    color: colors.textSecondary,
    marginBottom: 12,
  },
  infoBox: {
    backgroundColor: colors.infoLight,
    padding: 12,
    borderRadius: 8,
    marginTop: 8,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.info,
    marginBottom: 6,
  },
  infoText: {
    fontSize: 13,
    color: colors.info,
    marginBottom: 4,
  },
  footer: {
    flexDirection: 'row',
    padding: 16,
    gap: 12,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  cancelButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  submitButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    backgroundColor: colors.primaryPressed,
    alignItems: 'center',
  },
  submitButtonDisabled: {
    backgroundColor: colors.textMuted,
  },
  submitButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.textInverted,
  },
});
