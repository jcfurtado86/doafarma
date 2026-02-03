import React from 'react';
import { View, TextInput, Text, TextInputProps } from 'react-native';
import { formatTimeInput } from '@/utils/validation/timeHelpers';
import { styles } from './styles';

interface TimeInputProps extends Omit<TextInputProps, 'value' | 'onChangeText'> {
  value: string;
  onChange: (time: string) => void;
  error?: string;
  placeholder?: string;
}

export function TimeInput({
  value,
  onChange,
  error,
  placeholder = 'HH:MM',
  ...rest
}: TimeInputProps) {
  const handleChange = (text: string) => {
    const formatted = formatTimeInput(text);
    onChange(formatted);
  };

  return (
    <View style={styles.container}>
      <TextInput
        style={[styles.input, error && styles.inputError]}
        value={value}
        onChangeText={handleChange}
        placeholder={placeholder}
        keyboardType="numeric"
        maxLength={5}
        {...rest}
      />
      {error && <Text style={styles.errorText}>{error}</Text>}
    </View>
  );
}
