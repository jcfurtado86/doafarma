import React, { useEffect, useRef } from 'react';
import { View, TextInput } from 'react-native';
import { Button, Input } from '@/components/ui';
import { styles } from './styles';
import { Controller, UseFormSetError } from 'react-hook-form';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { InputRow } from '@/components/InputRow';
import { Select, SelectItem, type SelectHandle } from '@/components/ui/Select';
import { SearchableSelect, SearchableSelectRef } from '@/components/SearchableSelect';
import { DoctorRegistrationFormData } from '@/stores/doctorRegistrationFormStore';
import { BRAZILIAN_STATES } from '@/constants/BrazilianStates';
import { z } from 'zod';
import { addressFormSchema } from '@/utils/validation/addressValidation';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import { useCitiesByState } from '@/hooks/useCitiesByState';

interface DoctorAddressStepProps {
  onSubmit: (
    data: Partial<DoctorRegistrationFormData>,
    setError: UseFormSetError<any>
  ) => Promise<void>;
}

const doctorAddressSchema = z.object({
  addresses: z.array(addressFormSchema),
});

type DoctorAddressFormData = z.infer<typeof doctorAddressSchema>;

export function DoctorAddressStep({ onSubmit }: DoctorAddressStepProps) {
  const {
    control,
    handleSubmit,
    formState: { errors },
    setError,
    watch,
    setValue,
  } = useFeatureForm<DoctorAddressFormData>({
    schema: doctorAddressSchema,
  });

  const selectedUf = watch('addresses.0.uf');
  const { cities, isLoading, error: citiesError } = useCitiesByState(selectedUf || null);

  const isInitialMount = useRef(true);
  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }
    setValue('addresses.0.city', '');
  }, [selectedUf, setValue]);

  async function handleFinishRegistration(data: DoctorAddressFormData) {
    await onSubmit(data, setError);
  }

  const cepRef = useRef<TextInput>(null);
  const ufRef = useRef<SelectHandle>(null);
  const cityRef = useRef<SearchableSelectRef | null>(null);
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
        <Controller
          control={control}
          name="addresses.0.label"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              returnKeyType="next"
              placeholder="Nome do consultório ou clinica"
              onSubmitEditing={() => cepRef.current?.focus()}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.addresses?.[0]?.label?.message}
            />
          )}
        />
        <Controller
          control={control}
          name="addresses.0.cep"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={cepRef}
              returnKeyType="next"
              placeholder="CEP"
              keyboardType="numeric"
              onSubmitEditing={() => ufRef.current?.focus()}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.addresses?.[0]?.cep?.message}
            />
          )}
        />
        <InputRow>
          <Controller
            control={control}
            name="addresses.0.uf"
            render={({ field: { value, onChange, onBlur } }) => (
              <Select
                ref={ufRef}
                value={value}
                onValueChange={(next) => {
                  onChange(next);
                  if (next) cityRef.current?.focus();
                }}
                onBlur={onBlur}
                placeholder="Estado"
                error={errors.addresses?.[0]?.uf?.message}
                containerStyle={{ flex: 1 }}
              >
                {BRAZILIAN_STATES.map((state) => (
                  <SelectItem key={state.value} label={state.label} value={state.value} />
                ))}
              </Select>
            )}
          />
          <SearchableSelect
            ref={cityRef}
            formProps={{
              name: 'addresses.0.city',
              control: control,
            }}
            placeholder="Cidade"
            cities={cities}
            isLoading={isLoading}
            loadError={citiesError}
            error={errors.addresses?.[0]?.city?.message}
            containerStyle={{ flex: 1 }}
            nextRef={neighborhoodRef}
          />
        </InputRow>
        <Controller
          control={control}
          name="addresses.0.neighborhood"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={neighborhoodRef}
              returnKeyType="next"
              placeholder="Bairro"
              onSubmitEditing={() => streetRef.current?.focus()}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.addresses?.[0]?.neighborhood?.message}
            />
          )}
        />
        <InputRow>
          <Controller
            control={control}
            name="addresses.0.street"
            render={({ field: { onChange, onBlur, value } }) => (
              <Input
                ref={streetRef}
                returnKeyType="next"
                placeholder="Rua ou Avenida"
                onSubmitEditing={() => numberRef.current?.focus()}
                value={value}
                onChangeText={onChange}
                onBlur={onBlur}
                error={errors.addresses?.[0]?.street?.message}
                containerStyle={{ flex: 3 }}
              />
            )}
          />
          <Controller
            control={control}
            name="addresses.0.number"
            render={({ field: { onChange, onBlur, value } }) => (
              <Input
                ref={numberRef}
                returnKeyType="next"
                keyboardType="numeric"
                placeholder="N°000"
                onSubmitEditing={() => complementRef.current?.focus()}
                value={value}
                onChangeText={onChange}
                onBlur={onBlur}
                error={errors.addresses?.[0]?.number?.message}
                containerStyle={{ flex: 1 }}
              />
            )}
          />
        </InputRow>
        <Controller
          control={control}
          name="addresses.0.complement"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={complementRef}
              returnKeyType="done"
              placeholder="Complemento"
              onSubmitEditing={() => handleSubmit(handleFinishRegistration)()}
              value={value ?? ''}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.addresses?.[0]?.complement?.message}
            />
          )}
        />
      </View>
      <View style={styles.buttonContainer}>
        <Button onPress={() => handleSubmit(handleFinishRegistration)()} label="Próximo" />
      </View>
    </>
  );
}
