import { MedicationAppointment } from '@/types/medicationAppointment';

export type Variant = 'doctor' | 'receptor';

/**
 * Verifica se a data agendada já passou (comparando apenas datas, sem hora)
 */
export function isScheduledDatePassed(scheduledDate: string): boolean {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const scheduled = new Date(scheduledDate);
  scheduled.setHours(0, 0, 0, 0);

  return scheduled <= today;
}

/**
 * Verifica se o usuário pode responder (aceitar/contrapropor) ao agendamento
 *
 * Condições:
 * - Status deve ser 'proposed'
 * - Usuário NÃO pode ter feito a última proposta
 */
export function canRespond(appointment: MedicationAppointment, variant: Variant): boolean {
  if (appointment.status !== 'proposed') {
    return false;
  }

  return variant === 'doctor'
    ? appointment.proposed_by === 'receptor'
    : appointment.proposed_by === 'doctor';
}

/**
 * Verifica se o usuário pode confirmar a entrega
 *
 * Condições:
 * - Status deve ser 'confirmed'
 * - Data agendada deve ter passado
 * - Usuário NÃO pode ter confirmado ainda
 */
export function canConfirm(appointment: MedicationAppointment, variant: Variant): boolean {
  if (appointment.status !== 'confirmed') {
    return false;
  }

  if (!isScheduledDatePassed(appointment.scheduled_date)) {
    return false;
  }

  return variant === 'receptor' ? !appointment.receptor_confirmed : !appointment.doctor_confirmed;
}

/**
 * Retorna mensagem de status da proposta
 */
export function getProposalStatus(
  appointment: MedicationAppointment,
  variant: Variant
): string | null {
  if (appointment.status !== 'proposed') {
    return null;
  }

  if (canRespond(appointment, variant)) {
    return variant === 'doctor'
      ? 'Proposta do paciente - Responda abaixo'
      : 'Proposta do médico - Responda abaixo';
  }

  return variant === 'doctor' ? 'Aguardando resposta do paciente' : 'Aguardando resposta do médico';
}

/**
 * Retorna mensagem de status da confirmação
 */
export function getConfirmationStatus(
  appointment: MedicationAppointment,
  variant: Variant
): string | null {
  if (appointment.status === 'completed') {
    return 'Entrega concluída';
  }

  if (appointment.status !== 'confirmed') {
    return null;
  }

  const { receptor_confirmed, doctor_confirmed } = appointment;

  if (variant === 'receptor') {
    if (receptor_confirmed) {
      return doctor_confirmed ? 'Aguardando...' : 'Você confirmou, aguardando o médico';
    }
    return doctor_confirmed ? 'Médico confirmou, confirme sua entrega' : null;
  }

  // variant === 'doctor'
  if (doctor_confirmed) {
    return receptor_confirmed ? 'Aguardando...' : 'Você confirmou, aguardando o paciente';
  }
  return receptor_confirmed ? 'Paciente confirmou, confirme a entrega' : null;
}

/**
 * Formata data do agendamento para exibição (DD/MM/AAAA)
 */
export function formatAppointmentDate(dateString: string): string {
  const [year, month, day] = dateString.split('-');
  return `${day}/${month}/${year}`;
}

/**
 * Formata hora do agendamento para exibição (HH:MM)
 */
export function formatAppointmentTime(timeString: string): string {
  return timeString.substring(0, 5);
}
