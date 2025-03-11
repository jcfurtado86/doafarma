import { TextInput, TextInputProps } from 'react-native';
import { styles } from './styles';
import { Controller, UseControllerProps } from 'react-hook-form';
import { useState } from 'react';

interface InputProps {
  formProps: UseControllerProps;
  inputProps: TextInputProps;
}

export function Input({ formProps, inputProps }: InputProps) {
  const [isFocused, setIsFocused] = useState(false);
  return (
    <Controller
      render={() => (
        <TextInput
          style={[styles.input, isFocused && styles.inputFocused]}
          onFocus={() => setIsFocused(true)}
          onBlur={() => setIsFocused(false)}
          {...inputProps}
        />
      )}
      {...formProps}
    />
  );
}
