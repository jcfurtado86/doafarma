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
    borderLeftColor: colors.primary,
  },
  expiredCard: {
    borderLeftColor: colors.error,
    backgroundColor: colors.errorSurface,
  },
  pressableContent: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  content: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 4,
  },
  drugName: {
    fontSize: 16,
    fontWeight: 'bold',
    color: colors.textPrimary,
    flex: 1,
  },
  expiredLabel: {
    fontSize: 10,
    fontWeight: 'bold',
    color: colors.error,
    backgroundColor: colors.errorSurface,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  substance: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 8,
  },
  details: {
    gap: 2,
  },
  detailText: {
    fontSize: 13,
    color: colors.textSecondary,
  },
  expiredText: {
    color: colors.error,
    fontWeight: '600',
  },
  label: {
    fontWeight: '600',
    color: colors.textPrimary,
  },
  doctorText: {
    fontSize: 12,
    color: colors.textMuted,
    marginTop: 6,
  },
  chevron: {
    marginLeft: 12,
  },
  chevronText: {
    fontSize: 18,
    color: colors.textMuted,
  },
});
