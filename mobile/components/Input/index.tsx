import { TextInput, TextInputProps, StyleProp, TextStyle } from 'react-native';
import { styles } from './styles';
import { Controller, UseControllerProps } from 'react-hook-form';
import { useState } from 'react';

interface InputProps {
  formProps: UseControllerProps;
  inputProps: TextInputProps;
  style?: StyleProp<TextStyle>;
}

export function Input({ formProps, inputProps, style }: InputProps) {
  const [isFocused, setIsFocused] = useState(false);
  return (
    <Controller
      render={() => (
        <TextInput
          style={[styles.input, isFocused && styles.inputFocused, style]}
          onFocus={() => setIsFocused(true)}
          onBlur={() => setIsFocused(false)}
          {...inputProps}
        />
      )}
      {...formProps}
    />
  );
}
