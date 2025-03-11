import React from 'react';
import { View, Pressable } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
//import { Input } from '@/components/Input';
//import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';
//import { useForm } from 'react-hook-form';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
export default function DoctorRegistrationScreen() {
  const router = useRouter();
  //const { control } = useForm();
  return (
    <View style={styles.container}>
      <Pressable
        style={({ pressed }) => [styles.backButton, pressed ? styles.backButtonPressed : null]}
        onPress={() => router.push('/register')}
      >
        <Ionicons name="arrow-back" size={28} color="#A4C457" />
      </Pressable>

      <View style={styles.textContainer}>
        <Title>Onde podemos te encontra?!</Title>
        <Caption>Você pode adicionar o endereço do seu consultório ou clínica!</Caption>
      </View>
    </View>
  );
}
