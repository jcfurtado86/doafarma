import { MedicationOfferingSearchResult } from './medicationOffering';

export type MedicationRequestStatus = 'pending' | 'confirmed' | 'rejected';

export interface Receptor {
  id: number;
  name: string;
  email: string;
  phone_number?: string;
}

export interface MedicationRequest {
  id: number;
  status: MedicationRequestStatus;
  created_at: string;
  updated_at: string;
  medication_offering?: MedicationOfferingSearchResult;
  receptor?: Receptor;
}

export interface CreateMedicationRequestData {
  medication_offering_id: number;
}

export const REQUEST_STATUS_LABELS: Record<MedicationRequestStatus, string> = {
  pending: 'Pendente',
  confirmed: 'Confirmada',
  rejected: 'Recusada',
};

export const REQUEST_STATUS_COLORS: Record<MedicationRequestStatus, string> = {
  pending: '#f59e0b', // amber/yellow
  confirmed: '#10b981', // green
  rejected: '#ef4444', // red
};
