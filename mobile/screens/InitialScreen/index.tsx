import React from 'react';
import { View, Text, Pressable, Image, ImageBackground } from 'react-native';
import { useRouter } from 'expo-router';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';

export default function InitialScreen() {
  const router = useRouter();

  return (
    <ImageBackground
      source={require('@/assets/images/img-fundo.svg')}
      style={styles.imageBackground}
      resizeMode="cover"
    >
      <View style={styles.container}>
        <View style={styles.imageContainer}>
          <Image source={require('@/assets/images/LogoIcon.png')} style={styles.logo} />
        </View>

        <View style={styles.textContainer}>
          <Text style={styles.title}>Bem vindo ao DoaFarma!</Text>
          <Text style={styles.caption}>Aqui sua doação é mais fácil</Text>

          <PrimaryButton onPress={() => router.push('/register')} label="Registrar" />

          <Pressable
            onPress={() => console.log('Entrar')}
            style={({ pressed }) => [
              styles.buttonSecondary,
              pressed && styles.buttonHoverSecondary,
            ]}
          >
            {({ pressed }) => (
              <Text
                style={[styles.buttonTextSecondary, pressed && styles.buttonTextSecondaryHover]}
              >
                Entrar
              </Text>
            )}
          </Pressable>
        </View>
      </View>
    </ImageBackground>
  );
}
