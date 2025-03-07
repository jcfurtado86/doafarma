import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
//import { useRouter } from 'expo-router';

export default function DoctorRegistrationScreen() {
  //const router = useRouter();

  return (
    <View style={styles.container}>
      <View style={styles.textContainer}>
        <Text style={styles.title}>Olá doutor!</Text>
        <Text style={styles.caption}>Preencha seus dados pessoais</Text>
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
  },

  textContainer: {
    textAlign: 'left',
    justifyContent: 'flex-end',
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
});
