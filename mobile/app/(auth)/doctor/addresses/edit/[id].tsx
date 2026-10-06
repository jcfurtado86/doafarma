import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { colors } from '@/theme/tokens';
import { getErrorMessage } from '@/types/errors';
import { toast } from '@/utils/toast';
import { useAddressStore } from '@/stores/addressStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { AddressForm } from '@/components/AddressForm';
import { AddressFormData } from '@/utils/validation/addressValidation';

export default function EditAddressScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const addressId = parseInt(id, 10);
  const address = useAddressStore((state) => state.addresses.find((item) => item.id === addressId));
  const ensureAddressesLoaded = useAddressStore((state) => state.ensureAddressesLoaded);
  const updateAddress = useAddressStore((state) => state.updateAddress);
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    ensureAddressesLoaded()
      .then(() => setIsReady(true))
      .catch((error: unknown) => {
        // Falha de carga não é "não encontrado": mostra o motivo real e volta
        Alert.alert(
          'Erro',
          getErrorMessage(error, 'Não foi possível carregar o endereço. Tente novamente.'),
          [{ text: 'OK', onPress: () => router.back() }]
        );
      });
  }, [ensureAddressesLoaded, router]);

  useEffect(() => {
    if (isReady && !address) {
      Alert.alert('Erro', 'Endereço não encontrado', [
        { text: 'OK', onPress: () => router.back() },
      ]);
    }
  }, [isReady, address, router]);

  const handleUpdateAddress = async (data: AddressFormData) => {
    await updateAddress(addressId, data);
    toast.success('Endereço atualizado com sucesso!');
    router.back();
  };

  if (!isReady || !address) {
    return (
      <View style={[styles.container, styles.centered]}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Caption style={{ marginTop: 16 }}>Carregando...</Caption>
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
        <View style={styles.header}>
          <Title>Editar Endereço</Title>
          <Caption>Atualize os dados do endereço</Caption>
        </View>

        <AddressForm
          defaultValues={{
            label: address.label,
            cep: address.cep,
            uf: address.uf,
            city: address.city,
            neighborhood: address.neighborhood,
            street: address.street,
            number: address.number,
            complement: address.complement ?? '',
          }}
          submitLabel="Salvar Alterações"
          submittingLabel="Salvando..."
          onSubmit={handleUpdateAddress}
          onCancel={() => router.back()}
        />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    backgroundColor: colors.surface,
  },
  centered: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  header: {
    marginTop: 20,
    marginBottom: 30,
  },
});
