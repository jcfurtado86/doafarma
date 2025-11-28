import React, { useRef } from 'react';
import { View, StyleSheet, Alert, TextInput } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import SecondaryButton from '@/components/SecondaryButton';
import { z } from 'zod';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import api from '@/services/api';

const loginSchema = z.object({
  email: z
    .string({ required_error: 'Email é obrigatório' })
    .email('Email inválido')
    .max(255, 'Email não pode ter mais de 255 caracteres'),
  password: z.string({ required_error: 'Senha é obrigatória' }).min(1, 'Senha é obrigatória'),
});

type LoginFormData = z.infer<typeof loginSchema>;

export default function LoginScreen() {
  const router = useRouter();
  const { saveSession } = useAuthStore();

  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useFeatureForm<LoginFormData>({
    schema: loginSchema,
  });

  const emailRef = useRef<TextInput>(null);
  const passwordRef = useRef<TextInput>(null);

  const handleLogin = async (data: LoginFormData) => {
    try {
      const response = await api.post('/v1/auth/login', data);
      const { data: loginData } = response.data;

      await saveSession(loginData.user, loginData.token);
      router.replace('/(auth)/dashboard');
    } catch (error: any) {
      console.error('Login error:', error);

      if (error.response?.status === 422) {
        const errors = error.response.data.errors;
        const firstError = Object.values(errors)[0];
        Alert.alert('Erro', Array.isArray(firstError) ? firstError[0] : firstError);
      } else if (error.response?.status === 401) {
        Alert.alert('Erro', 'Email ou senha incorretos');
      } else if (error.response?.status === 429) {
        Alert.alert('Erro', 'Muitas tentativas de login. Tente novamente mais tarde.');
      } else {
        Alert.alert('Erro', 'Erro ao fazer login. Tente novamente.');
      }
    }
  };

  const handleGoBack = () => {
    router.back();
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Title>Entrar</Title>
        <Caption>Faça login para acessar sua conta</Caption>
      </View>

      <View style={styles.form}>
        <Input
          ref={emailRef}
          formProps={{
            name: 'email',
            control: control,
          }}
          inputProps={{
            placeholder: 'Digite seu email',
            keyboardType: 'email-address',
            autoCapitalize: 'none',
            autoComplete: 'email',
            returnKeyType: 'next',
            onSubmitEditing: () => passwordRef.current?.focus(),
          }}
          error={errors.email?.message}
        />

        <Input
          ref={passwordRef}
          formProps={{
            name: 'password',
            control: control,
          }}
          inputProps={{
            placeholder: 'Digite sua senha',
            secureTextEntry: true,
            autoComplete: 'password',
            returnKeyType: 'done',
            onSubmitEditing: () => handleSubmit(handleLogin)(),
          }}
          error={errors.password?.message}
        />

        <View style={styles.buttonContainer}>
          <PrimaryButton
            label={isSubmitting ? 'Entrando...' : 'Entrar'}
            onPress={() => handleSubmit(handleLogin)()}
            disabled={isSubmitting}
          />

          <SecondaryButton label="Voltar" onPress={handleGoBack} />
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    backgroundColor: '#ffffff',
  },
  header: {
    marginTop: 60,
    marginBottom: 40,
  },
  form: {
    flex: 1,
  },
  buttonContainer: {
    marginTop: 30,
    gap: 16,
  },
});
