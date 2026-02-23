import React, { useEffect, useCallback } from 'react';
import { View, StyleSheet, FlatList, Alert } from 'react-native';
import { toast } from '@/utils/toast';
import { useRouter } from 'expo-router';
import { useMedicationOfferingStore } from '@/stores/medicationOfferingStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import PrimaryButton from '@/components/PrimaryButton';
import { MedicationOfferingCard } from '@/components/MedicationOfferingCard';
import type { MedicationOffering } from '@/types/medicationOffering';

export default function MedicationOfferingsScreen() {
  const router = useRouter();
  const { offerings, isLoading, fetchOfferings, deleteOffering } = useMedicationOfferingStore();

  const fetchOfferingsCallback = useCallback(() => {
    fetchOfferings();
  }, [fetchOfferings]);

  useEffect(() => {
    fetchOfferingsCallback();
  }, [fetchOfferingsCallback]);

  const handleDelete = useCallback(
    async (id: number, drugName: string) => {
      Alert.alert(
        'Confirmar exclusão',
        `Tem certeza que deseja excluir a oferta do medicamento "${drugName}"?`,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: 'Excluir',
            style: 'destructive',
            onPress: async () => {
              try {
                await deleteOffering(id);
                toast.success('Oferta excluída com sucesso!');
              } catch (error: any) {
                Alert.alert(
                  'Erro ao excluir',
                  error.message || 'Não foi possível excluir a oferta. Tente novamente.'
                );
              }
            },
          },
        ]
      );
    },
    [deleteOffering]
  );

  const handleEdit = useCallback(
    (offering: MedicationOffering) => {
      router.push(`/(auth)/medication-offerings/edit/${offering.id}`);
    },
    [router]
  );

  const renderOffering = useCallback(
    ({ item }: { item: MedicationOffering }) => (
      <MedicationOfferingCard
        offering={item}
        onEdit={() => handleEdit(item)}
        onDelete={() => handleDelete(item.id, item.drug?.product_name || 'Medicamento')}
      />
    ),
    [handleEdit, handleDelete]
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Title>Minhas Ofertas</Title>
        <Caption>Gerencie suas ofertas de medicamentos</Caption>
      </View>

      <View style={styles.content}>
        <FlatList
          data={offerings}
          renderItem={renderOffering}
          keyExtractor={(item) => item.id.toString()}
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.list}
          refreshing={isLoading}
          onRefresh={fetchOfferingsCallback}
          ListEmptyComponent={
            !isLoading ? (
              <View style={styles.emptyContainer}>
                <Caption>Nenhuma oferta encontrada</Caption>
                <Caption>Toque no botão abaixo para criar sua primeira oferta</Caption>
              </View>
            ) : null
          }
        />
      </View>

      <View style={styles.footer}>
        <PrimaryButton
          label="Nova Oferta"
          onPress={() => router.push('/(auth)/medication-offerings/create')}
        />
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
    marginBottom: 20,
  },
  content: {
    flex: 1,
  },
  list: {
    paddingBottom: 20,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 40,
  },
  footer: {
    marginBottom: 20,
  },
});
