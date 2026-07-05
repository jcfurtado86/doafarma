import { z } from 'zod';
import { ValidationMessages } from '@/constants/ValidationMessages';
import { isDateInPast, isValidDateFormat } from './dateHelpers';
import { isValidTimeFormat } from './timeHelpers';

export const counterProposeSchema = z.object({
  scheduled_date: z
    .string({ error: 'Data é obrigatória' })
    .refine(isValidDateFormat, { error: ValidationMessages.date.invalid })
    .refine((date) => !isDateInPast(date), {
      error: ValidationMessages.date.mustBeTodayOrFuture,
    }),
  scheduled_time: z
    .string({ error: 'Horário é obrigatório' })
    .refine(isValidTimeFormat, { error: ValidationMessages.time.invalid }),
});

export type CounterProposeFormData = z.infer<typeof counterProposeSchema>;
