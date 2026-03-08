import { z } from 'zod';

export const forgotPasswordSchema = z.object({
  email: z
    .string({ required_error: 'E-mail e obrigatorio' })
    .email('E-mail invalido')
    .max(255, 'E-mail nao pode ter mais de 255 caracteres')
    .transform((v) => v.toLowerCase().trim()),
});

export type ForgotPasswordFormData = z.infer<typeof forgotPasswordSchema>;

export const resetPasswordSchema = z
  .object({
    token: z.string({ required_error: 'Token e obrigatorio' }).min(1, 'Token e obrigatorio'),
    email: z
      .string({ required_error: 'E-mail e obrigatorio' })
      .email('E-mail invalido')
      .max(255, 'E-mail nao pode ter mais de 255 caracteres')
      .transform((v) => v.toLowerCase().trim()),
    password: z
      .string({ required_error: 'Senha e obrigatoria' })
      .min(8, 'Senha deve ter pelo menos 8 caracteres'),
    password_confirmation: z.string({
      required_error: 'Confirmacao de senha e obrigatoria',
    }),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'As senhas nao coincidem',
    path: ['password_confirmation'],
  });

export type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;
