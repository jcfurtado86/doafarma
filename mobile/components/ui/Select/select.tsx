import {
  Children,
  forwardRef,
  isValidElement,
  type ReactElement,
  type ReactNode,
  useImperativeHandle,
  useRef,
  useState,
} from 'react';
import {
  FlatList,
  Modal,
  Platform,
  Pressable,
  type StyleProp,
  Text,
  TouchableOpacity,
  View,
  type ViewStyle,
} from 'react-native';
import { Picker } from '@react-native-picker/picker';
import { colors } from '@/theme/tokens';
import { styles } from './styles';

export type SelectSize = 'sm' | 'md' | 'lg';

export interface SelectHandle {
  focus: () => void;
  blur: () => void;
}

export interface SelectProps {
  value: string;
  onValueChange: (value: string) => void;
  onBlur?: () => void;
  onFocus?: () => void;
  placeholder?: string;
  label?: string;
  error?: string;
  helperText?: string;
  size?: SelectSize;
  disabled?: boolean;
  children: ReactNode;
  containerStyle?: StyleProp<ViewStyle>;
  accessibilityLabel?: string;
  testID?: string;
}

const triggerSizeMap = {
  sm: styles.triggerSm,
  md: styles.triggerMd,
  lg: styles.triggerLg,
} as const;

type SelectItemElement = ReactElement<{ label: string; value: string }>;

const extractOptions = (children: ReactNode): { label: string; value: string }[] =>
  Children.toArray(children)
    .filter((child): child is SelectItemElement => {
      if (!isValidElement(child)) return false;
      const props = (child as SelectItemElement).props;
      return typeof props?.label === 'string' && typeof props?.value === 'string';
    })
    .map((child) => ({ label: child.props.label, value: child.props.value }));

export const Select = forwardRef<SelectHandle, SelectProps>(
  (
    {
      value,
      onValueChange,
      onBlur,
      onFocus,
      placeholder,
      label,
      error,
      helperText,
      size = 'md',
      disabled = false,
      children,
      containerStyle,
      accessibilityLabel,
      testID,
    },
    ref
  ) => {
    const pickerRef = useRef<Picker<string>>(null);
    const [isFocused, setIsFocused] = useState(false);
    const [modalVisible, setModalVisible] = useState(false);
    const hasError = Boolean(error);

    useImperativeHandle(
      ref,
      () => ({
        focus: () => {
          if (Platform.OS === 'android') {
            pickerRef.current?.focus();
          } else {
            setModalVisible(true);
          }
        },
        blur: () => {
          if (Platform.OS === 'android') {
            pickerRef.current?.blur();
          } else {
            setModalVisible(false);
          }
        },
      }),
      []
    );

    const options = extractOptions(children);
    const selectedLabel = options.find((opt) => opt.value === value)?.label ?? '';
    const showPlaceholder = !value || !selectedLabel;
    const displayText = showPlaceholder ? (placeholder ?? '') : selectedLabel;
    const a11yLabel = label ?? accessibilityLabel ?? placeholder ?? 'Selecione uma opção';
    const isTriggerFocused = Platform.OS === 'android' ? isFocused : modalVisible;

    const triggerStyle = [
      styles.trigger,
      triggerSizeMap[size],
      isTriggerFocused && !hasError && !disabled && styles.triggerFocused,
      hasError && styles.triggerError,
      disabled && styles.triggerDisabled,
    ];

    const renderFeedback = () => {
      if (hasError) return <Text style={styles.errorText}>{error}</Text>;
      if (helperText) return <Text style={styles.helperText}>{helperText}</Text>;
      return null;
    };

    if (Platform.OS === 'android') {
      return (
        <View style={[styles.container, containerStyle]} testID={testID}>
          {label && <Text style={styles.label}>{label}</Text>}
          <View
            style={triggerStyle}
            accessibilityLabel={a11yLabel}
            accessibilityState={{ disabled }}
          >
            <Picker
              ref={pickerRef}
              enabled={!disabled}
              selectedValue={value}
              onValueChange={(itemValue) => onValueChange(String(itemValue ?? ''))}
              onFocus={() => {
                setIsFocused(true);
                onFocus?.();
              }}
              onBlur={() => {
                setIsFocused(false);
                onBlur?.();
              }}
            >
              {placeholder && (
                <Picker.Item label={placeholder} value="" color={colors.textPlaceholder} />
              )}
              {children}
            </Picker>
          </View>
          {renderFeedback()}
        </View>
      );
    }

    const closeModal = (didSelect: boolean, selectedValue?: string) => {
      if (didSelect && selectedValue !== undefined) {
        onValueChange(selectedValue);
      }
      setModalVisible(false);
      onBlur?.();
    };

    return (
      <View style={[styles.container, containerStyle]} testID={testID}>
        {label && <Text style={styles.label}>{label}</Text>}
        <TouchableOpacity
          style={triggerStyle}
          disabled={disabled}
          activeOpacity={0.7}
          onPress={() => {
            setModalVisible(true);
            onFocus?.();
          }}
          accessibilityRole="button"
          accessibilityLabel={a11yLabel}
          accessibilityState={{ disabled }}
        >
          <Text
            style={[
              styles.triggerText,
              showPlaceholder && styles.triggerTextPlaceholder,
              disabled && styles.triggerTextDisabled,
            ]}
            numberOfLines={1}
            ellipsizeMode="tail"
          >
            {displayText}
          </Text>
        </TouchableOpacity>
        <Modal
          visible={modalVisible}
          animationType="fade"
          transparent
          onRequestClose={() => closeModal(false)}
        >
          <Pressable
            style={styles.modalBackdrop}
            onPress={() => closeModal(false)}
            accessibilityLabel="Fechar seleção"
          >
            <View style={styles.modalContent}>
              <FlatList
                data={options}
                keyExtractor={(item) => item.value}
                renderItem={({ item }) => {
                  const isSelected = value === item.value;
                  return (
                    <TouchableOpacity
                      style={styles.modalItem}
                      onPress={() => closeModal(true, item.value)}
                      accessibilityRole="button"
                      accessibilityLabel={`Selecionar opção: ${item.label}`}
                    >
                      <Text
                        style={[styles.modalItemText, isSelected && styles.modalItemTextSelected]}
                      >
                        {item.label}
                      </Text>
                    </TouchableOpacity>
                  );
                }}
                ListFooterComponent={
                  <TouchableOpacity
                    style={styles.modalCancelButton}
                    onPress={() => closeModal(false)}
                    accessibilityRole="button"
                    accessibilityLabel="Cancelar seleção"
                  >
                    <Text style={styles.modalCancelText}>Cancelar</Text>
                  </TouchableOpacity>
                }
              />
            </View>
          </Pressable>
        </Modal>
        {renderFeedback()}
      </View>
    );
  }
);

Select.displayName = 'Select';
