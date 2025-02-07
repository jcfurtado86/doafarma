import React from 'react';
import { View, Text, Pressable, StyleSheet, Image, ImageBackground } from 'react-native';

export default function RegisterScreen() {
  return (
    <ImageBackground source={require('@/assets/images/img-fundo2.svg')} style={styles.imageBackground}>
      <View style={styles.container}>
        <View style={styles.textContainer}>
          <Text style={styles.title}>Crie sua Conta</Text>
          <Text style={styles.subtitle}>Nos diga se você é um paciente ou um médico</Text>
        </View>

        <View style={styles.imageContainer}>
          <Image source={require('@/assets/images/Paciente.png')} style={styles.image} />

          <Pressable style={styles.buttonPrimary} onPress={() => console.log('Paciente')}>
            <Text style={styles.buttonText}>Paciente</Text>
          </Pressable>

          <Image source={require('@/assets/images/Medico.png')} style={styles.image} />

          <Pressable style={styles.buttonSecondary} onPress={() => console.log('Médico')}>
            <Text style={styles.buttonText}>Medico</Text>
          </Pressable>
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
    backgroundColor: "#ffffff",
  },

  container: {
    flex: 1,
    justifyContent: 'space-between',
    paddingBottom: 50,
    padding: 20,
  },

  textContainer: {
    textAlign: 'left', 
    justifyContent: "flex-start",
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

  buttonPrimary: {
    backgroundColor: '#A4C457',
    padding: 15,
    borderRadius: 10,
    marginBottom: 16,
    height: 48,
    width: '100%',
    alignItems: 'center',
  },

  buttonSecondary: {
    backgroundColor: '#A4C457',
    padding: 15,
    borderRadius: 10,
    marginBottom: 16,
    height: 48,
    width: '100%',
    alignItems: 'center',
  },

  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },

});
