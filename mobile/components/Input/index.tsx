import { TextInput, TextInputProps, StyleProp, TextStyle } from 'react-native';
import { styles } from './styles';
import { Controller, FieldValues, UseControllerProps } from 'react-hook-form';
import { forwardRef, useState } from 'react';

interface InputProps<T extends FieldValues = FieldValues> {
  formProps: UseControllerProps<T>;
  inputProps: TextInputProps;
  error?: string;
}

const Input = forwardRef<TextInput, InputProps<any>>(
  ({ formProps, inputProps, style, error }, ref) => {
    const [isFocused, setIsFocused] = useState(false);
    const hasError = !!error;

    return (
      <View
        style={[
          styles.container,
          hasError ? { marginBottom: 8 } : { marginBottom: 16 },
        ]}
      >
        <Controller
          render={({ field }) => (
            <TextInput
              ref={ref}
              style={[
                styles.input,
                isFocused && styles.inputFocused,
                hasError && styles.inputError,
                style,
              ]}
              value={field.value}
              onChangeText={field.onChange}
              onFocus={() => setIsFocused(true)}
              onBlur={() => setIsFocused(false)}
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
