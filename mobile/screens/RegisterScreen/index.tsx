import React from 'react';
import { useRouter, Href } from 'expo-router';
import { View, Image, ImageBackground } from 'react-native';
import { Button } from '@/components/ui';
import { styles } from './styles';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import ArrowBackButton from '@/components/ArrowBackButton';

export function RegisterScreen() {
  const router = useRouter();

  return (
    <ImageBackground
      source={require('@/assets/images/img-fundo2.png')}
      style={styles.imageBackground}
    >
      <ArrowBackButton style={{ marginTop: 56, marginLeft: 20 }} onPress={() => router.push('/')} />
      <View style={styles.container}>
        <View style={styles.textContainer}>
          <Title>Crie sua Conta</Title>
          <Caption>Nos diga se você é um paciente ou um médico</Caption>
        </View>

        <View style={styles.choiceContainer}>
          <View style={styles.imageContainer}>
            <Image source={require('@/assets/images/Paciente.png')} style={styles.patientImage} />
            <Button onPress={() => router.push('/register/receptor' as Href)} label="Paciente" />
          </View>

          <View style={styles.imageContainer}>
            <Image source={require('@/assets/images/Medico.png')} style={styles.doctorImage} />
            <Button onPress={() => router.push('/register/doctor?step=1')} label="Médico" />
          </View>
        </View>
      </View>
    </ImageBackground>
  );
}
