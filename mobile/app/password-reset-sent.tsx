import React from 'react';
import { View, StyleSheet, Text, TouchableOpacity, ImageBackground } from 'react-native';
import { useRouter, Href } from 'expo-router';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Button } from '@/components/ui';
import { colors } from '@/theme/tokens';
import { Ionicons } from '@expo/vector-icons';

export default function PasswordResetSentScreen() {
  const router = useRouter();

  return (
    <ImageBackground
      source={require('@/assets/images/img-fundo2.png')}
      style={styles.background}
      resizeMode="cover"
    >
      <View style={styles.container}>
        <View style={styles.content}>
          <View style={styles.iconContainer}>
            <Ionicons name="mail-outline" size={64} color={colors.primaryPressed} />
          </View>

          <Title>E-mail enviado!</Title>
          <Caption>
            Verifique sua caixa de entrada e clique no link para redefinir sua senha.
          </Caption>

          <View style={styles.buttonContainer}>
            <Button
              label="Ja tenho o codigo"
              onPress={() => router.push('/password-reset/manual' as Href)}
            />
          </View>

          <TouchableOpacity onPress={() => router.replace('/login' as Href)}>
            <Text style={styles.backToLoginLink}>Voltar ao login</Text>
          </TouchableOpacity>
        </View>
      </View>
    </ImageBackground>
  );
}

const styles = StyleSheet.create({
  background: {
    flex: 1,
  },
  container: {
    flex: 1,
    justifyContent: 'center',
    padding: 20,
  },
  content: {
    alignItems: 'center',
  },
  iconContainer: {
    marginBottom: 24,
  },
  buttonContainer: {
    width: '100%',
    marginTop: 32,
  },
  backToLoginLink: {
    color: colors.primaryPressed,
    fontSize: 14,
    fontWeight: '600',
    textDecorationLine: 'underline',
    marginTop: 24,
  },
});
