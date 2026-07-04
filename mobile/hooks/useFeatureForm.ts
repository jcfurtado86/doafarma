import { useForm, UseFormProps, UseFormReturn } from 'react-hook-form';
import { ZodType } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { ENABLE_FRONTEND_VALIDATION } from '@/config/featureFlags';

export function useFeatureForm<T extends Record<string, any>>(
  props: UseFormProps<T> & { schema?: ZodType<T, T> }
): UseFormReturn<T> {
  const { schema, ...rest } = props;
  return useForm<T>({
    ...rest,
    resolver: ENABLE_FRONTEND_VALIDATION && schema ? zodResolver(schema) : undefined,
  });
}
