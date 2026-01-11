import { StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

export const styles = StyleSheet.create({
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    borderLeftWidth: 4,
    borderLeftColor: Colors.yellow_green_400,
  },
  expiredCard: {
    borderLeftColor: '#ef4444',
    backgroundColor: '#fef2f2',
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
    color: '#1f2937',
    flex: 1,
  },
  expiredLabel: {
    fontSize: 10,
    fontWeight: 'bold',
    color: '#ef4444',
    backgroundColor: '#fecaca',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  substance: {
    fontSize: 14,
    color: '#6b7280',
    marginBottom: 8,
  },
  details: {
    gap: 2,
  },
  detailText: {
    fontSize: 13,
    color: '#6b7280',
  },
  expiredText: {
    color: '#ef4444',
    fontWeight: '600',
  },
  label: {
    fontWeight: '600',
    color: '#374151',
  },
  doctorText: {
    fontSize: 12,
    color: '#9ca3af',
    marginTop: 6,
  },
  chevron: {
    marginLeft: 12,
  },
  chevronText: {
    fontSize: 18,
    color: '#9ca3af',
  },
});
