import { Badge, BadgeVariant } from '@/components/ui';
import { MedicationRequestStatus, REQUEST_STATUS_LABELS } from '@/types/medicationRequest';

const STATUS_TO_VARIANT: Record<MedicationRequestStatus, BadgeVariant> = {
  pending: 'warning',
  confirmed: 'success',
  rejected: 'error',
};

interface RequestStatusBadgeProps {
  status: MedicationRequestStatus;
}

export function RequestStatusBadge({ status }: RequestStatusBadgeProps) {
  const variant = STATUS_TO_VARIANT[status] ?? 'neutral';
  const label = REQUEST_STATUS_LABELS[status] ?? status;
  return <Badge variant={variant} label={label} />;
}
