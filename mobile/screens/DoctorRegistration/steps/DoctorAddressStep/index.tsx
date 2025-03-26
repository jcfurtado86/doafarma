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
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';

interface DoctorAddressStepProps {
  onSubmit: (data: Partial<DoctorRegistrationFormData>) => Promise<void>;
}

const doctorAddressSchema = z.object({
  addresses: z.array(
    z.object({
      location_name: z.string().min(1, 'Nome do consultório é obrigatório'),
      cep: z.string().min(1, 'CEP é obrigatório'),
      uf: z.string().min(1, 'UF é obrigatório'),
      city: z.string().min(1, 'Cidade é obrigatória'),
      neighborhood: z.string().min(1, 'Bairro é obrigatório'),
      full_address: z.string().min(1, 'Rua ou Avenida é obrigatória'),
      number: z.string().min(1, 'Número é obrigatório'),
      complement: z.string().optional(),
    })
  ),
});

type DoctorAddressFormData = z.infer<typeof doctorAddressSchema>;

export function DoctorAddressStep({ onSubmit }: DoctorAddressStepProps) {
  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useForm<DoctorAddressFormData>({
    resolver: zodResolver(doctorAddressSchema),
  });

  async function handleFinishRegistration(data: DoctorAddressFormData) {
    await onSubmit(data);
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
            onSubmitEditing: () => cepRef.current?.focus(),
            returnKeyType: 'next',
            placeholder: 'Nome do consultório ou clinica',
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
            onSubmitEditing: () => ufRef.current?.focus(),
            returnKeyType: 'next',
            placeholder: 'CEP',
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
            onSubmitEditing: () => streetRef.current?.focus(),
            returnKeyType: 'next',
            placeholder: 'Bairro',
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
              onSubmitEditing: () => numberRef.current?.focus(),
              returnKeyType: 'next',
              placeholder: 'Rua ou Avenida',
            }}
            error={errors.addresses?.[0]?.full_address?.message}
          />
          <Input
            ref={numberRef}
            formProps={{
              name: 'addresses[0].number',
              control: control,
            }}
            inputProps={{
              onSubmitEditing: () => complementRef.current?.focus(),
              returnKeyType: 'next',
              keyboardType: 'numeric',
              placeholder: 'N°000',
            }}
            error={errors.addresses?.[0]?.number?.message}
          />
        </InputRow>
        <Input
          ref={complementRef}
          formProps={{
            name: 'addresses[0].complement',
            control: control,
          }}
          inputProps={{
            onSubmitEditing: () => handleSubmit(handleFinishRegistration)(),
            returnKeyType: 'done',
            placeholder: 'Complemento',
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
