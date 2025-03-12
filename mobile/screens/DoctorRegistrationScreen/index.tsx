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
        onPress={() => router.push('/register')}
      >
        <Ionicons name="arrow-back" size={28} color="#A4C457" />
      </Pressable>

      <View style={styles.textContainer}>
        <Title>Olá doutor!</Title>
        <Caption>Preencha seus dados pessoais</Caption>
      </View>
      <View style={styles.inputContainer}>
        <Input
          formProps={{
            name: 'doctorName',
            control: control,
          }}
          inputProps={{
            onChangeText: (text) => {
              console.log(text);
            },
            placeholder: 'Nome Completo',
          }}
        />

        <InputRow>
          <Input
            formProps={{
              name: 'crm',
              control: control,
            }}
            inputProps={{
              onChangeText: (text) => {
                console.log(text);
              },
              placeholder: 'CRM',
            }}
            style={{ flex: 2 }}
          />
          <Input
            formProps={{
              name: 'crm_uf',
              control: control,
            }}
            inputProps={{
              onChangeText: (text) => {
                console.log(text);
              },
              placeholder: 'UF',
            }}
            style={{ flex: 1 }}
          />
        </InputRow>
        <InputRow>
          <Input
            formProps={{
              name: 'ddd',
              control: control,
            }}
            inputProps={{
              onChangeText: (text) => {
                console.log(text);
              },
              placeholder: 'DDD',
            }}
            style={{ flex: 1 }}
          />
          <Input
            formProps={{
              name: 'phone_number',
              control: control,
            }}
            inputProps={{
              onChangeText: (text) => {
                console.log(text);
              },
              placeholder: 'Telefone',
            }}
            style={{ flex: 3 }}
          />
        </InputRow>

        <Input
          formProps={{
            name: 'email',
            control: control,
          }}
          inputProps={{
            onChangeText: (text) => {
              console.log(text);
            },
            placeholder: 'Email',
          }}
        />
        <Input
          formProps={{
            name: 'password',
            control: control,
          }}
          inputProps={{
            onChangeText: (text) => {
              console.log(text);
            },
            placeholder: 'Senha',
          }}
        />
        <Input
          formProps={{
            name: 'password_confirmation',
            control: control,
          }}
          inputProps={{
            onChangeText: (text) => {
              console.log(text);
            },
            placeholder: 'Confirmar Senha',
          }}
        />
      </View>
      <View style={styles.buttonContainer}>
        <PrimaryButton onPress={() => router.push('/register/doctoraddress')} label="Próximo" />
      </View>
    </View>
  );
}
