import React, { useMemo, useCallback, memo } from 'react';
import { View, TextInput, Text, TextInputProps } from 'react-native';
import {
  formatDateInput,
  convertDateToAPI,
  convertDateFromAPI,
} from '@/utils/validation/dateHelpers';
import { styles } from './styles';

interface DateInputProps extends Omit<TextInputProps, 'value' | 'onChangeText'> {
  value: string;
  onChange: (date: string) => void;
  error?: string;
  placeholder?: string;
}

export const DateInput = memo(function DateInput({
  value,
  onChange,
  error,
  placeholder = 'DD/MM/AAAA',
  ...rest
}: DateInputProps) {
  const displayValue = useMemo(
    () => (value.includes('-') ? convertDateFromAPI(value) : value),
    [value]
  );

  const handleChange = useCallback(
    (text: string) => {
      const formatted = formatDateInput(text);
      if (formatted.length === 10) {
        onChange(convertDateToAPI(formatted));
      } else {
        onChange(formatted);
      }
    },
    [onChange]
  );

  return (
    <View style={styles.container}>
      <TextInput
        style={[styles.input, error && styles.inputError]}
        value={displayValue}
        onChangeText={handleChange}
        placeholder={placeholder}
        keyboardType="numeric"
        maxLength={10}
        {...rest}
      />
      {error && <Text style={styles.errorText}>{error}</Text>}
    </View>
  );
});
