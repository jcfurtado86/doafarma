import React, { useState } from 'react';
import {
  View,
  StyleSheet,
  Text,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
  ImageBackground,
} from 'react-native';
import { useRouter, Href } from 'expo-router';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import ArrowBackButton from '@/components/ArrowBackButton';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import { authService, AuthServiceError } from '@/services/authService';
import { forgotPasswordSchema, ForgotPasswordFormData } from '@/utils/validation/authValidation';
import { colors } from '@/theme/tokens';
import { Ionicons } from '@expo/vector-icons';

export default function ForgotPasswordScreen() {
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const {
    control,
    handleSubmit,
    formState: { errors },
    setError: setFormError,
  } = useFeatureForm<ForgotPasswordFormData>({
    schema: forgotPasswordSchema,
    defaultValues: {
      email: '',
    },
  });

  const handleForgotPassword = async (data: ForgotPasswordFormData) => {
    setIsLoading(true);
    setError(null);

    try {
      await authService.forgotPassword(data.email);
      router.push('/password-reset-sent' as Href);
    } catch (err) {
      if (err instanceof AuthServiceError) {
        if (err.isNetworkError) {
          setError('Sem conexao com a internet.');
        } else if (err.isRateLimited) {
          setError('Muitas tentativas. Aguarde alguns minutos.');
        } else if (err.isServerError) {
          setError('Erro no servidor. Tente novamente mais tarde.');
        } else if (err.isValidationError && err.validationErrors) {
          if (err.validationErrors.email?.[0]) {
            setFormError('email', { message: err.validationErrors.email[0] });
          } else {
            setError(err.message);
          }
        } else {
          setError(err.message);
        }
      } else {
        setError('Erro ao enviar e-mail. Tente novamente.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <ImageBackground
      source={require('@/assets/images/img-fundo2.png')}
      style={styles.background}
      resizeMode="cover"
    >
      <KeyboardAvoidingView
        style={styles.container}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          <ArrowBackButton onPress={() => router.back()} style={styles.backButton} />

          <View style={styles.header}>
            <Title>Esqueci minha senha</Title>
            <Caption>Informe seu e-mail para receber o link de redefinicao</Caption>
          </View>

          {error && (
            <View style={styles.errorContainer}>
              <Ionicons name="alert-circle-outline" size={20} color={colors.error} />
              <Text style={styles.errorText}>{error}</Text>
            </View>
          )}

          <View style={styles.form}>
            <Input
              formProps={{
                name: 'email',
                control: control,
              }}
              inputProps={{
                placeholder: 'E-mail',
                keyboardType: 'email-address',
                autoCapitalize: 'none',
                autoComplete: 'email',
                returnKeyType: 'done',
                onSubmitEditing: () => handleSubmit(handleForgotPassword)(),
                editable: !isLoading,
              }}
              error={errors.email?.message}
            />

            <View style={styles.buttonContainer}>
              {isLoading ? (
                <View style={styles.loadingButton}>
                  <ActivityIndicator color={colors.textInverted} />
                  <Text style={styles.loadingText}>Enviando...</Text>
                </View>
              ) : (
                <PrimaryButton
                  label="Enviar"
                  onPress={() => handleSubmit(handleForgotPassword)()}
                  disableWhenOffline
                />
              )}
            </View>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </ImageBackground>
  );
}

const styles = StyleSheet.create({
  background: {
    flex: 1,
  },
  container: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    padding: 20,
    justifyContent: 'center',
  },
  backButton: {
    position: 'absolute',
    top: 50,
    left: 0,
  },
  header: {
    marginBottom: 32,
    alignItems: 'center',
  },
  errorContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.errorSurface,
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
    gap: 8,
  },
  errorText: {
    color: colors.error,
    fontSize: 14,
    flex: 1,
  },
  form: {
    width: '100%',
  },
  buttonContainer: {
    marginTop: 24,
  },
  loadingButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.primary,
    paddingVertical: 16,
    borderRadius: 8,
    gap: 8,
  },
  loadingText: {
    color: colors.textInverted,
    fontSize: 16,
    fontWeight: '600',
  },
});
