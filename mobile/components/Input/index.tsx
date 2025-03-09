import { TextInput, TextInputProps, View } from 'react-native';
import { styles } from './styles';
import { Controller, UseControllerProps } from 'react-hook-form';

interface InputProps {
  formProps: UseControllerProps;
  inputProps: TextInputProps;
}

export function Input({ formProps, inputProps }: InputProps) {
  return (
    <Controller
      render={() => (
        <View style={styles.group}>
          <TextInput style={styles.control} {...inputProps} />
        </View>
      )}
      {...formProps}
    />
  );
}
