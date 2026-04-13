import React, { useRef, useState, useEffect } from 'react';
import { colors } from '@/theme/tokens';
import { View, StyleSheet, Alert, TextInput } from 'react-native';
import { toast } from '@/utils/toast';
import { getErrorMessage } from '@/types/errors';
import { useRouter } from 'expo-router';
import { useMedicationOfferingStore } from '@/stores/medicationOfferingStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Input } from '@/components/ui/Input';
import { Select, SelectItem } from '@/components/Select';
import PrimaryButton from '@/components/PrimaryButton';
import SecondaryButton from '@/components/SecondaryButton';
import { Controller } from 'react-hook-form';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import api from '@/services/api';
import { Drug } from '@/types/medicationOffering';
import { InputRow } from '@/components/InputRow';
import {
  createMedicationOfferingSchema,
  CreateMedicationOfferingFormData,
} from '@/utils/validation/medicationOfferingValidation';
import { formatDateInput, convertDateToAPI } from '@/utils/validation/dateHelpers';

export default function CreateMedicationOfferingScreen() {
  const router = useRouter();
  const { createOffering } = useMedicationOfferingStore();
  const [drugs, setDrugs] = useState<Drug[]>([]);
  const [loadingDrugs, setLoadingDrugs] = useState(true);

  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useFeatureForm<CreateMedicationOfferingFormData>({
    schema: createMedicationOfferingSchema,
  });

  const drugRef = useRef<any>(null);
  const lotNumberRef = useRef<TextInput>(null);
  const expiresAtRef = useRef<TextInput>(null);
  const quantityRef = useRef<TextInput>(null);

  useEffect(() => {
    fetchDrugs();
  }, []);

  const fetchDrugs = async () => {
    try {
      const response = await api.get('/v1/drugs');
      setDrugs(response.data.data);
    } catch {
      Alert.alert('Erro', 'Erro ao carregar lista de medicamentos');
    } finally {
      setLoadingDrugs(false);
    }
  };

  const handleCreateOffering = async (data: CreateMedicationOfferingFormData) => {
    try {
      await createOffering({
        drug_id: parseInt(data.drug_id, 10),
        lot_number: data.lot_number,
        expires_at: convertDateToAPI(data.expires_at),
        quantity: parseInt(data.quantity, 10),
      });

      toast.success('Oferta de medicamento criada com sucesso!');
      router.back();
    } catch (error: unknown) {
      Alert.alert(
        'Erro ao criar oferta',
        getErrorMessage(error, 'Ocorreu um erro inesperado. Tente novamente.'),
        [{ text: 'OK' }]
      );
    }
  };

  const handleGoBack = () => {
    router.back();
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Title>Nova Oferta</Title>
        <Caption>Adicione uma nova oferta de medicamento</Caption>
      </View>

      <View style={styles.form}>
        <InputRow>
          <Select
            ref={drugRef}
            formProps={{
              name: 'drug_id',
              control: control,
            }}
            selectProps={{
              placeholder: loadingDrugs ? 'Carregando medicamentos...' : 'Selecione o medicamento',
              enabled: !loadingDrugs,
            }}
            nextRef={lotNumberRef}
            error={errors.drug_id?.message}
          >
            {drugs.map((drug) => (
              <SelectItem key={drug.id} label={drug.product_name} value={drug.id.toString()} />
            ))}
          </Select>
        </InputRow>

        <Controller
          control={control}
          name="lot_number"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={lotNumberRef}
              placeholder="Número do lote"
              autoCapitalize="characters"
              returnKeyType="next"
              onSubmitEditing={() => expiresAtRef.current?.focus()}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.lot_number?.message}
            />
          )}
        />

        <Controller
          control={control}
          name="expires_at"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={expiresAtRef}
              placeholder="DD/MM/AAAA"
              keyboardType="numeric"
              maxLength={10}
              returnKeyType="next"
              onSubmitEditing={() => quantityRef.current?.focus()}
              value={value}
              onChangeText={(text) => onChange(formatDateInput(text))}
              onBlur={onBlur}
              error={errors.expires_at?.message}
            />
          )}
        />

        <Controller
          control={control}
          name="quantity"
          render={({ field: { onChange, onBlur, value } }) => (
            <Input
              ref={quantityRef}
              placeholder="Quantidade"
              keyboardType="numeric"
              returnKeyType="done"
              onSubmitEditing={() => handleSubmit(handleCreateOffering)()}
              value={value}
              onChangeText={onChange}
              onBlur={onBlur}
              error={errors.quantity?.message}
            />
          )}
        />

        <View style={styles.buttonContainer}>
          <PrimaryButton
            label={isSubmitting ? 'Criando...' : 'Criar Oferta'}
            onPress={() => handleSubmit(handleCreateOffering)()}
            disabled={isSubmitting}
          />

          <SecondaryButton label="Cancelar" onPress={handleGoBack} />
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    backgroundColor: colors.surface,
  },
  header: {
    marginTop: 60,
    marginBottom: 40,
  },
  form: {
    flex: 1,
  },
  buttonContainer: {
    marginTop: 30,
    gap: 16,
  },
});
