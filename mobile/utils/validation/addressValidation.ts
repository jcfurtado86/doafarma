import { z } from 'zod';

export const addressValidation = {
  label: z
    .string({ error: 'Nome do consultório é obrigatório' })
    .min(1, 'Nome do consultório é obrigatório')
    .max(255, 'Nome do consultório não pode exceder 255 caracteres'),

  cep: z.string({ error: 'CEP é obrigatório' }).length(8, 'CEP deve ter exatamente 8 dígitos'),

  uf: z
    .string({ error: 'UF é obrigatório' })
    .min(1, 'UF é obrigatório')
    .max(255, 'UF não pode exceder 255 caracteres'),

  city: z
    .string({ error: 'Cidade é obrigatória' })
    .min(1, 'Cidade é obrigatória')
    .max(255, 'Cidade não pode exceder 255 caracteres'),

  neighborhood: z
    .string({ error: 'Bairro é obrigatório' })
    .min(1, 'Bairro é obrigatório')
    .max(255, 'Bairro não pode exceder 255 caracteres'),

  street: z
    .string({ error: 'Rua ou Avenida é obrigatória' })
    .min(1, 'Rua ou Avenida é obrigatória')
    .max(255, 'Rua ou Avenida não pode exceder 255 caracteres'),

  number: z
    .string({ error: 'Número é obrigatório' })
    .min(1, 'Número é obrigatório')
    .max(20, 'Número não pode exceder 20 caracteres'),

  // Sem transform ''->undefined: o '' precisa chegar no backend pra limpar a coluna
  // no PUT (a regra 'sometimes' ignora chave ausente; ConvertEmptyStringsToNull faz ''->null)
  complement: z.string().max(255, 'Complemento não pode exceder 255 caracteres').optional(),
};

export const addressFormSchema = z.object(addressValidation);

export type AddressFormData = z.infer<typeof addressFormSchema>;
