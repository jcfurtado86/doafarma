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
import { forwardRef, useState } from 'react';
import { a11y } from '@/utils/accessibility';
import { colors } from '@/theme/tokens';

interface InputProps<T extends FieldValues = FieldValues> {
  formProps: UseControllerProps<T>;
  inputProps: TextInputProps;
  containerStyle?: StyleProp<ViewStyle>;
  inputStyle?: StyleProp<TextStyle>;
  error?: string;
}

const Input = forwardRef<TextInput, InputProps<any>>(
  ({ formProps, inputProps, containerStyle, inputStyle, error }, ref) => {
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
);

Input.displayName = 'Input';

export { Input };
