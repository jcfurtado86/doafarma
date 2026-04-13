import React, { useRef, useState } from 'react';
import {
  View,
  StyleSheet,
  TextInput,
  Text,
  TouchableOpacity,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
  ImageBackground,
} from 'react-native';
import { useRouter, Href } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Input } from '@/components/ui/Input';
import PrimaryButton from '@/components/PrimaryButton';
import { z } from 'zod';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import { authService, AuthServiceError } from '@/services/authService';
import { isUserBlocked, UserRole } from '@/types/user';
import { colors } from '@/theme/tokens';
import { Ionicons } from '@expo/vector-icons';
import * as Device from 'expo-device';
import { logger } from '@/utils/logger';

const loginSchema = z.object({
  email: z
    .string({ required_error: 'E-mail é obrigatório' })
    .email('E-mail inválido')
    .max(255, 'E-mail não pode ter mais de 255 caracteres'),
  password: z.string({ required_error: 'Senha é obrigatória' }).min(1, 'Senha é obrigatória'),
});

type LoginFormData = z.infer<typeof loginSchema>;

export default function LoginScreen() {
  const router = useRouter();
  const { saveSession, setupPushNotifications } = useAuthStore();
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const {
    control,
    handleSubmit,
    formState: { errors },
    setError: setFormError,
  } = useFeatureForm<LoginFormData>({
    schema: loginSchema,
    defaultValues: {
      email: '',
      password: '',
    },
  });

  const passwordRef = useRef<TextInput>(null);

  const getDeviceName = async (): Promise<string> => {
    try {
      const deviceName =
        Device.deviceName || `${Device.brand || ''} ${Device.modelName || ''}`.trim();
      return deviceName || 'Mobile App';
    } catch {
      return 'Mobile App';
    }
  };

  const handleLogin = async (data: LoginFormData) => {
    logger.debug('[LoginScreen] handleLogin() started');
    setIsLoading(true);
    setError(null);

    try {
      const deviceName = await getDeviceName();
      logger.debug('[LoginScreen] Calling authService.login...');

      const response = await authService.login({
        email: data.email.toLowerCase().trim(),
        password: data.password,
        device_name: deviceName,
      });

      logger.debug('[LoginScreen] Login response received');
      const { user, access_token, refresh_token, expires_in } = response.data;

      await saveSession(user, access_token, refresh_token, expires_in);

      // Redirect blocked users (pending/rejected) to approval screen
      if (isUserBlocked(user.status)) {
        router.replace('/(auth)/pending-approval' as Href);
        return;
      }

      // Push notifications only set up for approved users to avoid
      // sending notifications to users who can't access the app
      setupPushNotifications();

      // Navigate based on user role
      const routeByRole: Record<UserRole, Href> = {
        [UserRole.Doctor]: '/(auth)/dashboard' as Href,
        [UserRole.Receptor]: '/(auth)/receptor' as Href,
      };
      router.replace(routeByRole[user.role] || ('/(auth)/dashboard' as Href));
    } catch (err) {
      logger.error('[LoginScreen] handleLogin() error caught:', err);
      if (err instanceof AuthServiceError) {
        if (err.isNetworkError) {
          setError('Sem conexão com a internet. Verifique sua conexão.');
        } else if (err.isRateLimited) {
          setError('Muitas tentativas. Aguarde alguns minutos.');
        } else if (err.isServerError) {
          setError('Erro no servidor. Tente novamente mais tarde.');
        } else if (err.isValidationError && err.validationErrors) {
          // Mapeia erros do backend para os campos
          if (err.validationErrors.email?.[0]) {
            setFormError('email', { message: err.validationErrors.email[0] });
          }
          if (err.validationErrors.password?.[0]) {
            setFormError('password', { message: err.validationErrors.password[0] });
          }
          // Se não tem erro específico de campo, mostra mensagem geral
          if (!err.validationErrors.email && !err.validationErrors.password) {
            setError(err.message);
          }
        } else {
          setError(err.message);
        }
      } else {
        setError('Erro ao fazer login. Tente novamente.');
      }
    } finally {
      logger.debug('[LoginScreen] handleLogin() finished, setting isLoading=false');
      setIsLoading(false);
    }
  };

  const handleGoToRegister = () => {
    router.push('/register' as Href);
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
          <View style={styles.header}>
            <Title>Bem-vindo de volta!</Title>
            <Caption>Faça login para continuar</Caption>
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
                returnKeyType: 'next',
                onSubmitEditing: () => passwordRef.current?.focus(),
                editable: !isLoading,
              }}
              error={errors.email?.message}
            />

            <View style={styles.passwordContainer}>
              <Input
                ref={passwordRef}
                formProps={{
                  name: 'password',
                  control: control,
                }}
                inputProps={{
                  placeholder: 'Senha',
                  secureTextEntry: !showPassword,
                  autoComplete: 'password',
                  autoCapitalize: 'none',
                  returnKeyType: 'done',
                  onSubmitEditing: () => handleSubmit(handleLogin)(),
                  editable: !isLoading,
                }}
                error={errors.password?.message}
              />
              <TouchableOpacity
                style={styles.eyeButton}
                onPress={() => setShowPassword(!showPassword)}
                hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
              >
                <Ionicons
                  name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                  size={24}
                  color={colors.primaryPressed}
                />
              </TouchableOpacity>
            </View>

            <TouchableOpacity
              onPress={() => router.push('/forgot-password' as Href)}
              style={styles.forgotPasswordContainer}
            >
              <Text style={styles.forgotPasswordLink}>Esqueci minha senha</Text>
            </TouchableOpacity>

            <View style={styles.buttonContainer}>
              {isLoading ? (
                <View style={styles.loadingButton}>
                  <ActivityIndicator color={colors.textInverted} />
                  <Text style={styles.loadingText}>Entrando...</Text>
                </View>
              ) : (
                <PrimaryButton label="Entrar" onPress={() => handleSubmit(handleLogin)()} />
              )}
            </View>

            <View style={styles.registerContainer}>
              <Text style={styles.registerText}>Não tem uma conta?</Text>
              <TouchableOpacity onPress={handleGoToRegister}>
                <Text style={styles.registerLink}>Cadastre-se</Text>
              </TouchableOpacity>
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
  passwordContainer: {
    position: 'relative',
  },
  eyeButton: {
    position: 'absolute',
    right: 16,
    top: 12,
    zIndex: 1,
  },
  forgotPasswordContainer: {
    alignItems: 'flex-end',
    marginTop: 4,
  },
  forgotPasswordLink: {
    color: colors.primaryPressed,
    fontSize: 14,
    fontWeight: '600',
    textDecorationLine: 'underline',
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
  registerContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 24,
    gap: 4,
  },
  registerText: {
    color: colors.primaryPressed,
    fontSize: 14,
  },
  registerLink: {
    color: colors.primaryPressed,
    fontSize: 14,
    fontWeight: '600',
    textDecorationLine: 'underline',
  },
});
