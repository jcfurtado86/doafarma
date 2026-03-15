import {
  ActivityIndicator,
  FlatList,
  Modal,
  Pressable,
  StyleProp,
  Text,
  TextInput,
  TouchableOpacity,
  View,
  ViewStyle,
} from 'react-native';
import { Controller, FieldValues, UseControllerProps } from 'react-hook-form';
import { forwardRef, useImperativeHandle, useMemo, useState } from 'react';
import { colors } from '@/theme/tokens';
import { styles } from './styles';

export interface SearchableSelectRef {
  focus: () => void;
}

interface SearchableSelectProps<T extends FieldValues = FieldValues> {
  formProps: UseControllerProps<T>;
  placeholder: string;
  cities: string[];
  isLoading: boolean;
  loadError?: string | null;
  containerStyle?: StyleProp<ViewStyle>;
  nextRef?: React.RefObject<{ focus: () => void } | null>;
  error?: string;
}

/**
 * SearchableSelect — Select com busca para listas grandes como cidades.
 *
 * - Unifica o comportamento entre iOS e Android via Modal com FlatList.
 * - Exibe ActivityIndicator enquanto as opções carregam.
 * - Filtra as opções em tempo real conforme o usuário digita.
 * - Expõe `focus()` via ref para navegação sequencial entre campos.
 */
const SearchableSelect = forwardRef<SearchableSelectRef, SearchableSelectProps<any>>(
  (
    { formProps, placeholder, cities, isLoading, loadError, containerStyle, nextRef, error },
    ref
  ) => {
    const [modalVisible, setModalVisible] = useState(false);
    const [searchText, setSearchText] = useState('');
    const hasError = !!error;
    const isDisabled = isLoading || cities.length === 0;

    useImperativeHandle(ref, () => ({
      focus: () => {
        if (!isDisabled) openModal();
      },
    }));

    function openModal() {
      setModalVisible(true);
    }

    function closeModal() {
      setModalVisible(false);
      setSearchText('');
    }

    const filteredCities = useMemo(() => {
      if (!searchText) return cities;
      const lower = searchText.toLowerCase();
      return cities.filter((city) => city.toLowerCase().includes(lower));
    }, [cities, searchText]);

    return (
      <View
        style={[
          styles.container,
          hasError || loadError ? { marginBottom: 8 } : { marginBottom: 16 },
          containerStyle,
        ]}
      >
        <Controller
          {...formProps}
          render={({ field }) => (
            <>
              <TouchableOpacity
                style={[
                  styles.trigger,
                  hasError && styles.triggerError,
                  modalVisible && styles.triggerFocused,
                  isDisabled && styles.triggerDisabled,
                ]}
                onPress={openModal}
                disabled={isDisabled}
                activeOpacity={0.7}
                accessibilityRole="button"
                accessibilityLabel={field.value || placeholder}
                accessibilityState={{ disabled: isDisabled }}
              >
                {isLoading ? (
                  <View style={styles.loadingRow}>
                    <ActivityIndicator size="small" color={colors.primary} />
                    <Text style={styles.loadingText}>Carregando cidades...</Text>
                  </View>
                ) : (
                  <Text style={[styles.triggerText, !field.value && styles.placeholderText]}>
                    {field.value || placeholder}
                  </Text>
                )}
              </TouchableOpacity>

              <Modal
                visible={modalVisible}
                animationType="fade"
                transparent
                onRequestClose={closeModal}
              >
                <Pressable style={styles.modalOverlay} onPress={closeModal}>
                  <View style={styles.modalContent}>
                    <TextInput
                      style={styles.searchInput}
                      placeholder="Buscar cidade..."
                      placeholderTextColor={colors.textPlaceholder}
                      value={searchText}
                      onChangeText={setSearchText}
                      autoFocus
                    />

                    {filteredCities.length === 0 ? (
                      <View style={styles.emptyContainer}>
                        <Text style={styles.emptyText}>Nenhuma cidade encontrada</Text>
                      </View>
                    ) : (
                      <FlatList
                        data={filteredCities}
                        keyExtractor={(item) => item}
                        keyboardShouldPersistTaps="handled"
                        renderItem={({ item }) => {
                          const isSelected = field.value === item;
                          return (
                            <TouchableOpacity
                              style={styles.optionItem}
                              onPress={() => {
                                field.onChange(item);
                                closeModal();
                                if (nextRef) nextRef.current?.focus();
                              }}
                              accessibilityRole="button"
                              accessibilityLabel={`Selecionar cidade: ${item}`}
                            >
                              <Text
                                style={[styles.optionText, isSelected && styles.optionTextSelected]}
                              >
                                {item}
                              </Text>
                            </TouchableOpacity>
                          );
                        }}
                      />
                    )}

                    <TouchableOpacity
                      style={styles.cancelButton}
                      onPress={closeModal}
                      accessibilityRole="button"
                      accessibilityLabel="Cancelar seleção"
                    >
                      <Text style={styles.cancelText}>Cancelar</Text>
                    </TouchableOpacity>
                  </View>
                </Pressable>
              </Modal>
            </>
          )}
        />

        {hasError && <Text style={styles.errorText}>{error}</Text>}
        {!hasError && loadError && <Text style={styles.loadErrorText}>{loadError}</Text>}
      </View>
    );
  }
);

SearchableSelect.displayName = 'SearchableSelect';

export { SearchableSelect };
