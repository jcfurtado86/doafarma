import React, { useEffect, useRef } from 'react';
import { Alert, StyleSheet, TextInput, View } from 'react-native';
import { Controller } from 'react-hook-form';
import { Button, Input } from '@/components/ui';
import { Select, SelectItem, type SelectHandle } from '@/components/ui/Select';
import { SearchableSelect, SearchableSelectRef } from '@/components/SearchableSelect';
import { InputRow } from '@/components/InputRow';
import { BRAZILIAN_STATES } from '@/constants/BrazilianStates';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import { useCitiesByState } from '@/hooks/useCitiesByState';
import { addressFormSchema, AddressFormData } from '@/utils/validation/addressValidation';
import { AddressServiceError } from '@/services/addressService';
import { getErrorMessage } from '@/types/errors';

const FORM_FIELDS = Object.keys(addressFormSchema.shape) as (keyof AddressFormData)[];

function isFormField(field: string): field is keyof AddressFormData {
  return (FORM_FIELDS as string[]).includes(field);
}

interface AddressFormProps {
  defaultValues?: Partial<AddressFormData>;
  submitLabel: string;
  submittingLabel: string;
  onSubmit: (data: AddressFormData) => Promise<void>;
  onCancel: () => void;
}

export function AddressForm({
  defaultValues,
  submitLabel,
  submittingLabel,
  onSubmit,
  onCancel,
}: AddressFormProps) {
  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
    setError,
    watch,
    setValue,
  } = useFeatureForm<AddressFormData>({
    schema: addressFormSchema,
    defaultValues,
  });

  const selectedUf = watch('uf');
  const { cities, isLoading, error: citiesError } = useCitiesByState(selectedUf || null);

  const isInitialMount = useRef(true);
  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }
    setValue('city', '');
  }, [selectedUf, setValue]);

  async function handleFormSubmit(data: AddressFormData) {
    try {
      await onSubmit(data);
    } catch (error: unknown) {
      if (error instanceof AddressServiceError && error.validationErrors) {
        const unmappedMessages: string[] = [];

        Object.entries(error.validationErrors).forEach(([field, messages], index) => {
          if (isFormField(field)) {
            setError(field, { message: messages[0] }, { shouldFocus: index === 0 });
          } else {
            unmappedMessages.push(...messages);
          }
        });

        if (unmappedMessages.length > 0) {
          Alert.alert('Erro', unmappedMessages.join('\n'));
        }
        return;
      }

      Alert.alert('Erro', getErrorMessage(error, 'Ocorreu um erro inesperado. Tente novamente.'));
    }
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
      <View style={styles.inputContainer}>
        <Controller
          control={control}
          name="label"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              returnKeyType="next"
              placeholder="Nome do consultório ou clinica"
              onSubmitEditing={() => cepRef.current?.focus()}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.label?.message}
            />
          )}
        />
        <Controller
          control={control}
          name="cep"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={cepRef}
              returnKeyType="next"
              placeholder="CEP"
              keyboardType="numeric"
              maxLength={8}
              onSubmitEditing={() => ufRef.current?.focus()}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.cep?.message}
            />
          )}
        />
        <InputRow>
          <Controller
            control={control}
            name="uf"
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
                error={errors.uf?.message}
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
              name: 'city',
              control: control,
            }}
            placeholder="Cidade"
            cities={cities}
            isLoading={isLoading}
            loadError={citiesError}
            error={errors.city?.message}
            containerStyle={{ flex: 1 }}
            nextRef={neighborhoodRef}
          />
        </InputRow>
        <Controller
          control={control}
          name="neighborhood"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={neighborhoodRef}
              returnKeyType="next"
              placeholder="Bairro"
              onSubmitEditing={() => streetRef.current?.focus()}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.neighborhood?.message}
            />
          )}
        />
        <InputRow>
          <Controller
            control={control}
            name="street"
            render={({ field: { onChange, onBlur, value } }) => (
              <Input
                ref={streetRef}
                returnKeyType="next"
                placeholder="Rua ou Avenida"
                onSubmitEditing={() => numberRef.current?.focus()}
                value={value}
                onChangeText={onChange}
                onBlur={onBlur}
                error={errors.street?.message}
                containerStyle={{ flex: 3 }}
              />
            )}
          />
          <Controller
            control={control}
            name="number"
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
                error={errors.number?.message}
                containerStyle={{ flex: 1 }}
              />
            )}
          />
        </InputRow>
        <Controller
          control={control}
          name="complement"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={complementRef}
              returnKeyType="done"
              placeholder="Complemento"
              onSubmitEditing={() => handleSubmit(handleFormSubmit)()}
              value={value ?? ''}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.complement?.message}
            />
          )}
        />
      </View>

      <View style={styles.buttonContainer}>
        <Button
          label={isSubmitting ? submittingLabel : submitLabel}
          onPress={() => handleSubmit(handleFormSubmit)()}
          disabled={isSubmitting}
        />
        <Button variant="secondary" label="Cancelar" onPress={onCancel} />
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  inputContainer: {
    justifyContent: 'space-between',
    width: '100%',
  },
  buttonContainer: {
    marginTop: 30,
    marginBottom: 20,
    gap: 16,
  },
});
