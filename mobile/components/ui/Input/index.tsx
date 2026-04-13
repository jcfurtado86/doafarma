import { forwardRef, useState } from 'react';
import {
  StyleProp,
  Text,
  TextInput,
  TextInputProps,
  TextStyle,
  View,
  ViewStyle,
} from 'react-native';
import { colors } from '@/theme/tokens';
import { a11y } from '@/utils/accessibility';
import { styles } from './styles';

export type InputSize = 'sm' | 'md' | 'lg';

export interface InputProps extends Omit<TextInputProps, 'editable' | 'style'> {
  label?: string;
  error?: string;
  helperText?: string;
  size?: InputSize;
  disabled?: boolean;
  containerStyle?: StyleProp<ViewStyle>;
  inputStyle?: StyleProp<TextStyle>;
}

const sizeStyleMap = {
  sm: styles.sizeSm,
  md: styles.sizeMd,
  lg: styles.sizeLg,
} as const;

export const Input = forwardRef<TextInput, InputProps>(
  (
    {
      label,
      error,
      helperText,
      size = 'md',
      disabled = false,
      containerStyle,
      inputStyle,
      onFocus,
      onBlur,
      placeholder,
      placeholderTextColor,
      ...rest
    },
    ref
  ) => {
    const [isFocused, setIsFocused] = useState(false);
    const hasError = Boolean(error);

    return (
      <View style={[styles.container, containerStyle]}>
        {label && <Text style={styles.label}>{label}</Text>}
        <TextInput
          ref={ref}
          {...a11y.input(label ?? placeholder ?? '')}
          accessibilityState={{ disabled }}
          editable={!disabled}
          placeholder={placeholder}
          placeholderTextColor={placeholderTextColor ?? colors.textPlaceholder}
          style={[
            styles.input,
            sizeStyleMap[size],
            isFocused && !hasError && !disabled && styles.focused,
            hasError && styles.error,
            disabled && styles.disabled,
            inputStyle,
          ]}
          onFocus={(event) => {
            setIsFocused(true);
            onFocus?.(event);
          }}
          onBlur={(event) => {
            setIsFocused(false);
            onBlur?.(event);
          }}
          {...rest}
        />
        {hasError ? (
          <Text style={styles.errorText}>{error}</Text>
        ) : helperText ? (
          <Text style={styles.helperText}>{helperText}</Text>
        ) : null}
      </View>
    );
  }
);

Input.displayName = 'Input';
