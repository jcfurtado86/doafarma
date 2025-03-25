import React, { useRef } from 'react';
import { View, TextInput } from 'react-native';
import { Input } from '@/components/Input';
import PrimaryButton from '@/components/PrimaryButton';
import { styles } from './styles';
import { useForm } from 'react-hook-form';
import { Title } from '@/components/Title';
import { Caption } from '@/components/Caption';
import { InputRow } from '@/components/InputRow';
import { Select, SelectItem } from '@/components/Select';
import { Picker } from '@react-native-picker/picker';
import { DoctorRegistrationFormData } from '@/stores/doctorRegistrationFormStore';

interface DoctorPersonalDataStepProps {
  onSubmit: (data: DoctorRegistrationFormData) => void;
}

export function DoctorPersonalDataStep({ onSubmit }: DoctorPersonalDataStepProps) {
  const { control, handleSubmit } = useForm<DoctorRegistrationFormData>();

  function handleNextStep(data: DoctorRegistrationFormData) {
    onSubmit(data);
  }

  const crmRef = useRef<TextInput>(null);
  const crmUf = useRef<Picker<string | number>>(null);
  const dddRef = useRef<TextInput>(null);
  const phoneRef = useRef<TextInput>(null);
  const emailRef = useRef<TextInput>(null);
  const passwordRef = useRef<TextInput>(null);
  const passwordConfirmationRef = useRef<TextInput>(null);

  return (
    <>
      <View style={styles.textContainer}>
        <Title>Olá doutor!</Title>
        <Caption>Preencha seus dados pessoais</Caption>
      </View>
      <View style={styles.inputContainer}>
        <Input
          formProps={{
            name: 'name',
            control: control,
          }}
          inputProps={{
            onSubmitEditing: () => crmRef.current?.focus(),
            returnKeyType: 'next',
            placeholder: 'Nome Completo',
          }}
        />

        <InputRow>
          <Input
            ref={crmRef}
            formProps={{
              name: 'crm',
              control: control,
            }}
            inputProps={{
              onSubmitEditing: () => crmUf.current?.focus(),
              returnKeyType: 'next',
              placeholder: 'CRM',
            }}
            style={{ flex: 2 }}
          />
          <Select
            ref={crmUf}
            formProps={{
              name: 'crm_uf',
              control: control,
            }}
            selectProps={{
              placeholder: 'UF',
            }}
            styleView={{ flex: 1 }}
            nextRef={dddRef}
          >
            <SelectItem label="AC" value="AC" />
            <SelectItem label="AL" value="AL" />
            <SelectItem label="AP" value="AP" />
            <SelectItem label="AM" value="AM" />
            <SelectItem label="BA" value="BA" />
          </Select>
        </InputRow>
        <InputRow>
          <Input
            ref={dddRef}
            formProps={{
              name: 'ddd',
              control: control,
            }}
            inputProps={{
              placeholder: 'DDD',
              onSubmitEditing: () => phoneRef.current?.focus(),
              returnKeyType: 'next',
            }}
            style={{ flex: 1 }}
          />
          <Input
            ref={phoneRef}
            formProps={{
              name: 'phone_number',
              control: control,
            }}
            inputProps={{
              placeholder: 'Telefone',
              onSubmitEditing: () => emailRef.current?.focus(),
              returnKeyType: 'next',
            }}
            style={{ flex: 3 }}
          />
        </InputRow>

        <Input
          ref={emailRef}
          formProps={{
            name: 'email',
            control: control,
          }}
          inputProps={{
            placeholder: 'Email',
            onSubmitEditing: () => passwordRef.current?.focus(),
            returnKeyType: 'next',
          }}
        />
        <Input
          ref={passwordRef}
          formProps={{
            name: 'password',
            control: control,
          }}
          inputProps={{
            placeholder: 'Senha',
            secureTextEntry: true,
            onSubmitEditing: () => passwordConfirmationRef.current?.focus(),
            returnKeyType: 'next',
          }}
        />
        <Input
          ref={passwordConfirmationRef}
          formProps={{
            name: 'password_confirmation',
            control: control,
          }}
          inputProps={{
            placeholder: 'Confirmar Senha',
            secureTextEntry: true,
            onSubmitEditing: () => handleSubmit(handleNextStep)(),
            returnKeyType: 'done',
          }}
        />
      </View>
      <View style={styles.buttonContainer}>
        <PrimaryButton onPress={() => handleSubmit(handleNextStep)()} label="Próximo" />
      </View>
    </>
  );
}
