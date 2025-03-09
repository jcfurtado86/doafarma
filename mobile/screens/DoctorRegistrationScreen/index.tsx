import React from 'react';
import { View, Text, Pressable } from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';

export default function DoctorRegistrationScreen() {
  const router = useRouter();

  return (
    <View style={styles.container}>
      <Pressable
        style={({ pressed }) => [styles.backButton, pressed ? styles.backButtonPressed : null]}
        onPress={() => router.push('/register')}
      >
        <Ionicons name="arrow-back" size={28} color="#A4C457" />
      </Pressable>

      <View style={styles.textContainer}>
        <Text style={styles.title}>Olá doutor!</Text>
        <Text style={styles.caption}>Preencha seus dados pessoais</Text>
      </View>
      <View style={styles.inputContainer}>
        <Input />
      </View>
      <View style={styles.buttonContainer}>
        <PrimaryButton onPress={() => console.log('Próximo')} label="Próximo" />
      </View>
    </View>
  );
}
