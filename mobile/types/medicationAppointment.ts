import { colors } from '@/theme/tokens';
import { Address } from './address';
import { MedicationRequest } from './medicationRequest';

export type MedicationAppointmentStatus = 'proposed' | 'confirmed' | 'completed';
export type ProposedBy = 'receptor' | 'doctor';

export type { Address };

export interface MedicationAppointment {
  id: number;
  scheduled_date: string;
  scheduled_time: string;
  status: MedicationAppointmentStatus;
  proposed_by: ProposedBy;
  receptor_confirmed: boolean;
  doctor_confirmed: boolean;
  created_at: string;
  updated_at: string;
  medication_request?: MedicationRequest;
  address?: Address;
}

export interface CreateMedicationAppointmentData {
  medication_request_id: number;
  scheduled_date: string;
  scheduled_time: string;
}

export interface CounterProposeAppointmentData {
  scheduled_date: string;
  scheduled_time: string;
  address_id?: number;
}

export const APPOINTMENT_STATUS_LABELS: Record<MedicationAppointmentStatus, string> = {
  proposed: 'Proposto',
  confirmed: 'Confirmado',
  completed: 'Concluído',
};

export const APPOINTMENT_STATUS_COLORS: Record<MedicationAppointmentStatus, string> = {
  proposed: colors.statusProposed,
  confirmed: colors.statusConfirmed,
  completed: colors.statusCompleted,
};
