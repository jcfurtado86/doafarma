import { z } from 'zod';
import { ValidationMessages } from '@/constants/ValidationMessages';
import { isDateInPast, isValidDateFormat } from './dateHelpers';
import { isValidTimeFormat } from './timeHelpers';

export const counterProposeSchema = z.object({
  scheduled_date: z
    .string({ required_error: 'Data é obrigatória' })
    .refine(isValidDateFormat, { message: ValidationMessages.date.invalid })
    .refine((date) => !isDateInPast(date), {
      message: ValidationMessages.date.mustBeTodayOrFuture,
    }),
  scheduled_time: z
    .string({ required_error: 'Horário é obrigatório' })
    .refine(isValidTimeFormat, { message: ValidationMessages.time.invalid }),
});

export type CounterProposeFormData = z.infer<typeof counterProposeSchema>;
