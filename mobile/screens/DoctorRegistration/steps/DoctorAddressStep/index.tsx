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
import { z } from 'zod';
import { useFeatureForm } from '@/hooks/useFeatureForm';

interface DoctorAddressStepProps {
  onSubmit: (
    data: Partial<DoctorRegistrationFormData>,
    setError: UseFormSetError<any>
  ) => Promise<void>;
}

const doctorAddressSchema = z.object({
  addresses: z.array(
    z.object({
      location_name: z
        .string({ required_error: 'Nome do consultório é obrigatório' })
        .max(255, 'Nome do consultório não pode exceder 255 caracteres'),
      cep: z
        .string({ required_error: 'CEP é obrigatório' })
        .length(8, 'CRM deve ter exatamente 8 dígitos'),
      uf: z
        .string({ required_error: 'UF é obrigatório' })
        .max(255, 'UF não pode exceder 255 caracteres'),
      city: z
        .string({ required_error: 'Cidade é obrigatória' })
        .max(255, 'Cidade não pode exceder 255 caracteres'),
      neighborhood: z
        .string({ required_error: 'Bairro é obrigatório' })
        .max(255, 'Bairro não pode exceder 255 caracteres'),
      full_address: z
        .string({ required_error: 'Rua ou Avenida é obrigatória' })
        .max(255, 'Rua ou Avenida não pode exceder 255 caracteres'),
      number: z
        .string({ required_error: 'Número é obrigatório' })
        .max(255, 'Número não pode exceder 255 caracteres'),
      complement: z
        .string()
        .optional()
        .transform((val) => (val === '' ? undefined : val))
        .pipe(z.string().max(255, 'Complemento não pode exceder 255 caracteres').optional()),
    })
  ),
});

type DoctorAddressFormData = z.infer<typeof doctorAddressSchema>;

export function DoctorAddressStep({ onSubmit }: DoctorAddressStepProps) {
  const {
    control,
    handleSubmit,
    formState: { errors },
    setError,
  } = useFeatureForm<DoctorAddressFormData>({
    schema: doctorAddressSchema,
  });

  async function handleFinishRegistration(data: DoctorAddressFormData) {
    await onSubmit(data, setError);
  }

  const cepRef = useRef<TextInput>(null);
  const ufRef = useRef<Picker<string | number>>(null);
  const cityRef = useRef<Picker<string | number>>(null);
  const neighborhoodRef = useRef<TextInput>(null);
  const streetRef = useRef<TextInput>(null);
  const numberRef = useRef<TextInput>(null);
  const complementRef = useRef<TextInput>(null);

  return (
    <>
      <View style={styles.textContainer}>
        <Title>Onde podemos te encontrar?</Title>
        <Caption>Você pode adicionar o endereço do seu consultório ou clínica!</Caption>
      </View>

      <View style={styles.inputContainer}>
        <Input
          formProps={{
            name: 'addresses[0].location_name',
            control: control,
          }}
          inputProps={{
            returnKeyType: 'next',
            placeholder: 'Nome do consultório ou clinica',
            onSubmitEditing: () => cepRef.current?.focus(),
          }}
          error={errors.addresses?.[0]?.location_name?.message}
        />
        <Input
          ref={cepRef}
          formProps={{
            name: 'addresses[0].cep',
            control: control,
          }}
          inputProps={{
            returnKeyType: 'next',
            placeholder: 'CEP',
            keyboardType: 'numeric',
            onSubmitEditing: () => ufRef.current?.focus(),
          }}
          error={errors.addresses?.[0]?.cep?.message}
        />
        <InputRow>
          <Select
            ref={ufRef}
            formProps={{
              name: 'addresses[0].uf',
              control: control,
            }}
            selectProps={{
              placeholder: 'Estado',
            }}
            error={errors.addresses?.[0]?.uf?.message}
            containerStyle={{ flex: 1 }}
            nextRef={cityRef}
          >
            <SelectItem label="AC" value="AC" />
            <SelectItem label="AL" value="AL" />
            <SelectItem label="AP" value="AP" />
            <SelectItem label="AM" value="AM" />
            <SelectItem label="BA" value="BA" />
          </Select>
          <Select
            ref={cityRef}
            formProps={{
              name: 'addresses[0].city',
              control: control,
            }}
            selectProps={{
              placeholder: 'Cidade',
            }}
            error={errors.addresses?.[0]?.city?.message}
            containerStyle={{ flex: 1 }}
            nextRef={neighborhoodRef}
          >
            <SelectItem label="Rio Branco" value="Rio Branco" />
            <SelectItem label="Maceió" value="Maceió" />
            <SelectItem label="Macapá" value="Macapá" />
          </Select>
        </InputRow>
        <Input
          ref={neighborhoodRef}
          formProps={{
            name: 'addresses[0].neighborhood',
            control: control,
          }}
          inputProps={{
            returnKeyType: 'next',
            placeholder: 'Bairro',
            onSubmitEditing: () => streetRef.current?.focus(),
          }}
          error={errors.addresses?.[0]?.neighborhood?.message}
        />
        <InputRow>
          <Input
            ref={streetRef}
            formProps={{
              name: 'addresses[0].full_address',
              control: control,
            }}
            inputProps={{
              returnKeyType: 'next',
              placeholder: 'Rua ou Avenida',
              onSubmitEditing: () => numberRef.current?.focus(),
            }}
            error={errors.addresses?.[0]?.full_address?.message}
            containerStyle={{ flex: 3 }}
          />
          <Input
            ref={numberRef}
            formProps={{
              name: 'addresses[0].number',
              control: control,
            }}
            inputProps={{
              returnKeyType: 'next',
              keyboardType: 'numeric',
              placeholder: 'N°000',
              onSubmitEditing: () => complementRef.current?.focus(),
            }}
            error={errors.addresses?.[0]?.number?.message}
            containerStyle={{ flex: 1 }}
          />
        </InputRow>
        <Input
          ref={complementRef}
          formProps={{
            name: 'addresses[0].complement',
            control: control,
          }}
          inputProps={{
            returnKeyType: 'done',
            placeholder: 'Complemento',
            onSubmitEditing: () => handleSubmit(handleFinishRegistration)(),
          }}
          error={errors.addresses?.[0]?.complement?.message}
        />
      </View>
      <View style={styles.buttonContainer}>
        <PrimaryButton onPress={() => handleSubmit(handleFinishRegistration)()} label="Próximo" />
      </View>
    </>
  );
}
