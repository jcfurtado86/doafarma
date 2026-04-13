import React, { useRef, useState } from 'react';
import {
  View,
  TextInput,
  Text,
  TouchableOpacity,
  ActivityIndicator,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useRouter, Href } from 'expo-router';
import { Input } from '@/components/ui/Input';
import PrimaryButton from '@/components/PrimaryButton';
import ArrowBackButton from '@/components/ArrowBackButton';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { useFeatureForm } from '@/hooks/useFeatureForm';
import { useReceptorRegistrationStore } from '@/stores/receptorRegistrationStore';
import {
  receptorRegistrationSchema,
  ReceptorRegistrationFormData,
} from '@/utils/validation/receptorValidation';
import { colors } from '@/theme/tokens';
import { styles } from './styles';
import { Ionicons } from '@expo/vector-icons';

export function ReceptorRegistrationScreen() {
  const router = useRouter();
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

  const {
    updateFormData,
    submitRegistration,
    isLoading,
    error,
    validationErrors,
    clearErrors,
    resetForm,
  } = useReceptorRegistrationStore();

  const {
    control,
    handleSubmit,
    formState: { errors },
    setError,
    watch,
    setValue,
  } = useFeatureForm<ReceptorRegistrationFormData>({
    schema: receptorRegistrationSchema,
    defaultValues: {
      name: '',
      email: '',
      cpf: '',
      phone_number: '',
      password: '',
      password_confirmation: '',
      terms_accepted: false,
    },
  });

  const termsAccepted = watch('terms_accepted');

  // Refs para navegação entre inputs
  const emailRef = useRef<TextInput>(null);
  const cpfRef = useRef<TextInput>(null);
  const phoneRef = useRef<TextInput>(null);
  const passwordRef = useRef<TextInput>(null);
  const passwordConfirmationRef = useRef<TextInput>(null);

  // Função para aplicar máscara de CPF
  const formatCPF = (value: string): string => {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 3) return numbers;
    if (numbers.length <= 6) return `${numbers.slice(0, 3)}.${numbers.slice(3)}`;
    if (numbers.length <= 9)
      return `${numbers.slice(0, 3)}.${numbers.slice(3, 6)}.${numbers.slice(6)}`;
    return `${numbers.slice(0, 3)}.${numbers.slice(3, 6)}.${numbers.slice(6, 9)}-${numbers.slice(9, 11)}`;
  };

  // Função para aplicar máscara de telefone
  const formatPhone = (value: string): string => {
    const numbers = value.replace(/\D/g, '');
    if (numbers.length <= 2) return numbers;
    if (numbers.length <= 7) return `(${numbers.slice(0, 2)}) ${numbers.slice(2)}`;
    return `(${numbers.slice(0, 2)}) ${numbers.slice(2, 7)}-${numbers.slice(7, 11)}`;
  };

  const handleGoBack = () => {
    resetForm();
    clearErrors();
    router.push('/register');
  };

  const handleTermsToggle = () => {
    setValue('terms_accepted', !termsAccepted);
  };

  const onSubmit = async (data: ReceptorRegistrationFormData) => {
    // Atualiza a store com os dados do formulário
    updateFormData({
      name: data.name,
      email: data.email,
      cpf: data.cpf,
      phone_number: data.phone_number,
      password: data.password,
      password_confirmation: data.password_confirmation,
      terms_accepted: data.terms_accepted,
    });

    const success = await submitRegistration();

    if (!success && validationErrors) {
      // Mapeia erros do backend para o formulário
      Object.keys(validationErrors).forEach((key) => {
        const fieldKey = key as keyof ReceptorRegistrationFormData;
        if (validationErrors[key]?.[0]) {
          setError(fieldKey, { message: validationErrors[key][0] });
        }
      });
    }

    if (success) {
      router.replace('/(auth)/receptor' as Href);
    }
  };

  if (isLoading) {
    return (
      <View style={styles.container}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
          <Text style={styles.loadingText}>Criando sua conta...</Text>
        </View>
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ArrowBackButton style={{ marginTop: 56 }} onPress={handleGoBack} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{ flexGrow: 1 }}
      >
        <View style={styles.textContainer}>
          <Title>Olá, paciente!</Title>
          <Caption>Preencha seus dados para criar sua conta</Caption>
        </View>

        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
          </View>
        )}

        <View style={styles.inputContainer}>
          <Input
            formProps={{
              name: 'name',
              control: control,
            }}
            inputProps={{
              returnKeyType: 'next',
              placeholder: 'Nome Completo',
              autoCapitalize: 'words',
              onSubmitEditing: () => emailRef.current?.focus(),
            }}
            error={errors.name?.message}
          />

          <Input
            ref={emailRef}
            formProps={{
              name: 'email',
              control: control,
            }}
            inputProps={{
              returnKeyType: 'next',
              placeholder: 'Email',
              keyboardType: 'email-address',
              autoCapitalize: 'none',
              autoComplete: 'email',
              onSubmitEditing: () => cpfRef.current?.focus(),
            }}
            error={errors.email?.message}
          />

          <Input
            ref={cpfRef}
            formProps={{
              name: 'cpf',
              control: control,
              rules: {
                onChange: (e: { target: { value: string } }) => {
                  const formatted = formatCPF(e.target.value);
                  setValue('cpf', formatted);
                },
              },
            }}
            inputProps={{
              returnKeyType: 'next',
              placeholder: 'CPF (000.000.000-00)',
              keyboardType: 'numeric',
              maxLength: 14,
              onSubmitEditing: () => phoneRef.current?.focus(),
            }}
            error={errors.cpf?.message}
          />

          <Input
            ref={phoneRef}
            formProps={{
              name: 'phone_number',
              control: control,
              rules: {
                onChange: (e: { target: { value: string } }) => {
                  const formatted = formatPhone(e.target.value);
                  setValue('phone_number', formatted);
                },
              },
            }}
            inputProps={{
              returnKeyType: 'next',
              placeholder: 'Telefone (00) 00000-0000',
              keyboardType: 'phone-pad',
              maxLength: 15,
              onSubmitEditing: () => passwordRef.current?.focus(),
            }}
            error={errors.phone_number?.message}
          />

          <View style={styles.passwordContainer}>
            <Input
              ref={passwordRef}
              formProps={{
                name: 'password',
                control: control,
              }}
              inputProps={{
                returnKeyType: 'next',
                placeholder: 'Senha (mínimo 8 caracteres)',
                secureTextEntry: !showPassword,
                autoCapitalize: 'none',
                onSubmitEditing: () => passwordConfirmationRef.current?.focus(),
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

          <View style={styles.passwordContainer}>
            <Input
              ref={passwordConfirmationRef}
              formProps={{
                name: 'password_confirmation',
                control: control,
              }}
              inputProps={{
                returnKeyType: 'done',
                placeholder: 'Confirmar Senha',
                secureTextEntry: !showPasswordConfirmation,
                autoCapitalize: 'none',
                onSubmitEditing: () => handleSubmit(onSubmit)(),
              }}
              error={errors.password_confirmation?.message}
            />
            <TouchableOpacity
              style={styles.eyeButton}
              onPress={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
              hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            >
              <Ionicons
                name={showPasswordConfirmation ? 'eye-off-outline' : 'eye-outline'}
                size={24}
                color={colors.primaryPressed}
              />
            </TouchableOpacity>
          </View>

          <TouchableOpacity
            style={styles.termsContainer}
            onPress={handleTermsToggle}
            activeOpacity={0.7}
          >
            <View style={[styles.checkbox, termsAccepted && styles.checkboxChecked]}>
              {termsAccepted && <Ionicons name="checkmark" size={16} color={colors.textInverted} />}
            </View>
            <Text style={styles.termsText}>
              Li e aceito os <Text style={styles.termsLink}>Termos de Uso</Text> e a{' '}
              <Text style={styles.termsLink}>Política de Privacidade</Text>
            </Text>
          </TouchableOpacity>
          {errors.terms_accepted?.message && (
            <Text style={styles.termsError}>{errors.terms_accepted.message}</Text>
          )}
        </View>

        <View style={styles.buttonContainer}>
          <PrimaryButton onPress={() => handleSubmit(onSubmit)()} label="Criar Conta" />
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
