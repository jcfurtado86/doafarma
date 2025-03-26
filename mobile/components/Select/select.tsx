import { styles } from './styles';
import { Controller, FieldValues, UseControllerProps } from 'react-hook-form';
import { forwardRef, useState } from 'react';
import { Picker, PickerProps } from '@react-native-picker/picker';
import { StyleProp, Text, TextInput, TextStyle, View, ViewStyle } from 'react-native';

interface SelectProps<T extends FieldValues = FieldValues> {
  formProps: UseControllerProps<T>;
  selectProps: PickerProps;
  children: React.ReactNode;
  styleView?: StyleProp<ViewStyle>;
  styleSelect?: StyleProp<TextStyle>;
  nextRef?: React.RefObject<TextInput | Picker<string | number>>;
  error?: string;
}

const Select = forwardRef<Picker<string | number>, SelectProps<any>>(
  ({ formProps, selectProps, children, styleView, styleSelect, nextRef, error }, ref) => {
    const [isFocused, setIsFocused] = useState(false);
    const hasError = !!error;

    return (
      <View style={[styles.container, hasError ? { marginBottom: 8 } : { marginBottom: 16 }]}>
        <Controller
          render={({ field }) => (
            <View
              style={[
                styles.selectContainer,
                isFocused && styles.selectContainerFocused,
                hasError && styles.selectContainerError,
                styleView,
              ]}
            >
              <Picker
                ref={ref}
                style={[styleSelect]}
                onFocus={() => setIsFocused(true)}
                onBlur={() => setIsFocused(false)}
                onValueChange={(itemValue) => {
                  field.onChange(itemValue);

                  if (nextRef && itemValue) {
                    nextRef.current?.focus();
                  }
                }}
                selectedValue={field.value}
                {...selectProps}
              >
                <Picker.Item
                  label={selectProps.placeholder}
                  value=""
                  color={selectProps.selectedValue ? undefined : '#AFB2BF'}
                  enabled={!isFocused}
                />
                {children}
              </Picker>
            </View>
          )}
          {...formProps}
        />
        {hasError && <Text style={styles.errorText}>{error}</Text>}
      </View>
    );
  }
);

Select.displayName = 'Select';

export { Select };
