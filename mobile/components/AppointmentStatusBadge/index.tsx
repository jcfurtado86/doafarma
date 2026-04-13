import { Badge, BadgeVariant } from '@/components/ui';
import {
  MedicationAppointmentStatus,
  APPOINTMENT_STATUS_LABELS,
} from '@/types/medicationAppointment';

const STATUS_TO_VARIANT: Record<MedicationAppointmentStatus, BadgeVariant> = {
  proposed: 'warning',
  confirmed: 'info',
  completed: 'success',
};

interface AppointmentStatusBadgeProps {
  status: MedicationAppointmentStatus;
}

export function AppointmentStatusBadge({ status }: AppointmentStatusBadgeProps) {
  const variant = STATUS_TO_VARIANT[status] ?? 'neutral';
  const label = APPOINTMENT_STATUS_LABELS[status] ?? status;
  return <Badge variant={variant} label={label} />;
}
