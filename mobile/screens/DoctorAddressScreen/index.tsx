import React, { useRef } from 'react';
import { View, Pressable, TextInput } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';
import { useForm } from 'react-hook-form';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { InputRow } from '@/components/InputRow';
import { Select, SelectItem } from '@/components/Select';
import { Picker } from '@react-native-picker/picker';
export default function DoctorRegistrationScreen() {
  const router = useRouter();
  const { control, handleSubmit } = useForm();

  const handleFinishRegistration = (data: any) => {
    console.log(data);
    //router.push('/dashboard');
  };

  const cepRef = useRef<TextInput>(null);
  const ufRef = useRef<Picker<string | number>>(null);
  const cityRef = useRef<Picker<string | number>>(null);
  const neighborhoodRef = useRef<TextInput>(null);
  const streetRef = useRef<TextInput>(null);
  const numberRef = useRef<TextInput>(null);
  const complementRef = useRef<TextInput>(null);

  return (
    <View style={styles.container}>
      <Pressable
        style={({ pressed }) => [styles.backButton, pressed ? styles.backButtonPressed : null]}
        onPress={() => router.push('/register/doctor')}
      >
        <Ionicons name="arrow-back" size={28} color="#A4C457" />
      </Pressable>

      <View style={styles.textContainer}>
        <Title>Onde podemos te encontrar?</Title>
        <Caption>Você pode adicionar o endereço do seu consultório ou clínica!</Caption>
      </View>

      <View style={styles.inputContainer}>
        <Input
          formProps={{
            name: 'Addresses',
            control: control,
          }}
          inputProps={{
            onSubmitEditing: () => cepRef.current?.focus(),
            returnKeyType: 'next',
            placeholder: 'Nome do consultório ou clinica',
          }}
        />
        <Input
          ref={cepRef}
          formProps={{
            name: 'cep',
            control: control,
          }}
          inputProps={{
            onSubmitEditing: () => ufRef.current?.focus(),
            returnKeyType: 'next',
            placeholder: 'CEP',
          }}
        />
        <InputRow>
          <Select
            ref={ufRef}
            formProps={{
              name: 'uf',
              control: control,
            }}
            selectProps={{
              placeholder: 'Estado',
            }}
            styleView={{ flex: 1 }}
            nextRef={cityRef}
          >
            <SelectItem label="AC" value="AC" />
            <SelectItem label="AL" value="AL" />
            <SelectItem label="AP" value="AP" />
            <SelectItem label="AM" value="AM" />
            <SelectItem label="BA" value="BA" />
          </Select>
          <Select
            ref={cityRef}
            formProps={{
              name: 'city',
              control: control,
            }}
            selectProps={{
              placeholder: 'Cidade',
            }}
            styleView={{ flex: 1 }}
            nextRef={neighborhoodRef}
          >
            <SelectItem label="Rio Branco" value="Rio Branco" />
            <SelectItem label="Maceió" value="Maceió" />
            <SelectItem label="Macapá" value="Macapá" />
          </Select>
        </InputRow>
        <Input
          ref={neighborhoodRef}
          formProps={{
            name: 'neighborhood',
            control: control,
          }}
          inputProps={{
            onSubmitEditing: () => streetRef.current?.focus(),
            returnKeyType: 'next',
            placeholder: 'Bairro',
          }}
        />
        <InputRow>
          <Input
            ref={streetRef}
            formProps={{
              name: 'full_address',
              control: control,
            }}
            inputProps={{
              onSubmitEditing: () => numberRef.current?.focus(),
              returnKeyType: 'next',
              placeholder: 'Rua ou Avenida',
            }}
            style={{ flex: 3 }}
          />
          <Input
            ref={numberRef}
            formProps={{
              name: 'Number',
              control: control,
            }}
            inputProps={{
              onSubmitEditing: () => complementRef.current?.focus(),
              returnKeyType: 'next',
              keyboardType: 'numeric',
              placeholder: 'N°000',
            }}
            style={{ flex: 1 }}
          />
        </InputRow>
        <Input
          ref={complementRef}
          formProps={{
            name: 'Complement',
            control: control,
          }}
          inputProps={{
            onSubmitEditing: () => handleSubmit(handleFinishRegistration)(),
            returnKeyType: 'done',
            placeholder: 'Complemento',
          }}
        />
      </View>
      <View style={styles.buttonContainer}>
        <PrimaryButton onPress={() => handleSubmit(handleFinishRegistration)()} label="Próximo" />
      </View>
    </View>
  );
}
