import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  TextInput,
  FlatList,
  ActivityIndicator,
  Pressable,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  Keyboard,
} from 'react-native';
import { router, Href } from 'expo-router';
import { colors } from '@/theme/tokens';
import { Pagination } from '@/constants/Pagination';
import { SearchResultCard } from '@/components/SearchResultCard';
import { ListFooterLoader } from '@/components/ListFooterLoader';
import { medicationOfferingService } from '@/services/medicationOfferingService';
import { MedicationOfferingSearchResult } from '@/types/medicationOffering';
import { usePagination } from '@/hooks/usePagination';

type SearchState = 'loading' | 'results' | 'empty' | 'error';

export default function SearchScreen() {
  const [searchQuery, setSearchQuery] = useState('');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const fetchOfferingsFn = useCallback(
    async (page: number) => {
      return medicationOfferingService.searchPaginated(searchQuery, page);
    },
    [searchQuery]
  );

  const {
    items: results,
    isLoading,
    isLoadingMore,
    error,
    loadMore,
    refresh,
  } = usePagination<MedicationOfferingSearchResult>({
    fetchFn: fetchOfferingsFn,
    onError: (err) => setErrorMessage(err),
    refreshKey: searchQuery,
  });

  const searchState: SearchState = isLoading
    ? 'loading'
    : error
      ? 'error'
      : results.length === 0
        ? 'empty'
        : 'results';

  const handleSearch = useCallback(() => {
    Keyboard.dismiss();
    refresh();
  }, [refresh]);

  const handleClearSearch = useCallback(() => {
    setSearchQuery('');
  }, []);

  const handleCardPress = useCallback((offering: MedicationOfferingSearchResult) => {
    router.push({
      pathname: '/(auth)/receptor/offering/[id]' as Href,
      params: {
        id: offering.id.toString(),
        offering: JSON.stringify(offering),
      },
    } as any);
  }, []);

  const handleRetry = useCallback(() => {
    refresh();
  }, [refresh]);

  const renderEmptyState = () => {
    switch (searchState) {
      case 'loading':
        return (
          <View style={styles.stateContainer}>
            <ActivityIndicator size="large" color={colors.primary} />
            <Text style={styles.stateSubtitle}>Carregando medicamentos...</Text>
          </View>
        );
      case 'empty':
        return (
          <View style={styles.stateContainer}>
            <Text style={styles.stateIcon}>🔍</Text>
            <Text style={styles.stateTitle}>
              {searchQuery.trim()
                ? 'Nenhum medicamento encontrado'
                : 'Nenhum medicamento disponível'}
            </Text>
            <Text style={styles.stateSubtitle}>
              {searchQuery.trim()
                ? 'Tente buscar por outro nome ou princípio ativo'
                : 'Não há medicamentos disponíveis para doação no momento'}
            </Text>
          </View>
        );
      case 'error':
        return (
          <View style={styles.stateContainer}>
            <Text style={styles.stateIcon}>⚠️</Text>
            <Text style={styles.stateTitle}>Erro ao carregar medicamentos</Text>
            <Text style={styles.stateSubtitle}>
              {errorMessage || 'Verifique sua conexão e tente novamente'}
            </Text>
            <Pressable
              style={styles.retryButton}
              onPress={handleRetry}
              accessibilityLabel="Tentar carregar novamente"
              accessibilityRole="button"
            >
              <Text style={styles.retryButtonText}>Tentar Novamente</Text>
            </Pressable>
          </View>
        );
      default:
        return null;
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <View style={styles.searchContainer}>
        <View style={styles.inputWrapper}>
          <TextInput
            style={styles.searchInput}
            placeholder="Filtrar por nome ou substância"
            placeholderTextColor={colors.textMuted}
            value={searchQuery}
            onChangeText={setSearchQuery}
            onSubmitEditing={handleSearch}
            returnKeyType="search"
            autoCorrect={false}
            autoCapitalize="none"
            editable={!isLoading}
            accessibilityLabel="Campo de busca de medicamentos"
            accessibilityHint="Digite o nome do medicamento ou substância para filtrar a lista"
          />
          {searchQuery.length > 0 && (
            <Pressable
              style={styles.clearButton}
              onPress={handleClearSearch}
              accessibilityLabel="Limpar busca"
              accessibilityRole="button"
            >
              <Text style={styles.clearButtonText}>✕</Text>
            </Pressable>
          )}
        </View>
        <Pressable
          style={[styles.searchButton, isLoading && styles.searchButtonDisabled]}
          onPress={handleSearch}
          disabled={isLoading}
          accessibilityLabel="Buscar medicamentos"
          accessibilityHint="Toque para buscar medicamentos com o termo digitado"
          accessibilityRole="button"
        >
          <Text style={styles.searchButtonText}>🔍</Text>
        </Pressable>
      </View>

      {searchState === 'results' ? (
        <FlatList
          data={results}
          keyExtractor={(item) => item.id.toString()}
          renderItem={({ item }) => (
            <SearchResultCard offering={item} onPress={() => handleCardPress(item)} />
          )}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode="on-drag"
          onEndReached={loadMore}
          onEndReachedThreshold={Pagination.END_REACHED_THRESHOLD}
          ListFooterComponent={<ListFooterLoader isLoading={isLoadingMore} />}
        />
      ) : (
        renderEmptyState()
      )}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  searchContainer: {
    flexDirection: 'row',
    padding: 16,
    gap: 8,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  inputWrapper: {
    flex: 1,
    position: 'relative',
  },
  searchInput: {
    height: 48,
    backgroundColor: colors.surfaceSecondary,
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingRight: 40,
    fontSize: 16,
    color: colors.textPrimary,
  },
  clearButton: {
    position: 'absolute',
    right: 8,
    top: 0,
    bottom: 0,
    width: 32,
    justifyContent: 'center',
    alignItems: 'center',
  },
  clearButtonText: {
    fontSize: 16,
    color: colors.textMuted,
    fontWeight: '600',
  },
  searchButton: {
    width: 48,
    height: 48,
    backgroundColor: colors.primary,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  searchButtonDisabled: {
    backgroundColor: colors.border,
  },
  searchButtonText: {
    fontSize: 20,
  },
  listContent: {
    padding: 16,
  },
  stateContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 32,
  },
  stateIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  stateTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: colors.textPrimary,
    textAlign: 'center',
    marginBottom: 8,
  },
  stateSubtitle: {
    fontSize: 14,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
  },
  retryButton: {
    marginTop: 24,
    backgroundColor: colors.primary,
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 8,
  },
  retryButtonText: {
    color: colors.textInverted,
    fontWeight: '600',
    fontSize: 16,
  },
});
