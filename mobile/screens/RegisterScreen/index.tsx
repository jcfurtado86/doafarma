import React from 'react';
import { useRouter } from 'expo-router';
import { View, Text, Pressable, Image, ImageBackground } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';

export default function RegisterScreen() {
  const router = useRouter();

  return (
    <ImageBackground
      source={require('@/assets/images/img-fundo2.svg')}
      style={styles.imageBackground}
    >
      <Pressable
        style={({ pressed }) => [styles.backButton, pressed ? styles.backButtonPressed : null]}
        onPress={() => router.push('/')}
      >
        <Ionicons name="arrow-back" size={28} color="#A4C457" />
      </Pressable>

      <View style={styles.container}>
        <View style={styles.textContainer}>
          <Text style={styles.title}>Crie sua Conta</Text>
          <Text style={styles.subtitle}>Nos diga se você é um paciente ou um médico</Text>
        </View>

        <View style={styles.imageContainer}>
          <Image source={require('@/assets/images/Paciente.png')} style={styles.image} />

          <PrimaryButton onPress={() => console.log('Paciente')} label="Paciente" />

          <Image source={require('@/assets/images/Medico.png')} style={styles.image} />

          <PrimaryButton onPress={() => router.push('/register/doctor')} label="Médico" />
        </View>
      </View>
    </ImageBackground>
  );
}
