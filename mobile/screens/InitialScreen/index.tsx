import React from 'react';
import { View, Image, ImageBackground, Text } from 'react-native';
import { useRouter } from 'expo-router';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import SecondaryButton from '@/components/SecondaryButton';

export function InitialScreen() {
  const router = useRouter();

  return (
    <ImageBackground
      source={require('@/assets/images/img-fundo.png')}
      style={styles.imageBackground}
      resizeMode="cover"
    >
      <View style={styles.container}>
        <View style={styles.imageContainer}>
          <Image source={require('@/assets/images/LogoIcon.png')} style={styles.logo} />
        </View>

        <View style={styles.actionContainer}>
          <View style={styles.textContainer}>
            <Title>Bem vindo ao DoaFarma!</Title>
            <Caption>Aqui sua doação é mais fácil</Caption>
            <Text>API Host: {process.env.EXPO_PUBLIC_API_HOST}</Text>
          </View>
          <View style={styles.buttonContainer}>
            <PrimaryButton onPress={() => router.push('/register')} label="Registrar" />
            <SecondaryButton label="Entrar" onPress={() => console.log('Entrar')} />
          </View>
        </View>
      </View>
    </ImageBackground>
  );
}
