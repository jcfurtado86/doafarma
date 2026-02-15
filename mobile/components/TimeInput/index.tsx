import React, { useCallback, memo } from 'react';
import { View, TextInput, Text, TextInputProps } from 'react-native';
import { formatTimeInput } from '@/utils/validation/timeHelpers';
import { styles } from './styles';
import { a11y } from '@/utils/accessibility';

interface TimeInputProps extends Omit<TextInputProps, 'value' | 'onChangeText' | 'onChange'> {
  value: string;
  onChange: (time: string) => void;
  error?: string;
  placeholder?: string;
}

export const TimeInput = memo(function TimeInput({
  value,
  onChange,
  error,
  placeholder = 'HH:MM',
  ...rest
}: TimeInputProps) {
  const handleChange = useCallback(
    (text: string) => {
      const formatted = formatTimeInput(text);
      onChange(formatted);
    },
    [onChange]
  );

  return (
    <View style={styles.container}>
      <TextInput
        {...a11y.input('Horário', 'Formato hora, minutos')}
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
});
