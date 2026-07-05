import { z } from 'zod';

const LOT_NUMBER_REGEX = /^[A-Z0-9\-]+$/i;
const MIN_QUANTITY = 1;
const MAX_QUANTITY = 100000;
const MAX_YEARS_AHEAD = 10;

const validateDateString = (dateString: string): boolean => {
  if (dateString.length !== 10) return false;

  const [day, month, year] = dateString.split('/').map(Number);

  if (!day || !month || !year) return false;
  if (day < 1 || day > 31) return false;
  if (month < 1 || month > 12) return false;
  if (year < 2000 || year > 3000) return false;

  const date = new Date(year, month - 1, day);

  if (date.getDate() !== day || date.getMonth() !== month - 1 || date.getFullYear() !== year) {
    return false;
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (date <= today) return false;

  const maxDate = new Date();
  maxDate.setFullYear(today.getFullYear() + MAX_YEARS_AHEAD);

  if (date > maxDate) return false;

  return true;
};

export const medicationOfferingValidation = {
  drugId: z.string({ error: 'Medicamento é obrigatório' }).min(1, 'Selecione um medicamento'),

  lotNumber: z
    .string({ error: 'Número do lote é obrigatório' })
    .max(255, 'Número do lote não pode ter mais de 255 caracteres')
    .regex(LOT_NUMBER_REGEX, 'Número do lote deve conter apenas letras, números e hífens'),

  expiresAt: z
    .string({ error: 'Data de vencimento é obrigatória' })
    .length(10, 'Data deve estar no formato DD/MM/AAAA')
    .refine(validateDateString, {
      error: 'Data inválida. Deve ser futura e no máximo 10 anos à frente',
    }),

  quantity: z.string({ error: 'Quantidade é obrigatória' }).refine(
    (val) => {
      const num = parseInt(val, 10);
      return !isNaN(num) && num >= MIN_QUANTITY && num <= MAX_QUANTITY;
    },
    {
      error: `Quantidade deve ser entre ${MIN_QUANTITY} e ${MAX_QUANTITY.toLocaleString('pt-BR')}`,
    }
  ),
};

export const createMedicationOfferingSchema = z.object({
  drug_id: medicationOfferingValidation.drugId,
  lot_number: medicationOfferingValidation.lotNumber,
  expires_at: medicationOfferingValidation.expiresAt,
  quantity: medicationOfferingValidation.quantity,
});

export const updateMedicationOfferingSchema = z.object({
  drug_id: medicationOfferingValidation.drugId,
  lot_number: medicationOfferingValidation.lotNumber,
  expires_at: medicationOfferingValidation.expiresAt,
  quantity: medicationOfferingValidation.quantity,
});

export type CreateMedicationOfferingFormData = z.infer<typeof createMedicationOfferingSchema>;
export type UpdateMedicationOfferingFormData = z.infer<typeof updateMedicationOfferingSchema>;
