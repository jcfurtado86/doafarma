import React from 'react';
import { useRouter } from 'expo-router';
import { View, Text, Pressable, StyleSheet, Image, ImageBackground } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import PrimaryButton from '../components/PrimaryButton';

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

const styles = StyleSheet.create({
  imageBackground: {
    flex: 1,
    width: '100%',
    height: '100%',
    resizeMode: 'cover',
    position: 'absolute',
    backgroundColor: '#ffffff',
  },

  container: {
    flex: 1,
    justifyContent: 'space-between',
    marginTop: 50,
    paddingBottom: 50,
    padding: 20,
  },

  backButton: {
    position: 'absolute',
    top: 20,
    left: 10,
    zIndex: 10,
    padding: 10,
  },

  textContainer: {
    textAlign: 'left',
    justifyContent: 'flex-start',
  },

  title: {
    fontSize: 24,
    fontWeight: '500',
    marginBottom: 10,
  },

  subtitle: {
    fontSize: 16,
    color: '#666',
    marginBottom: 10,
  },

  imageContainer: {
    alignItems: 'center',
  },

  image: {
    width: 230,
    height: 230,
    resizeMode: 'contain',
    marginBottom: 16,
    marginTop: 32,
    alignItems: 'center',
  },

  backButtonPressed: {
    opacity: 0.7,
  },
});
