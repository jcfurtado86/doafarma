import { StyleSheet } from 'react-native';
import { colors } from '@/theme/tokens';

export const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: colors.black,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    borderLeftWidth: 4,
    borderLeftColor: colors.info,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
    gap: 12,
  },
  drugName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: colors.textPrimary,
    flex: 1,
  },
  scheduleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.infoLight,
    padding: 12,
    borderRadius: 8,
    marginBottom: 12,
    gap: 16,
  },
  scheduleDate: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.info,
  },
  scheduleTime: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.info,
  },
  addressContainer: {
    marginBottom: 8,
  },
  addressTitle: {
    fontSize: 12,
    color: colors.textSecondary,
    marginBottom: 4,
  },
  addressName: {
    fontSize: 14,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  addressText: {
    fontSize: 13,
    color: colors.textSecondary,
  },
  details: {
    marginBottom: 4,
  },
  detailText: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 4,
  },
  label: {
    fontWeight: '600',
    color: colors.textPrimary,
  },
  divider: {
    height: 1,
    backgroundColor: colors.border,
    marginVertical: 8,
  },
  statusContainer: {
    backgroundColor: colors.warningSurface,
    padding: 10,
    borderRadius: 8,
    marginTop: 8,
  },
  statusText: {
    fontSize: 13,
    color: colors.warningText,
    textAlign: 'center',
    fontWeight: '500',
  },
  proposalStatusContainer: {
    backgroundColor: colors.warningSurface,
    borderLeftWidth: 3,
    borderLeftColor: colors.warning,
  },
  proposalStatusText: {
    fontSize: 13,
    color: colors.warningText,
    textAlign: 'center',
    fontWeight: '600',
  },
  actions: {
    marginTop: 12,
    flexDirection: 'row',
    gap: 8,
  },
  button: {
    flex: 1,
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  acceptButton: {
    backgroundColor: colors.primaryPressed,
  },
  counterProposeButton: {
    backgroundColor: colors.info,
  },
  confirmButton: {
    backgroundColor: colors.primaryPressed,
  },
  buttonText: {
    color: colors.textInverted,
    fontWeight: '600',
    fontSize: 14,
  },
});
