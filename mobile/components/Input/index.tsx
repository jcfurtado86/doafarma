import {
  TextInput,
  TextInputProps,
  StyleProp,
  TextStyle,
  View,
  Text,
  ViewStyle,
} from 'react-native';
import { styles } from './styles';
import { Controller, FieldValues, UseControllerProps } from 'react-hook-form';
import { ForwardedRef, ReactElement, RefAttributes, forwardRef, useState } from 'react';
import { a11y } from '@/utils/accessibility';
import { colors } from '@/theme/tokens';

interface InputProps<T extends FieldValues = FieldValues> {
  formProps: UseControllerProps<T>;
  inputProps: TextInputProps;
  containerStyle?: StyleProp<ViewStyle>;
  inputStyle?: StyleProp<TextStyle>;
  error?: string;
}

function InputInner<T extends FieldValues>(
  { formProps, inputProps, containerStyle, inputStyle, error }: InputProps<T>,
  ref: ForwardedRef<TextInput>
): ReactElement {
  const [isFocused, setIsFocused] = useState(false);
  const hasError = !!error;

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
          <TextInput
            ref={ref}
            {...a11y.input(inputProps.placeholder ?? '')}
            style={[
              styles.input,
              isFocused && styles.inputFocused,
              hasError && styles.inputError,
              inputStyle,
            ]}
            value={field.value}
            onChangeText={field.onChange}
            onFocus={() => setIsFocused(true)}
            onBlur={() => setIsFocused(false)}
            placeholderTextColor={inputProps.placeholderTextColor ?? colors.textPlaceholder}
            {...inputProps}
          />
        )}
        {...formProps}
      />
      {hasError && <Text style={styles.errorText}>{error}</Text>}
    </View>
  );
}

const InputBase = forwardRef(InputInner);
InputBase.displayName = 'Input';

const Input = InputBase as unknown as <T extends FieldValues>(
  props: InputProps<T> & RefAttributes<TextInput>
) => ReactElement | null;

export { Input };
