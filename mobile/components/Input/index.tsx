import { TextInput, TextInputProps, StyleProp, TextStyle } from 'react-native';
import { styles } from './styles';
import { Controller, UseControllerProps } from 'react-hook-form';
import { forwardRef, useState } from 'react';

interface InputProps {
  formProps: UseControllerProps;
  inputProps: TextInputProps;
  style?: StyleProp<TextStyle>;
}

const Input = forwardRef<TextInput, InputProps>(({ formProps, inputProps, style }, ref) => {
  const [isFocused, setIsFocused] = useState(false);
  return (
    <Controller
      render={({ field }) => (
        <TextInput
          ref={ref}
          style={[styles.input, isFocused && styles.inputFocused, style]}
          value={field.value}
          onChangeText={field.onChange}
          onFocus={() => setIsFocused(true)}
          onBlur={() => setIsFocused(false)}
          {...inputProps}
        />
      )}
      {...formProps}
    />
  );
});

Input.displayName = 'Input';

export { Input };
