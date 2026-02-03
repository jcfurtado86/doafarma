import { StyleSheet } from 'react-native';
import { Colors } from '@/constants/Colors';

export const styles = StyleSheet.create({
  card: {
    backgroundColor: Colors.white,
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: Colors.black,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    borderLeftWidth: 4,
    borderLeftColor: Colors.blue_500,
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
    color: Colors.gray_800,
    flex: 1,
  },
  scheduleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.blue_50,
    padding: 12,
    borderRadius: 8,
    marginBottom: 12,
    gap: 16,
  },
  scheduleDate: {
    fontSize: 16,
    fontWeight: '600',
    color: Colors.blue_700,
  },
  scheduleTime: {
    fontSize: 16,
    fontWeight: '600',
    color: Colors.blue_700,
  },
  addressContainer: {
    marginBottom: 8,
  },
  addressTitle: {
    fontSize: 12,
    color: Colors.gray_500,
    marginBottom: 4,
  },
  addressName: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.gray_800,
  },
  addressText: {
    fontSize: 13,
    color: Colors.gray_600,
  },
  details: {
    marginBottom: 4,
  },
  detailText: {
    fontSize: 14,
    color: Colors.gray_500,
    marginBottom: 4,
  },
  label: {
    fontWeight: '600',
    color: Colors.gray_700,
  },
  divider: {
    height: 1,
    backgroundColor: Colors.gray_200,
    marginVertical: 8,
  },
  statusContainer: {
    backgroundColor: '#fef3c7',
    padding: 10,
    borderRadius: 8,
    marginTop: 8,
  },
  statusText: {
    fontSize: 13,
    color: '#92400e',
    textAlign: 'center',
    fontWeight: '500',
  },
  proposalStatusContainer: {
    backgroundColor: '#fff7ed',
    borderLeftWidth: 3,
    borderLeftColor: '#f59e0b',
  },
  proposalStatusText: {
    fontSize: 13,
    color: '#92400e',
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
    backgroundColor: Colors.yellow_green_500,
  },
  counterProposeButton: {
    backgroundColor: Colors.blue_500,
  },
  confirmButton: {
    backgroundColor: Colors.yellow_green_500,
  },
  buttonText: {
    color: Colors.white,
    fontWeight: '600',
    fontSize: 14,
  },
});
