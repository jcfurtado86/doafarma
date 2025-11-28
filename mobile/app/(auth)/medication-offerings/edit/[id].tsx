import React, { useRef, useState, useEffect, useCallback } from 'react';
import { View, StyleSheet, Alert, TextInput, ActivityIndicator } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useMedicationOfferingStore } from '@/stores/medicationOfferingStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Input } from '@/components/Input';
import { Select, SelectItem } from '@/components/Select';
import PrimaryButton from '@/components/PrimaryButton';
import SecondaryButton from '@/components/SecondaryButton';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import api from '@/services/api';
import { Drug } from '@/types/medicationOffering';
import { InputRow } from '@/components/InputRow';
import { medicationOfferingService } from '@/services/medicationOfferingService';
import {
  updateMedicationOfferingSchema,
  UpdateMedicationOfferingFormData,
} from '@/utils/validation/medicationOfferingValidation';
import {
  formatDateInput,
  convertDateToAPI,
  convertDateFromAPI,
} from '@/utils/validation/dateHelpers';

export default function EditMedicationOfferingScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const { updateOffering } = useMedicationOfferingStore();

  const [drugs, setDrugs] = useState<Drug[]>([]);
  const [loadingDrugs, setLoadingDrugs] = useState(true);
  const [loadingOffering, setLoadingOffering] = useState(true);

  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
    setValue,
  } = useFeatureForm<UpdateMedicationOfferingFormData>({
    schema: updateMedicationOfferingSchema,
  });

  const drugRef = useRef<any>(null);
  const lotNumberRef = useRef<TextInput>(null);
  const expiresAtRef = useRef<TextInput>(null);
  const quantityRef = useRef<TextInput>(null);

  const fetchDrugs = useCallback(async () => {
    try {
      const response = await api.get('/v1/drugs');
      setDrugs(response.data.data);
    } catch (error) {
      Alert.alert('Erro', 'Erro ao carregar lista de medicamentos');
    } finally {
      setLoadingDrugs(false);
    }
  }, []);

  const fetchOffering = useCallback(async () => {
    try {
      const offeringData = await medicationOfferingService.show(parseInt(id, 10));

      reset({
        drug_id: offeringData.drug?.id?.toString() || '',
        lot_number: offeringData.lot_number,
        expires_at: convertDateFromAPI(offeringData.expires_at),
        quantity: offeringData.quantity.toString(),
      });
    } catch (error: any) {
      Alert.alert('Erro', error.message || 'Erro ao carregar dados da oferta', [
        { text: 'OK', onPress: () => router.back() },
      ]);
    } finally {
      setLoadingOffering(false);
    }
  }, [id, reset, router]);

  useEffect(() => {
    fetchDrugs();
    fetchOffering();
  }, [fetchDrugs, fetchOffering]);

  const handleUpdateOffering = async (data: UpdateMedicationOfferingFormData) => {
    try {
      await updateOffering(parseInt(id, 10), {
        lot_number: data.lot_number,
        expires_at: convertDateToAPI(data.expires_at),
        quantity: parseInt(data.quantity, 10),
      });

      Alert.alert('Sucesso', 'Oferta de medicamento atualizada com sucesso!', [
        { text: 'OK', onPress: () => router.back() },
      ]);
    } catch (error: any) {
      Alert.alert(
        'Erro ao atualizar oferta',
        error.message || 'Ocorreu um erro inesperado. Tente novamente.',
        [{ text: 'OK' }]
      );
    }
  };

  const handleGoBack = () => {
    router.back();
  };

  if (loadingOffering) {
    return (
      <View style={[styles.container, styles.centered]}>
        <ActivityIndicator size="large" color="#007AFF" />
        <Caption style={{ marginTop: 16 }}>Carregando...</Caption>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Title>Editar Oferta</Title>
        <Caption>Atualize os dados da oferta de medicamento</Caption>
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
              enabled: false,
            }}
            nextRef={lotNumberRef}
            error={errors.drug_id?.message}
          >
            {drugs.map((drug) => (
              <SelectItem key={drug.id} label={drug.product_name} value={drug.id.toString()} />
            ))}
          </Select>
        </InputRow>

        <Input
          ref={lotNumberRef}
          formProps={{
            name: 'lot_number',
            control: control,
          }}
          inputProps={{
            placeholder: 'Número do lote',
            autoCapitalize: 'characters',
            returnKeyType: 'next',
            onSubmitEditing: () => expiresAtRef.current?.focus(),
          }}
          error={errors.lot_number?.message}
        />

        <Input
          ref={expiresAtRef}
          formProps={{
            name: 'expires_at',
            control: control,
          }}
          inputProps={{
            placeholder: 'DD/MM/AAAA',
            keyboardType: 'numeric',
            maxLength: 10,
            returnKeyType: 'next',
            onSubmitEditing: () => quantityRef.current?.focus(),
            onChangeText: (text) => {
              const formatted = formatDateInput(text);
              setValue('expires_at', formatted);
            },
          }}
          error={errors.expires_at?.message}
        />

        <Input
          ref={quantityRef}
          formProps={{
            name: 'quantity',
            control: control,
          }}
          inputProps={{
            placeholder: 'Quantidade',
            keyboardType: 'numeric',
            returnKeyType: 'done',
            onSubmitEditing: () => handleSubmit(handleUpdateOffering)(),
          }}
          error={errors.quantity?.message}
        />

        <View style={styles.buttonContainer}>
          <PrimaryButton
            label={isSubmitting ? 'Salvando...' : 'Salvar Alterações'}
            onPress={() => handleSubmit(handleUpdateOffering)()}
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
    backgroundColor: '#ffffff',
  },
  centered: {
    justifyContent: 'center',
    alignItems: 'center',
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
