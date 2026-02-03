import {
  isScheduledDatePassed,
  canRespond,
  canConfirm,
  getProposalStatus,
  getConfirmationStatus,
  formatAppointmentDate,
  formatAppointmentTime,
} from '@/utils/businessRules/appointmentRules';
import { MedicationAppointment } from '@/types/medicationAppointment';

const createAppointment = (
  overrides: Partial<MedicationAppointment> = {}
): MedicationAppointment => ({
  id: 1,
  scheduled_date: '2026-02-15',
  scheduled_time: '14:30:00',
  status: 'proposed',
  proposed_by: 'doctor',
  receptor_confirmed: false,
  doctor_confirmed: false,
  created_at: '2026-02-01T10:00:00Z',
  updated_at: '2026-02-01T10:00:00Z',
  ...overrides,
});

describe('appointmentRules', () => {
  describe('isScheduledDatePassed', () => {
    it('retorna true quando data já passou', () => {
      expect(isScheduledDatePassed('2020-01-01')).toBe(true);
    });

    it('retorna true quando data é hoje', () => {
      const today = new Date().toISOString().split('T')[0];
      expect(isScheduledDatePassed(today)).toBe(true);
    });

    it('retorna false quando data é futura', () => {
      expect(isScheduledDatePassed('2030-12-31')).toBe(false);
    });
  });

  describe('canRespond', () => {
    it('retorna false quando status não é proposed', () => {
      const appointment = createAppointment({ status: 'confirmed' });
      expect(canRespond(appointment, 'doctor')).toBe(false);
      expect(canRespond(appointment, 'receptor')).toBe(false);
    });

    it('doutor pode responder quando receptor fez a proposta', () => {
      const appointment = createAppointment({ proposed_by: 'receptor' });
      expect(canRespond(appointment, 'doctor')).toBe(true);
    });

    it('doutor NÃO pode responder quando ele fez a proposta', () => {
      const appointment = createAppointment({ proposed_by: 'doctor' });
      expect(canRespond(appointment, 'doctor')).toBe(false);
    });

    it('receptor pode responder quando doutor fez a proposta', () => {
      const appointment = createAppointment({ proposed_by: 'doctor' });
      expect(canRespond(appointment, 'receptor')).toBe(true);
    });

    it('receptor NÃO pode responder quando ele fez a proposta', () => {
      const appointment = createAppointment({ proposed_by: 'receptor' });
      expect(canRespond(appointment, 'receptor')).toBe(false);
    });
  });

  describe('canConfirm', () => {
    it('retorna false quando status não é confirmed', () => {
      const appointment = createAppointment({ status: 'proposed' });
      expect(canConfirm(appointment, 'doctor')).toBe(false);
    });

    it('retorna false quando data não passou', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        scheduled_date: '2030-12-31',
      });
      expect(canConfirm(appointment, 'doctor')).toBe(false);
    });

    it('doutor pode confirmar quando não confirmou ainda', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        scheduled_date: '2020-01-01',
        doctor_confirmed: false,
      });
      expect(canConfirm(appointment, 'doctor')).toBe(true);
    });

    it('doutor NÃO pode confirmar quando já confirmou', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        scheduled_date: '2020-01-01',
        doctor_confirmed: true,
      });
      expect(canConfirm(appointment, 'doctor')).toBe(false);
    });

    it('receptor pode confirmar quando não confirmou ainda', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        scheduled_date: '2020-01-01',
        receptor_confirmed: false,
      });
      expect(canConfirm(appointment, 'receptor')).toBe(true);
    });

    it('receptor NÃO pode confirmar quando já confirmou', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        scheduled_date: '2020-01-01',
        receptor_confirmed: true,
      });
      expect(canConfirm(appointment, 'receptor')).toBe(false);
    });
  });

  describe('getProposalStatus', () => {
    it('retorna null quando status não é proposed', () => {
      const appointment = createAppointment({ status: 'confirmed' });
      expect(getProposalStatus(appointment, 'doctor')).toBeNull();
    });

    it('doutor vê mensagem para responder quando receptor fez proposta', () => {
      const appointment = createAppointment({ proposed_by: 'receptor' });
      expect(getProposalStatus(appointment, 'doctor')).toBe(
        'Proposta do paciente - Responda abaixo'
      );
    });

    it('doutor vê mensagem de aguardando quando ele fez proposta', () => {
      const appointment = createAppointment({ proposed_by: 'doctor' });
      expect(getProposalStatus(appointment, 'doctor')).toBe('Aguardando resposta do paciente');
    });

    it('receptor vê mensagem para responder quando doutor fez proposta', () => {
      const appointment = createAppointment({ proposed_by: 'doctor' });
      expect(getProposalStatus(appointment, 'receptor')).toBe(
        'Proposta do médico - Responda abaixo'
      );
    });

    it('receptor vê mensagem de aguardando quando ele fez proposta', () => {
      const appointment = createAppointment({ proposed_by: 'receptor' });
      expect(getProposalStatus(appointment, 'receptor')).toBe('Aguardando resposta do médico');
    });
  });

  describe('getConfirmationStatus', () => {
    it('retorna "Entrega concluída" quando status é completed', () => {
      const appointment = createAppointment({ status: 'completed' });
      expect(getConfirmationStatus(appointment, 'doctor')).toBe('Entrega concluída');
    });

    it('retorna null quando status não é confirmed nem completed', () => {
      const appointment = createAppointment({ status: 'proposed' });
      expect(getConfirmationStatus(appointment, 'doctor')).toBeNull();
    });

    it('receptor: mostra aguardando médico quando receptor já confirmou', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        receptor_confirmed: true,
        doctor_confirmed: false,
      });
      expect(getConfirmationStatus(appointment, 'receptor')).toBe(
        'Você confirmou, aguardando o médico'
      );
    });

    it('receptor: pede confirmação quando médico já confirmou', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        receptor_confirmed: false,
        doctor_confirmed: true,
      });
      expect(getConfirmationStatus(appointment, 'receptor')).toBe(
        'Médico confirmou, confirme sua entrega'
      );
    });

    it('receptor: retorna null quando nenhum confirmou', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        receptor_confirmed: false,
        doctor_confirmed: false,
      });
      expect(getConfirmationStatus(appointment, 'receptor')).toBeNull();
    });

    it('receptor: retorna "Aguardando..." quando ambos confirmaram', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        receptor_confirmed: true,
        doctor_confirmed: true,
      });
      expect(getConfirmationStatus(appointment, 'receptor')).toBe('Aguardando...');
    });

    it('doutor: mostra aguardando paciente quando doutor já confirmou', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        doctor_confirmed: true,
        receptor_confirmed: false,
      });
      expect(getConfirmationStatus(appointment, 'doctor')).toBe(
        'Você confirmou, aguardando o paciente'
      );
    });

    it('doutor: pede confirmação quando paciente já confirmou', () => {
      const appointment = createAppointment({
        status: 'confirmed',
        doctor_confirmed: false,
        receptor_confirmed: true,
      });
      expect(getConfirmationStatus(appointment, 'doctor')).toBe(
        'Paciente confirmou, confirme a entrega'
      );
    });
  });

  describe('formatAppointmentDate', () => {
    it('formata data para DD/MM/AAAA', () => {
      expect(formatAppointmentDate('2026-02-15')).toBe('15/02/2026');
    });
  });

  describe('formatAppointmentTime', () => {
    it('formata hora para HH:MM', () => {
      expect(formatAppointmentTime('14:30:00')).toBe('14:30');
    });

    it('mantém HH:MM se já estiver no formato correto', () => {
      expect(formatAppointmentTime('09:15')).toBe('09:15');
    });
  });
});
