import React from 'react';
import { View, Pressable } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';
import { useForm } from 'react-hook-form';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { InputRow } from '@/components/InputRow';
export default function DoctorRegistrationScreen() {
  const router = useRouter();
  const { control } = useForm();
  return (
    <View style={styles.container}>
      <Pressable
        style={({ pressed }) => [styles.backButton, pressed ? styles.backButtonPressed : null]}
        onPress={() => router.push('/register/doctor')}
      >
        <Ionicons name="arrow-back" size={28} color="#A4C457" />
      </Pressable>

      <View style={styles.textContainer}>
        <Title>Onde podemos te encontra?!</Title>
        <Caption>Você pode adicionar o endereço do seu consultório ou clínica!</Caption>
      </View>

      <View style={styles.inputContainer}>
        <Input
          formProps={{
            name: 'Addresses',
            control: control,
          }}
          inputProps={{
            onChangeText: (text) => {
              console.log(text);
            },
            placeholder: 'Nome do consultório ou clinica',
          }}
        />
        <Input
          formProps={{
            name: 'cep',
            control: control,
          }}
          inputProps={{
            onChangeText: (text) => {
              console.log(text);
            },
            placeholder: 'CEP',
          }}
        />
        <InputRow>
          <Input
            formProps={{
              name: 'uf',
              control: control,
            }}
            inputProps={{
              onChangeText: (text) => {
                console.log(text);
              },
              placeholder: 'Estado',
            }}
            style={{ flex: 1 }}
          />
          <Input
            formProps={{
              name: 'city',
              control: control,
            }}
            inputProps={{
              onChangeText: (text) => {
                console.log(text);
              },
              placeholder: 'Cidade',
            }}
            style={{ flex: 1 }}
          />
        </InputRow>
        <Input
          formProps={{
            name: 'neighborhood',
            control: control,
          }}
          inputProps={{
            onChangeText: (text) => {
              console.log(text);
            },
            placeholder: 'Bairro',
          }}
        />
        <InputRow>
          <Input
            formProps={{
              name: 'full_address',
              control: control,
            }}
            inputProps={{
              onChangeText: (text) => {
                console.log(text);
              },
              placeholder: 'Rua ou Avenida',
            }}
            style={{ flex: 3 }}
          />
          <Input
            formProps={{
              name: 'Number',
              control: control,
            }}
            inputProps={{
              onChangeText: (text) => {
                console.log(text);
              },
              placeholder: 'N°000',
            }}
            style={{ flex: 1 }}
          />
        </InputRow>
        <Input
          formProps={{
            name: 'Complement',
            control: control,
          }}
          inputProps={{
            onChangeText: (text) => {
              console.log(text);
            },
            placeholder: 'Complemento',
          }}
        />
      </View>
      <View style={styles.buttonContainer}>
        <PrimaryButton onPress={() => router.push('/register/doctoraddress')} label="Próximo" />
      </View>
    </View>
  );
}
