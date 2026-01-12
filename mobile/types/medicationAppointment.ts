import { MedicationRequest } from './medicationRequest';

export type MedicationAppointmentStatus = 'scheduled' | 'completed';

export interface Address {
  id: number;
  location_name: string;
  full_address: string;
  complement?: string;
  cep: string;
}

export interface MedicationAppointment {
  id: number;
  scheduled_date: string;
  scheduled_time: string;
  status: MedicationAppointmentStatus;
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

export const APPOINTMENT_STATUS_LABELS: Record<MedicationAppointmentStatus, string> = {
  scheduled: 'Agendado',
  completed: 'Concluído',
};

export const APPOINTMENT_STATUS_COLORS: Record<MedicationAppointmentStatus, string> = {
  scheduled: '#3b82f6', // blue
  completed: '#10b981', // green
};
