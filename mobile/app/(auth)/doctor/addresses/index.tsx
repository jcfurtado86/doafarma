import React, { useCallback, useEffect } from 'react';
import { Alert, FlatList, StyleSheet, View } from 'react-native';
import { useRouter } from 'expo-router';
import { colors } from '@/theme/tokens';
import { getErrorMessage } from '@/types/errors';
import { toast } from '@/utils/toast';
import { useAddressStore } from '@/stores/addressStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Button, EmptyState } from '@/components/ui';
import { AddressCard } from '@/components/AddressCard';
import type { Address } from '@/types/address';

export default function AddressesScreen() {
  const router = useRouter();
  const { addresses, isLoading, fetchAddresses, deleteAddress, setDefaultAddress } =
    useAddressStore();

  const fetchAddressesCallback = useCallback(() => {
    fetchAddresses().catch(() => {
      // erro já fica registrado na store; pull-to-refresh permite tentar de novo
    });
  }, [fetchAddresses]);

  useEffect(() => {
    fetchAddressesCallback();
  }, [fetchAddressesCallback]);

  const handleDelete = useCallback(
    (address: Address) => {
      Alert.alert(
        'Confirmar exclusão',
        `Tem certeza que deseja excluir o endereço "${address.label}"?`,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: 'Excluir',
            style: 'destructive',
            onPress: async () => {
              try {
                await deleteAddress(address.id);
                toast.success('Endereço excluído com sucesso!');
              } catch (error: unknown) {
                Alert.alert(
                  'Erro ao excluir',
                  getErrorMessage(error, 'Não foi possível excluir o endereço. Tente novamente.')
                );
              }
            },
          },
        ]
      );
    },
    [deleteAddress]
  );

  const handleSetDefault = useCallback(
    async (address: Address) => {
      try {
        await setDefaultAddress(address.id);
        toast.success(`"${address.label}" definido como endereço padrão!`);
      } catch (error: unknown) {
        Alert.alert(
          'Erro',
          getErrorMessage(error, 'Não foi possível definir o endereço padrão. Tente novamente.')
        );
      }
    },
    [setDefaultAddress]
  );

  const handleEdit = useCallback(
    (address: Address) => {
      router.push(`/(auth)/doctor/addresses/edit/${address.id}`);
    },
    [router]
  );

  const renderAddress = useCallback(
    ({ item }: { item: Address }) => (
      <AddressCard
        address={item}
        onEdit={() => handleEdit(item)}
        onDelete={() => handleDelete(item)}
        onSetDefault={() => handleSetDefault(item)}
      />
    ),
    [handleEdit, handleDelete, handleSetDefault]
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Title>Meus Endereços</Title>
        <Caption>Gerencie os endereços do seu consultório</Caption>
      </View>

      <View style={styles.content}>
        <FlatList
          data={addresses}
          renderItem={renderAddress}
          keyExtractor={(item) => item.id.toString()}
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.list}
          refreshing={isLoading}
          onRefresh={fetchAddressesCallback}
          ListEmptyComponent={
            !isLoading ? (
              <EmptyState
                icon="location-outline"
                title="Nenhum endereço cadastrado"
                description="Cadastre o endereço do seu consultório ou clínica para receber os agendamentos de doação."
                actionLabel="Adicionar endereço"
                onAction={() => router.push('/(auth)/doctor/addresses/create')}
              />
            ) : null
          }
        />
      </View>

      <View style={styles.footer}>
        <Button
          label="Novo Endereço"
          onPress={() => router.push('/(auth)/doctor/addresses/create')}
        />
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
    marginTop: 20,
    marginBottom: 20,
  },
  content: {
    flex: 1,
  },
  list: {
    paddingBottom: 20,
    flexGrow: 1,
  },
  footer: {
    marginBottom: 20,
  },
});
