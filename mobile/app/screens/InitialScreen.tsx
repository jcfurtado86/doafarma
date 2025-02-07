import React from 'react';
import { View, Text, Pressable, StyleSheet, Image, ImageBackground } from 'react-native';
import { useRouter } from 'expo-router';

export default function InitialScreen() {
  const router = useRouter();

  return (
    <ImageBackground source={require('@/assets/images/img-fundo.svg')} style={styles.imageBackground}>
      <View style={styles.container}>
        
        <View style={styles.imageContainer}>
          <Image source={require('@/assets/images/LogoIcon.png')} style={styles.logo} />
        </View>

        <View style={styles.textContainer}>
          <Text style={styles.title}>Bem vindo ao DoaFarma!</Text>
          <Text style={styles.caption}>Aqui sua doação é mais fácil</Text>

          <Pressable style={styles.buttonPrimary} onPress={() => router.push('/screens/RegisterScreen')}>
            <Text style={styles.buttonTextPrimary}>Registrar</Text>
          </Pressable>

          <Pressable style={styles.buttonSecondary} onPress={() => console.log('Entrar')}>
            <Text style={styles.buttonTextSecondary}>Entrar</Text>
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
    paddingBottom: 80,
    padding: 20,
  },

  imageContainer: {
    alignItems: 'center',
  },

  logo: {
    width: '80%',
    height: undefined,
    resizeMode: 'contain',
    marginTop: 250,
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

  buttonPrimary: {
    backgroundColor: '#A4C457',
    padding: 15,
    borderRadius: 10,
    marginBottom: 16,
    height: 56,
    width: '100%',
    alignItems: 'center',
  },

  buttonSecondary: {
    backgroundColor: '#ECF3D4',
    padding: 15,
    borderRadius: 10,
    height: 56,
    width: '100%',
    alignItems: 'center',
  },

  buttonTextPrimary: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },

  buttonTextSecondary: {
    color: '#A4C457',
    fontSize: 16,
    fontWeight: 'bold',
  },
});
