import React from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { useRouter } from 'expo-router';
import { colors } from '@/theme/tokens';
import { toast } from '@/utils/toast';
import { useAddressStore } from '@/stores/addressStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { AddressForm } from '@/components/AddressForm';
import { AddressFormData } from '@/utils/validation/addressValidation';

export default function CreateAddressScreen() {
  const router = useRouter();
  const { createAddress } = useAddressStore();

  const handleCreateAddress = async (data: AddressFormData) => {
    await createAddress(data);
    toast.success('Endereço criado com sucesso!');
    router.back();
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
        <View style={styles.header}>
          <Title>Novo Endereço</Title>
          <Caption>Adicione o endereço do seu consultório ou clínica</Caption>
        </View>

        <AddressForm
          submitLabel="Criar Endereço"
          submittingLabel="Criando..."
          onSubmit={handleCreateAddress}
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
  header: {
    marginTop: 20,
    marginBottom: 30,
  },
});
