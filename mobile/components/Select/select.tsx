import { styles } from './styles';
import { Controller, UseControllerProps } from 'react-hook-form';
import { forwardRef, useState } from 'react';
import { Picker, PickerProps } from '@react-native-picker/picker';
import { StyleProp, TextInput, TextStyle, View, ViewStyle } from 'react-native';

interface SelectProps {
  formProps: UseControllerProps;
  selectProps: PickerProps;
  children: React.ReactNode;
  styleView?: StyleProp<ViewStyle>;
  styleSelect?: StyleProp<TextStyle>;
  nextRef?: React.RefObject<TextInput | Picker<string | number>>;
}

const Select = forwardRef<Picker<string | number>, SelectProps>(
  ({ formProps, selectProps, children, styleView, styleSelect, nextRef }, ref) => {
    const [selectedValue, setSelectedValue] = useState<string | number | undefined>(
      selectProps.selectedValue || undefined
    );
    const [isFocused, setIsFocused] = useState(false);
    return (
      <Controller
        render={() => (
          <View
            style={[styles.selectContainer, isFocused && styles.selectContainerFocused, styleView]}
          >
            <Picker
              ref={ref}
              style={[styleSelect]}
              onFocus={() => setIsFocused(true)}
              onBlur={() => setIsFocused(false)}
              onValueChange={(itemValue) => {
                setSelectedValue(itemValue);

                if (nextRef && itemValue) {
                  nextRef.current?.focus();
                }
              }}
              selectedValue={selectedValue}
              {...selectProps}
            >
              <Picker.Item
                label={selectProps.placeholder}
                value=""
                color={selectedValue ? undefined : '#AFB2BF'}
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
);

Select.displayName = 'Select';

export { Select };
