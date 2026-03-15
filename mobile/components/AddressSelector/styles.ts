import { StyleSheet } from 'react-native';
import { colors } from '@/theme/tokens';

export const styles = StyleSheet.create({
  container: {
    gap: 8,
  },
  option: {
    flexDirection: 'row',
    padding: 12,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    alignItems: 'flex-start',
  },
  optionSelected: {
    borderColor: colors.primaryPressed,
    backgroundColor: colors.successSurface,
  },
  radioButton: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: colors.border,
    marginRight: 12,
    marginTop: 2,
    justifyContent: 'center',
    alignItems: 'center',
  },
  radioButtonSelected: {
    borderColor: colors.primaryPressed,
  },
  radioButtonInner: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: colors.primaryPressed,
  },
  addressInfo: {
    flex: 1,
  },
  addressName: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
    marginBottom: 2,
  },
  addressText: {
    fontSize: 13,
    color: colors.textSecondary,
  },
});
