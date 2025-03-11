import { styles } from './styles';
import { Controller, UseControllerProps } from 'react-hook-form';
import { useState } from 'react';
import { Picker, PickerProps } from '@react-native-picker/picker';
import { View } from 'react-native';

interface SelectProps {
  formProps: UseControllerProps;
  selectProps: PickerProps;
  children: React.ReactNode;
}

export function Select({ formProps, selectProps, children }: SelectProps) {
  const [selectedLanguage, setSelectedLanguage] = useState<string | number | undefined>(undefined);
  const [isFocused, setIsFocused] = useState(false);
  return (
    <Controller
      render={() => (
        <View style={[styles.selectContainer, isFocused && styles.selectContainerFocused]}>
          <Picker
            onFocus={() => setIsFocused(true)}
            onBlur={() => setIsFocused(false)}
            onValueChange={(itemValue, itemIndex) => setSelectedLanguage(itemValue)}
            selectedValue={selectedLanguage}
            {...selectProps}
          >
            <Picker.Item
              label={selectProps.placeholder}
              value=""
              color={selectedLanguage ? undefined : '#AFB2BF'}
              enabled={!isFocused}
            />
            {children}
          </Picker>
        </View>
      )}
      {...formProps}
    />
  );
}
