import { styles } from './styles';
import { Controller, FieldValues, UseControllerProps } from 'react-hook-form';
import { forwardRef, useImperativeHandle, useState } from 'react';
import { Picker, PickerProps } from '@react-native-picker/picker';
import {
  FlatList,
  Modal,
  Platform,
  Pressable,
  StyleProp,
  Text,
  TextStyle,
  TouchableOpacity,
  View,
  ViewStyle,
} from 'react-native';
import { Colors } from '@/constants/Colors';
import { a11y } from '@/utils/accessibility';

interface SelectProps<T extends FieldValues = FieldValues> {
  formProps: UseControllerProps<T>;
  selectProps: PickerProps;
  children: React.ReactNode;
  containerStyle?: StyleProp<ViewStyle>;
  selectViewStyle?: StyleProp<ViewStyle>;
  selectStyle?: StyleProp<TextStyle>;
  nextRef?: React.RefObject<{ focus: () => void } | null>;
  error?: string;
}

/**
 * Select - Componente multiplataforma para seleção de opções.
 *
 * - No Android, utiliza o Picker nativo e o ref é do tipo Picker.
 * - No iOS, utiliza um Modal customizado e o ref expõe apenas o método `focus()`, para navegação entre campos.
 * - O tipo do ref é polimórfico para garantir integração fluida com formulários e navegação por teclado.
 *
 * Caso novas plataformas sejam adicionadas, adapte a lógica de ref conforme a necessidade.
 */
const Select = forwardRef<Picker<string | number> | { focus: () => void } | null, SelectProps<any>>(
  (
    {
      formProps,
      selectProps,
      children,
      containerStyle,
      selectViewStyle,
      selectStyle,
      nextRef,
      error,
    },
    ref
  ) => {
    const [isFocused, setIsFocused] = useState(false);
    const [modalVisible, setModalVisible] = useState(false);
    const hasError = !!error;

    useImperativeHandle(ref, () => ({
      // No iOS, expõe apenas o método focus para abrir o modal ao navegar com "next"
      focus: () => setModalVisible(true),
    }));

    const options = Array.isArray(children)
      ? (children as React.ReactElement<{ label: string; value: string | number }>[]).map(
          (child) => ({
            label: child.props.label,
            value: child.props.value,
          })
        )
      : [];

    if (Platform.OS === 'android') {
      // ...dentro do bloco Android...
      return (
        <View
          style={[
            styles.container,
            hasError ? { marginBottom: 8 } : { marginBottom: 16 },
            containerStyle,
          ]}
        >
          <Controller
            render={({ field }) => (
              <View
                style={[
                  styles.selectContainer,
                  isFocused && styles.selectContainerFocused,
                  hasError && styles.selectContainerError,
                  selectViewStyle,
                ]}
                {...a11y.input(selectProps.placeholder ?? 'Selecione uma opção')}
              >
                <Picker
                  // No Android, o ref é passado para o Picker para suportar navegação entre campos
                  ref={ref as React.Ref<Picker<string | number>>}
                  style={[selectStyle]}
                  onFocus={() => setIsFocused(true)}
                  onBlur={() => setIsFocused(false)}
                  onValueChange={(itemValue) => {
                    field.onChange(itemValue);

                    if (nextRef && itemValue) {
                      nextRef.current?.focus();
                    }
                  }}
                  selectedValue={field.value}
                  {...selectProps}
                >
                  <Picker.Item
                    label={selectProps.placeholder}
                    value=""
                    color={selectProps.selectedValue ? undefined : '#AFB2BF'}
                    enabled={!isFocused}
                  />
                  {children}
                </Picker>
              </View>
            )}
            {...formProps}
          />
          {hasError && <Text style={styles.errorText}>{error}</Text>}
        </View>
      );
    }

    return (
      // ...dentro do bloco iOS...
      <View
        style={[
          styles.container,
          hasError ? { marginBottom: 8 } : { marginBottom: 16 },
          containerStyle,
        ]}
      >
        <Controller
          {...formProps}
          render={({ field }) => {
            const selectedLabel =
              options.find((opt) => opt.value === field.value)?.label ||
              selectProps.placeholder ||
              '';

            return (
              <>
                <TouchableOpacity
                  style={[
                    styles.selectContainer,
                    selectViewStyle,
                    hasError && styles.selectContainerError,
                    modalVisible && styles.selectContainerFocused,
                  ]}
                  onPress={() => setModalVisible(true)}
                  activeOpacity={0.7}
                >
                  <Text
                    style={[
                      selectStyle,
                      {
                        color: field.value ? '#222' : '#AFB2BF',
                        paddingVertical: 12,
                        paddingLeft: 16,
                      },
                    ]}
                  >
                    {selectedLabel}
                  </Text>
                </TouchableOpacity>
                <Modal
                  visible={modalVisible}
                  animationType="fade"
                  transparent
                  onRequestClose={() => setModalVisible(false)}
                >
                  <Pressable
                    style={{
                      flex: 1,
                      backgroundColor: 'rgba(0,0,0,0.3)',
                      justifyContent: 'center',
                      padding: 24,
                    }}
                    onPress={() => setModalVisible(false)}
                  >
                    <View
                      style={{
                        backgroundColor: '#fff',
                        borderRadius: 12,
                        maxHeight: '70%',
                        paddingVertical: 8,
                      }}
                    >
                      <FlatList
                        data={options}
                        keyExtractor={(item) => String(item.value)}
                        renderItem={({ item }) => {
                          const isSelected = field.value === item.value;
                          return (
                            <TouchableOpacity
                              style={{
                                paddingVertical: 16,
                                paddingHorizontal: 20,
                                borderBottomWidth: 1,
                                borderBottomColor: '#eee',
                              }}
                              onPress={() => {
                                field.onChange(item.value);
                                setModalVisible(false);
                                if (nextRef) nextRef.current?.focus();
                              }}
                              accessibilityRole="button"
                              accessibilityLabel={`Selecionar opção: ${item.label}`}
                            >
                              <Text
                                style={{
                                  fontSize: 16,
                                  color: isSelected ? Colors.yellow_green_500 : '#222',
                                  fontWeight: isSelected ? 'bold' : 'normal',
                                }}
                              >
                                {item.label}
                              </Text>
                            </TouchableOpacity>
                          );
                        }}
                        ListFooterComponent={
                          <TouchableOpacity
                            style={{
                              paddingVertical: 16,
                              alignItems: 'center',
                            }}
                            onPress={() => setModalVisible(false)}
                            accessibilityRole="button"
                            accessibilityLabel="Cancelar seleção"
                          >
                            <Text style={{ color: '#AFB2BF' }}>Cancelar</Text>
                          </TouchableOpacity>
                        }
                      />
                    </View>
                  </Pressable>
                </Modal>
              </>
            );
          }}
        />
        {hasError && <Text style={styles.errorText}>{error}</Text>}
      </View>
    );
  }
);

Select.displayName = 'Select';

export { Select };
