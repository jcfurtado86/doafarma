import React, { useRef } from 'react';
import { View, TextInput } from 'react-native';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';
import { UseFormSetError } from 'react-hook-form';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { InputRow } from '@/components/InputRow';
import { Select, SelectItem } from '@/components/Select';
import { Picker } from '@react-native-picker/picker';
import { DoctorRegistrationFormData } from '@/stores/doctorRegistrationFormStore';
import { BRAZILIAN_STATES } from '@/constants/BrazilianStates';
import { z } from 'zod';
import { useFeatureForm } from '@/hooks/useFeatureForm';

interface DoctorPersonalDataStepProps {
  onSubmit: (data: Partial<DoctorRegistrationFormData>, setError: UseFormSetError<any>) => void;
}

const doctorPersonalDataSchema = z
  .object({
    name: z
      .string({ error: 'Nome é obrigatório' })
      .max(255, 'Nome não pode ter mais de 255 caracteres'),
    crm: z
      .string({ error: 'CRM é obrigatório' })
      .min(4, 'CRM deve ter no mínimo 4 dígitos')
      .max(10, 'CRM deve ter no máximo 10 dígitos')
      .regex(/^[0-9]{4,10}$/, 'CRM deve conter apenas dígitos numéricos (4 a 10)'),
    crm_uf: z
      .string({ error: 'UF é obrigatório' })
      .length(2, 'UF deve ter exatamente 2 caracteres'),
    ddd: z.string().nonempty('DDD é obrigatório').max(3, 'DDD não pode ter mais de 3 caracteres'),
    phone_number: z
      .string({ error: 'Telefone é obrigatório' })
      .max(9, 'Telefone não pode ter mais de 9 caracteres'),
    email: z
      .email({
        error: (issue) => (issue.input === undefined ? 'Email é obrigatório' : 'Email inválido'),
      })
      .max(255, 'Email não pode ter mais de 255 caracteres'),
    password: z
      .string({ error: 'Senha é obrigatória' })
      .min(8, 'Senha deve ter pelo menos 8 caracteres')
      .max(255, 'Senha não pode ter mais de 255 caracteres'),
    password_confirmation: z
      .string({ error: 'Confirmação de senha é obrigatória' })
      .max(255, 'Confirmação de senha não pode ter mais de 255 caracteres'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    error: 'As senhas não coincidem',
    path: ['password_confirmation'],
  });

type DoctorPersonalFormData = z.infer<typeof doctorPersonalDataSchema>;

export function DoctorPersonalDataStep({ onSubmit }: DoctorPersonalDataStepProps) {
  const {
    control,
    handleSubmit,
    formState: { errors },
    setError,
  } = useFeatureForm<DoctorPersonalFormData>({
    schema: doctorPersonalDataSchema,
  });

  function handleNextStep(data: DoctorPersonalFormData) {
    onSubmit(data, setError);
  }

  const crmRef = useRef<TextInput>(null);
  const crmUf = useRef<Picker<string | number>>(null);
  const dddRef = useRef<TextInput>(null);
  const phoneRef = useRef<TextInput>(null);
  const emailRef = useRef<TextInput>(null);
  const passwordRef = useRef<TextInput>(null);
  const passwordConfirmationRef = useRef<TextInput>(null);

  return (
    <>
      <View style={styles.textContainer}>
        <Title>Olá doutor!</Title>
        <Caption>Preencha seus dados pessoais</Caption>
      </View>
      <View style={styles.inputContainer}>
        <Input
          formProps={{
            name: 'name',
            control: control,
          }}
          inputProps={{
            returnKeyType: 'next',
            placeholder: 'Nome Completo',
            onSubmitEditing: () => crmRef.current?.focus(),
          }}
          error={errors.name?.message}
        />

        <InputRow>
          <Input
            ref={crmRef}
            formProps={{
              name: 'crm',
              control: control,
            }}
            inputProps={{
              returnKeyType: 'next',
              placeholder: 'CRM',
              keyboardType: 'numeric',
              onSubmitEditing: () => crmUf.current?.focus(),
            }}
            error={errors.crm?.message}
            containerStyle={{ flex: 2 }}
          />
          <Select
            ref={crmUf}
            formProps={{
              name: 'crm_uf',
              control: control,
            }}
            selectProps={{
              placeholder: 'UF',
            }}
            error={errors.crm_uf?.message}
            containerStyle={{ flex: 1 }}
            nextRef={dddRef}
          >
            {BRAZILIAN_STATES.map((state) => (
              <SelectItem key={state.value} label={state.label} value={state.value} />
            ))}
          </Select>
        </InputRow>
        <InputRow>
          <Input
            ref={dddRef}
            formProps={{
              name: 'ddd',
              control: control,
            }}
            inputProps={{
              returnKeyType: 'next',
              placeholder: 'DDD',
              keyboardType: 'numeric',
              onSubmitEditing: () => phoneRef.current?.focus(),
            }}
            error={errors.ddd?.message}
            containerStyle={{ flex: 1 }}
          />
          <Input
            ref={phoneRef}
            formProps={{
              name: 'phone_number',
              control: control,
            }}
            inputProps={{
              placeholder: 'Telefone',
              returnKeyType: 'next',
              keyboardType: 'numeric',
              onSubmitEditing: () => emailRef.current?.focus(),
            }}
            error={errors.phone_number?.message}
            containerStyle={{ flex: 3 }}
          />
        </InputRow>

        <Input
          ref={emailRef}
          formProps={{
            name: 'email',
            control: control,
          }}
          inputProps={{
            placeholder: 'Email',
            returnKeyType: 'next',
            onSubmitEditing: () => passwordRef.current?.focus(),
          }}
          error={errors.email?.message}
        />
        <Input
          ref={passwordRef}
          formProps={{
            name: 'password',
            control: control,
          }}
          inputProps={{
            placeholder: 'Senha',
            secureTextEntry: true,
            returnKeyType: 'next',
            onSubmitEditing: () => passwordConfirmationRef.current?.focus(),
          }}
          error={errors.password?.message}
        />
        <Input
          ref={passwordConfirmationRef}
          formProps={{
            name: 'password_confirmation',
            control: control,
          }}
          inputProps={{
            placeholder: 'Confirmar Senha',
            secureTextEntry: true,
            returnKeyType: 'done',
            onSubmitEditing: () => handleSubmit(handleNextStep)(),
          }}
          error={errors.password_confirmation?.message}
        />
      </View>
      <View style={styles.buttonContainer}>
        <PrimaryButton onPress={() => handleSubmit(handleNextStep)()} label="Próximo" />
      </View>
    </>
  );
}
