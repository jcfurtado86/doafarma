import { z } from 'zod';

/**
 * Validates Brazilian CPF using the official algorithm.
 * Checks both verification digits.
 */
const validateCPF = (cpf: string): boolean => {
  // Remove non-numeric characters
  const cleanCPF = cpf.replace(/\D/g, '');

  if (cleanCPF.length !== 11) return false;

  // Check for CPFs with all same digits
  if (/^(\d)\1{10}$/.test(cleanCPF)) return false;

  // Validate first check digit
  let sum = 0;
  for (let i = 0; i < 9; i++) {
    sum += parseInt(cleanCPF[i], 10) * (10 - i);
  }
  let remainder = sum % 11;
  let firstCheckDigit = remainder < 2 ? 0 : 11 - remainder;

  if (parseInt(cleanCPF[9], 10) !== firstCheckDigit) return false;

  // Validate second check digit
  sum = 0;
  for (let i = 0; i < 10; i++) {
    sum += parseInt(cleanCPF[i], 10) * (11 - i);
  }
  remainder = sum % 11;
  let secondCheckDigit = remainder < 2 ? 0 : 11 - remainder;

  if (parseInt(cleanCPF[10], 10) !== secondCheckDigit) return false;

  return true;
};

/**
 * Formats CPF with mask: 000.000.000-00
 */
export const formatCPF = (value: string): string => {
  const cleanValue = value.replace(/\D/g, '');
  const limitedValue = cleanValue.slice(0, 11);

  if (limitedValue.length <= 3) {
    return limitedValue;
  }
  if (limitedValue.length <= 6) {
    return `${limitedValue.slice(0, 3)}.${limitedValue.slice(3)}`;
  }
  if (limitedValue.length <= 9) {
    return `${limitedValue.slice(0, 3)}.${limitedValue.slice(3, 6)}.${limitedValue.slice(6)}`;
  }
  return `${limitedValue.slice(0, 3)}.${limitedValue.slice(3, 6)}.${limitedValue.slice(6, 9)}-${limitedValue.slice(9, 11)}`;
};

/**
 * Formats phone number with mask: (00) 00000-0000 or (00) 0000-0000
 */
export const formatPhone = (value: string): string => {
  const cleanValue = value.replace(/\D/g, '');
  const limitedValue = cleanValue.slice(0, 11);

  if (limitedValue.length <= 2) {
    return limitedValue.length ? `(${limitedValue}` : '';
  }
  if (limitedValue.length <= 6) {
    return `(${limitedValue.slice(0, 2)}) ${limitedValue.slice(2)}`;
  }
  if (limitedValue.length <= 10) {
    return `(${limitedValue.slice(0, 2)}) ${limitedValue.slice(2, 6)}-${limitedValue.slice(6)}`;
  }
  return `(${limitedValue.slice(0, 2)}) ${limitedValue.slice(2, 7)}-${limitedValue.slice(7)}`;
};

/**
 * Removes formatting from CPF or phone
 */
export const cleanNumeric = (value: string): string => {
  return value.replace(/\D/g, '');
};

// Validation schemas
export const receptorValidation = {
  name: z
    .string({ required_error: 'Nome é obrigatório' })
    .min(3, 'Nome deve ter pelo menos 3 caracteres')
    .max(255, 'Nome não pode ter mais de 255 caracteres'),

  email: z
    .string({ required_error: 'E-mail é obrigatório' })
    .email('E-mail inválido')
    .max(255, 'E-mail não pode ter mais de 255 caracteres'),

  cpf: z
    .string({ required_error: 'CPF é obrigatório' })
    .refine((val) => cleanNumeric(val).length === 11, {
      message: 'CPF deve ter 11 dígitos',
    })
    .refine((val) => validateCPF(val), {
      message: 'CPF inválido',
    }),

  phone: z.string({ required_error: 'Telefone é obrigatório' }).refine(
    (val) => {
      const clean = cleanNumeric(val);
      return clean.length >= 10 && clean.length <= 11;
    },
    {
      message: 'Telefone deve ter 10 ou 11 dígitos',
    }
  ),

  password: z
    .string({ required_error: 'Senha é obrigatória' })
    .min(8, 'Senha deve ter pelo menos 8 caracteres'),

  passwordConfirmation: z.string({ required_error: 'Confirmação de senha é obrigatória' }),

  termsAccepted: z.boolean().refine((val) => val === true, {
    message: 'Você deve aceitar os termos de uso',
  }),
};

export const receptorRegistrationSchema = z
  .object({
    name: receptorValidation.name,
    email: receptorValidation.email,
    cpf: receptorValidation.cpf,
    phone_number: receptorValidation.phone,
    password: receptorValidation.password,
    password_confirmation: receptorValidation.passwordConfirmation,
    terms_accepted: receptorValidation.termsAccepted,
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'As senhas não conferem',
    path: ['password_confirmation'],
  });

export type ReceptorRegistrationFormData = z.infer<typeof receptorRegistrationSchema>;
