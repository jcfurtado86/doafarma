import React, { useRef, useState, useEffect } from 'react';
import { View, StyleSheet, Alert, TextInput } from 'react-native';
import { useRouter } from 'expo-router';
import { useMedicationOfferingStore } from '@/stores/medicationOfferingStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Input } from '@/components/Input';
import { Select, SelectItem } from '@/components/Select';
import PrimaryButton from '@/components/PrimaryButton';
import SecondaryButton from '@/components/SecondaryButton';
import { z } from 'zod';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import api from '@/services/api';
import { Drug } from '@/types/medicationOffering';
import { InputRow } from '@/components/InputRow';

const createOfferingSchema = z.object({
  drug_id: z
    .string({ required_error: 'Medicamento é obrigatório' })
    .min(1, 'Selecione um medicamento'),
  lot_number: z
    .string({ required_error: 'Número do lote é obrigatório' })
    .max(255, 'Número do lote não pode ter mais de 255 caracteres'),
  expires_at: z
    .string({ required_error: 'Data de vencimento é obrigatória' })
    .min(1, 'Data de vencimento é obrigatória'),
  quantity: z
    .string({ required_error: 'Quantidade é obrigatória' })
    .min(1, 'Quantidade deve ser pelo menos 1'),
});

type CreateOfferingFormData = z.infer<typeof createOfferingSchema>;

export default function CreateMedicationOfferingScreen() {
  const router = useRouter();
  const { createOffering } = useMedicationOfferingStore();
  const [drugs, setDrugs] = useState<Drug[]>([]);
  const [loadingDrugs, setLoadingDrugs] = useState(true);

  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
    setValue, // ← ADICIONAR setValue
  } = useFeatureForm<CreateOfferingFormData>({
    schema: createOfferingSchema,
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
    } catch (error) {
      console.error('Erro ao carregar medicamentos:', error);
      Alert.alert('Erro', 'Erro ao carregar lista de medicamentos');
    } finally {
      setLoadingDrugs(false);
    }
  };

  // Função para aplicar máscara DD/MM/YYYY
  const formatDateInput = (text: string) => {
    // Remove tudo que não é número
    const numbers = text.replace(/\D/g, '');

    // Aplica a máscara DD/MM/YYYY
    if (numbers.length <= 2) {
      return numbers;
    } else if (numbers.length <= 4) {
      return `${numbers.slice(0, 2)}/${numbers.slice(2)}`;
    } else {
      return `${numbers.slice(0, 2)}/${numbers.slice(2, 4)}/${numbers.slice(4, 8)}`;
    }
  };

  // Função para converter DD/MM/YYYY para YYYY-MM-DD
  const convertDateToAPI = (dateString: string) => {
    const [day, month, year] = dateString.split('/');
    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
  };

  const handleCreateOffering = async (data: CreateOfferingFormData) => {
    try {
      await createOffering({
        drug_id: parseInt(data.drug_id),
        lot_number: data.lot_number,
        expires_at: convertDateToAPI(data.expires_at),
        quantity: parseInt(data.quantity),
      });

      Alert.alert('Sucesso', 'Oferta de medicamento criada com sucesso!', [
        { text: 'OK', onPress: () => router.back() },
      ]);
    } catch (error: any) {
      console.error('Erro ao criar oferta:', error);
      Alert.alert('Erro', 'Erro ao criar oferta de medicamento');
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

        <Input
          ref={lotNumberRef}
          formProps={{
            name: 'lot_number',
            control: control,
          }}
          inputProps={{
            placeholder: 'Número do lote',
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
              setValue('expires_at', formatted); // ← USAR setValue aqui
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
            onSubmitEditing: () => handleSubmit(handleCreateOffering)(),
          }}
          error={errors.quantity?.message}
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
    backgroundColor: '#ffffff',
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
