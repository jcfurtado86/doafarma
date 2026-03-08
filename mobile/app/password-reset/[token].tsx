import React, { useState, useRef } from 'react';
import {
  View,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
  ImageBackground,
  Alert,
} from 'react-native';
import { useRouter, useLocalSearchParams, Href } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import ArrowBackButton from '@/components/ArrowBackButton';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import { authService, AuthServiceError } from '@/services/authService';
import { resetPasswordSchema, ResetPasswordFormData } from '@/utils/validation/authValidation';
import { parseResetLink } from '@/utils/parseResetLink';
import { Colors } from '@/constants/Colors';
import { Ionicons } from '@expo/vector-icons';

export default function ResetPasswordScreen() {
  const router = useRouter();
  const { token: routeToken, email: routeEmail } = useLocalSearchParams<{
    token: string;
    email?: string;
  }>();

  const isManual = routeToken === 'manual';

  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [linkPasted, setLinkPasted] = useState(false);

  const passwordRef = useRef<TextInput>(null);
  const confirmPasswordRef = useRef<TextInput>(null);

  const {
    control,
    handleSubmit,
    formState: { errors },
    setError: setFormError,
    setValue,
  } = useFeatureForm<ResetPasswordFormData>({
    schema: resetPasswordSchema,
    defaultValues: {
      token: isManual ? '' : (routeToken ?? ''),
      email: routeEmail ?? '',
      password: '',
      password_confirmation: '',
    },
  });

  const handlePasteLink = async () => {
    try {
      const clipboardContent = await Clipboard.getStringAsync();

      if (!clipboardContent) {
        setError('Nenhum link copiado');
        return;
      }

      const parsed = parseResetLink(clipboardContent);

      if (!parsed) {
        setError('Link invalido, copie o link completo do email');
        return;
      }

      setValue('token', parsed.token);
      setValue('email', parsed.email);
      setLinkPasted(true);
      setError(null);
    } catch {
      setError('Erro ao ler a area de transferencia');
    }
  };

  const handleResetPassword = async (data: ResetPasswordFormData) => {
    setIsLoading(true);
    setError(null);

    try {
      await authService.resetPassword({
        token: data.token,
        email: data.email,
        password: data.password,
        password_confirmation: data.password_confirmation,
      });

      Alert.alert('Sucesso', 'Senha redefinida com sucesso!', [
        { text: 'OK', onPress: () => router.replace('/login' as Href) },
      ]);
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
          }
          if (err.validationErrors.password?.[0]) {
            setFormError('password', { message: err.validationErrors.password[0] });
          }
          if (err.validationErrors.token?.[0]) {
            setError('Token invalido ou expirado. Solicite um novo link.');
          }
          if (
            !err.validationErrors.email &&
            !err.validationErrors.password &&
            !err.validationErrors.token
          ) {
            setError(err.message);
          }
        } else {
          setError(err.message);
        }
      } else {
        setError('Erro ao redefinir senha. Tente novamente.');
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
            <Title>Redefinir senha</Title>
            <Caption>Digite sua nova senha</Caption>
          </View>

          {error && (
            <View style={styles.errorContainer}>
              <Ionicons name="alert-circle-outline" size={20} color="#DC2626" />
              <Text style={styles.errorText}>{error}</Text>
            </View>
          )}

          <View style={styles.form}>
            {isManual && !linkPasted && (
              <View style={styles.pasteContainer}>
                <TouchableOpacity style={styles.pasteButton} onPress={handlePasteLink}>
                  <Ionicons name="clipboard-outline" size={20} color={Colors.yellow_green_600} />
                  <Text style={styles.pasteButtonText}>Colar link</Text>
                </TouchableOpacity>
                <Text style={styles.pasteHint}>
                  Copie o link recebido no e-mail e toque no botao acima
                </Text>
              </View>
            )}

            {isManual && linkPasted && (
              <View style={styles.successBadge}>
                <Ionicons name="checkmark-circle-outline" size={20} color={Colors.green_600} />
                <Text style={styles.successBadgeText}>Link lido com sucesso</Text>
              </View>
            )}

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
                  placeholder: 'Nova senha',
                  secureTextEntry: !showPassword,
                  autoCapitalize: 'none',
                  returnKeyType: 'next',
                  onSubmitEditing: () => confirmPasswordRef.current?.focus(),
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
                  color={Colors.yellow_green_600}
                />
              </TouchableOpacity>
            </View>

            <View style={styles.passwordContainer}>
              <Input
                ref={confirmPasswordRef}
                formProps={{
                  name: 'password_confirmation',
                  control: control,
                }}
                inputProps={{
                  placeholder: 'Confirmar nova senha',
                  secureTextEntry: !showConfirmPassword,
                  autoCapitalize: 'none',
                  returnKeyType: 'done',
                  onSubmitEditing: () => handleSubmit(handleResetPassword)(),
                  editable: !isLoading,
                }}
                error={errors.password_confirmation?.message}
              />
              <TouchableOpacity
                style={styles.eyeButton}
                onPress={() => setShowConfirmPassword(!showConfirmPassword)}
                hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
              >
                <Ionicons
                  name={showConfirmPassword ? 'eye-off-outline' : 'eye-outline'}
                  size={24}
                  color={Colors.yellow_green_600}
                />
              </TouchableOpacity>
            </View>

            <View style={styles.buttonContainer}>
              {isLoading ? (
                <View style={styles.loadingButton}>
                  <ActivityIndicator color="#FFFFFF" />
                  <Text style={styles.loadingText}>Redefinindo...</Text>
                </View>
              ) : (
                <PrimaryButton
                  label="Redefinir senha"
                  onPress={() => handleSubmit(handleResetPassword)()}
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
    backgroundColor: '#FEE2E2',
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
    gap: 8,
  },
  errorText: {
    color: '#DC2626',
    fontSize: 14,
    flex: 1,
  },
  form: {
    width: '100%',
  },
  pasteContainer: {
    alignItems: 'center',
    marginBottom: 24,
  },
  pasteButton: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: Colors.yellow_green_600,
    borderStyle: 'dashed',
    borderRadius: 8,
    paddingVertical: 16,
    paddingHorizontal: 24,
    gap: 8,
  },
  pasteButtonText: {
    color: Colors.yellow_green_600,
    fontSize: 16,
    fontWeight: '600',
  },
  pasteHint: {
    color: Colors.gray_500,
    fontSize: 12,
    marginTop: 8,
    textAlign: 'center',
  },
  successBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.green_50,
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
    gap: 8,
  },
  successBadgeText: {
    color: Colors.green_600,
    fontSize: 14,
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
  buttonContainer: {
    marginTop: 24,
  },
  loadingButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: Colors.yellow_green_400,
    paddingVertical: 16,
    borderRadius: 8,
    gap: 8,
  },
  loadingText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
});
