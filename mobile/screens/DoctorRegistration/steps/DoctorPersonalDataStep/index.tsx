import React, { useRef } from 'react';
import { View, TextInput } from 'react-native';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';
import { useForm } from 'react-hook-form';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { InputRow } from '@/components/InputRow';
import { Select, SelectItem } from '@/components/Select';
import { Picker } from '@react-native-picker/picker';
import { DoctorRegistrationFormData } from '@/stores/doctorRegistrationFormStore';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

interface DoctorPersonalDataStepProps {
  onSubmit: (data: Partial<DoctorRegistrationFormData>) => void;
}

const doctorPersonalDataSchema = z
  .object({
    name: z.string().min(1, 'Nome é obrigatório'),
    crm: z.string().min(1, 'CRM é obrigatório'),
    crm_uf: z.string().min(1, 'UF é obrigatório'),
    ddd: z.string().min(1, 'DDD é obrigatório'),
    phone_number: z.string().min(1, 'Telefone é obrigatório'),
    email: z.string().email('Email inválido').min(1, 'Email é obrigatório'),
    password: z.string().min(6, 'Senha deve ter pelo menos 6 caracteres'),
    password_confirmation: z.string().min(1, 'Confirmação de senha é obrigatória'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'As senhas não coincidem',
    path: ['password_confirmation'],
  });

type DoctorPersonalFormData = z.infer<typeof doctorPersonalDataSchema>;

export function DoctorPersonalDataStep({ onSubmit }: DoctorPersonalDataStepProps) {
  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useForm<DoctorPersonalFormData>({
    resolver: zodResolver(doctorPersonalDataSchema),
  });

  function handleNextStep(data: DoctorPersonalFormData) {
    onSubmit(data);
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
            onSubmitEditing: () => crmRef.current?.focus(),
            returnKeyType: 'next',
            placeholder: 'Nome Completo',
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
              onSubmitEditing: () => crmUf.current?.focus(),
              returnKeyType: 'next',
              placeholder: 'CRM',
            }}
            error={errors.crm?.message}
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
            nextRef={dddRef}
          >
            <SelectItem label="AC" value="AC" />
            <SelectItem label="AL" value="AL" />
            <SelectItem label="AP" value="AP" />
            <SelectItem label="AM" value="AM" />
            <SelectItem label="BA" value="BA" />
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
              placeholder: 'DDD',
              onSubmitEditing: () => phoneRef.current?.focus(),
              returnKeyType: 'next',
              keyboardType: 'numeric',
            }}
            error={errors.ddd?.message}
          />
          <Input
            ref={phoneRef}
            formProps={{
              name: 'phone_number',
              control: control,
            }}
            inputProps={{
              placeholder: 'Telefone',
              onSubmitEditing: () => emailRef.current?.focus(),
              returnKeyType: 'next',
            }}
            error={errors.phone_number?.message}
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
            onSubmitEditing: () => passwordRef.current?.focus(),
            returnKeyType: 'next',
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
            onSubmitEditing: () => passwordConfirmationRef.current?.focus(),
            returnKeyType: 'next',
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
            onSubmitEditing: () => handleSubmit(handleNextStep)(),
            returnKeyType: 'done',
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
