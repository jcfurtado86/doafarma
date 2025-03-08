import React from 'react';
import { View, Text, StyleSheet, Pressable } from 'react-native';
import { Input } from '../components/Input';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

export default function DoctorRegistrationScreen() {
  const router = useRouter();

  return (
    <View style={styles.container}>
      <Pressable
        style={({ pressed }) => [styles.backButton, pressed ? styles.backButtonPressed : null]}
        onPress={() => router.push('/register/doctor')}
      >
        <Ionicons name="arrow-back" size={28} color="#A4C457" />
      </Pressable>

      <View style={styles.textContainer}>
        <Text style={styles.title}>Olá doutor!</Text>
        <Text style={styles.caption}>Preencha seus dados pessoais</Text>
        <Input />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'space-between',
    paddingBottom: 80,
    padding: 20,
    backgroundColor: '#ffffff',
  },

  textContainer: {
    textAlign: 'left',
    justifyContent: 'space-between',
    marginTop: 50,
    paddingBottom: 50,
  },

  title: {
    fontSize: 24,
    fontWeight: '500',
  },

  caption: {
    color: '#AFB2BF',
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 20,
    textAlign: 'left',
  },

  backButton: {
    position: 'absolute',
    top: 20,
    left: 10,
    zIndex: 10,
    padding: 10,
  },
  backButtonPressed: {
    opacity: 0.7,
  },
});
