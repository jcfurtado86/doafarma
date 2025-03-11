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
          }}
        />
      </View>
      <View style={styles.buttonContainer}>
        <PrimaryButton onPress={() => console.log('Próximo')} label="Próximo" />
      </View>
    </View>
  );
}
