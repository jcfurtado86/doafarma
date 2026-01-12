import React, { useState, useCallback, useEffect } from 'react';
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
import { Colors } from '@/constants/Colors';
import { SearchResultCard } from '@/components/SearchResultCard';
import { medicationOfferingService } from '@/services/medicationOfferingService';
import { MedicationOfferingSearchResult } from '@/types/medicationOffering';

type SearchState = 'loading' | 'results' | 'empty' | 'error';

export default function SearchScreen() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<MedicationOfferingSearchResult[]>([]);
  const [searchState, setSearchState] = useState<SearchState>('loading');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // Carregar todas as ofertas ao abrir a tela
  const loadAllOfferings = useCallback(async () => {
    setSearchState('loading');
    setErrorMessage(null);

    try {
      // Buscar com string vazia retorna todas as ofertas ativas
      const data = await medicationOfferingService.search('');
      setResults(data);
      setSearchState(data.length > 0 ? 'results' : 'empty');
    } catch (error: any) {
      setErrorMessage(error.message || 'Erro ao carregar medicamentos');
      setSearchState('error');
    }
  }, []);

  // Carregar ofertas quando a tela monta
  useEffect(() => {
    loadAllOfferings();
  }, [loadAllOfferings]);

  const handleSearch = useCallback(async () => {
    const trimmedQuery = query.trim();

    setSearchState('loading');
    setErrorMessage(null);
    Keyboard.dismiss();

    try {
      const data = await medicationOfferingService.search(trimmedQuery);
      setResults(data);
      setSearchState(data.length > 0 ? 'results' : 'empty');
    } catch (error: any) {
      setErrorMessage(error.message || 'Erro ao buscar medicamentos');
      setSearchState('error');
    }
  }, [query]);

  const handleClearSearch = useCallback(() => {
    setQuery('');
    loadAllOfferings();
  }, [loadAllOfferings]);

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
    if (query.trim()) {
      handleSearch();
    } else {
      loadAllOfferings();
    }
  }, [query, handleSearch, loadAllOfferings]);

  const renderEmptyState = () => {
    switch (searchState) {
      case 'loading':
        return (
          <View style={styles.stateContainer}>
            <ActivityIndicator size="large" color={Colors.yellow_green_400} />
            <Text style={styles.stateSubtitle}>Carregando medicamentos...</Text>
          </View>
        );
      case 'empty':
        return (
          <View style={styles.stateContainer}>
            <Text style={styles.stateIcon}>🔍</Text>
            <Text style={styles.stateTitle}>
              {query.trim() ? 'Nenhum medicamento encontrado' : 'Nenhum medicamento disponível'}
            </Text>
            <Text style={styles.stateSubtitle}>
              {query.trim()
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
            placeholderTextColor="#9ca3af"
            value={query}
            onChangeText={setQuery}
            onSubmitEditing={handleSearch}
            returnKeyType="search"
            autoCorrect={false}
            autoCapitalize="none"
            editable={searchState !== 'loading'}
            accessibilityLabel="Campo de busca de medicamentos"
            accessibilityHint="Digite o nome do medicamento ou substância para filtrar a lista"
          />
          {query.length > 0 && (
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
          style={[styles.searchButton, searchState === 'loading' && styles.searchButtonDisabled]}
          onPress={handleSearch}
          disabled={searchState === 'loading'}
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
    backgroundColor: '#f3f4f6',
  },
  searchContainer: {
    flexDirection: 'row',
    padding: 16,
    gap: 8,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  inputWrapper: {
    flex: 1,
    position: 'relative',
  },
  searchInput: {
    height: 48,
    backgroundColor: '#f7f7f7',
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingRight: 40,
    fontSize: 16,
    color: '#1f2937',
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
    color: '#9ca3af',
    fontWeight: '600',
  },
  searchButton: {
    width: 48,
    height: 48,
    backgroundColor: Colors.yellow_green_400,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  searchButtonDisabled: {
    backgroundColor: '#d1d5db',
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
    color: '#1f2937',
    textAlign: 'center',
    marginBottom: 8,
  },
  stateSubtitle: {
    fontSize: 14,
    color: '#6b7280',
    textAlign: 'center',
    lineHeight: 20,
  },
  retryButton: {
    marginTop: 24,
    backgroundColor: Colors.yellow_green_400,
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 8,
  },
  retryButtonText: {
    color: '#ffffff',
    fontWeight: '600',
    fontSize: 16,
  },
});
