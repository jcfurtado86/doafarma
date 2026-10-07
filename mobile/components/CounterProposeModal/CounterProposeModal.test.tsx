import React from 'react';
import { ReactTestInstance } from 'react-test-renderer';

import { fireEvent, render, waitFor } from '@testing-library/react-native';

import { Address } from '@/types/address';

import { CounterProposeModal } from '.';

function makeAddress(id: number, label: string, isDefault = false): Address {
  return {
    id,
    label,
    cep: '68900000',
    uf: 'AP',
    city: 'Macapá',
    neighborhood: 'Centro',
    street: `Rua ${label}`,
    number: String(id),
    complement: null,
    formatted_address: `Rua ${label}, ${id} - Centro, Macapá - AP`,
    is_default: isDefault,
  };
}

const CLINICA = makeAddress(1, 'Clínica');
const CONSULTORIO = makeAddress(2, 'Consultório', true);
const POSTO = makeAddress(3, 'Posto');
const FORA_DA_LISTA = makeAddress(99, 'Fora da lista');

// Data distante para o envio não esbarrar na validação de data no passado.
const FUTURE_DATE = '2099-01-15';

type Props = React.ComponentProps<typeof CounterProposeModal>;

function buildProps(overrides: Partial<Props> = {}): Props {
  return {
    visible: true,
    onClose: jest.fn(),
    onSubmit: jest.fn().mockResolvedValue(undefined),
    currentDate: '2026-11-10',
    currentTime: '14:30:00',
    currentAddress: CLINICA,
    userRole: 'doctor',
    doctorAddresses: [CLINICA, CONSULTORIO, POSTO],
    ...overrides,
  };
}

// "Marcado" é o que o leitor de tela anuncia: aceita selected ou checked.
function isMarked(element: ReactTestInstance): boolean {
  const state = element.props.accessibilityState ?? {};
  return Boolean(
    state.selected ||
    state.checked === true ||
    element.props['aria-selected'] ||
    element.props['aria-checked'] === true
  );
}

function markedLabels(radios: ReactTestInstance[]): string[] {
  return radios.filter(isMarked).map((radio) => radio.props.accessibilityLabel);
}

async function submittedData(onSubmit: Props['onSubmit']) {
  const mock = onSubmit as jest.Mock;
  await waitFor(() => expect(mock).toHaveBeenCalledTimes(1));
  return mock.mock.calls[0][0];
}

describe('CounterProposeModal (médico)', () => {
  // Regra 11
  it('ao abrir, marca o endereço que é o local atual do agendamento', () => {
    const props = buildProps({ visible: false, currentAddress: CLINICA });
    const { rerender, getAllByRole } = render(<CounterProposeModal {...props} />);

    // O local do agendamento muda enquanto a janela está fechada.
    rerender(<CounterProposeModal {...props} currentAddress={POSTO} />);
    rerender(<CounterProposeModal {...props} currentAddress={POSTO} visible />);

    expect(markedLabels(getAllByRole('radio'))).toEqual(['Posto']);
  });

  // Regra 12
  it('marca o local atual quando a lista de endereços chega depois da janela aberta', () => {
    const props = buildProps({ currentAddress: CLINICA, doctorAddresses: undefined });
    const { rerender, getAllByRole } = render(<CounterProposeModal {...props} />);

    rerender(<CounterProposeModal {...props} doctorAddresses={[CLINICA, CONSULTORIO, POSTO]} />);

    expect(markedLabels(getAllByRole('radio'))).toEqual(['Clínica']);
  });

  // Regra 13
  it('envia sem pedir troca de local quando o médico não escolhe outro endereço', async () => {
    const props = buildProps({ currentDate: FUTURE_DATE, currentAddress: CLINICA });
    const { getByRole } = render(<CounterProposeModal {...props} />);

    fireEvent.press(getByRole('button', { name: 'Contrapropor' }));

    const sent = await submittedData(props.onSubmit);
    expect(sent.address_id).toBeUndefined();
  });

  // Regra 13
  it('envia sem pedir troca de local quando o local atual não aparece na lista', async () => {
    const props = buildProps({ currentDate: FUTURE_DATE, currentAddress: FORA_DA_LISTA });
    const { getByRole } = render(<CounterProposeModal {...props} />);

    fireEvent.press(getByRole('button', { name: 'Contrapropor' }));

    const sent = await submittedData(props.onSubmit);
    expect(sent.address_id).toBeUndefined();
  });

  // Regra 14
  it('ao reabrir, mostra a data e a hora atuais do agendamento', () => {
    const first = buildProps({ currentDate: '2026-11-10', currentTime: '14:30:00' });
    const { rerender, getByLabelText } = render(<CounterProposeModal {...first} />);

    expect(getByLabelText('Data')).toHaveDisplayValue('10/11/2026');
    expect(getByLabelText('Horário')).toHaveDisplayValue('14:30');

    rerender(<CounterProposeModal {...first} visible={false} />);

    const changed = { ...first, currentDate: '2026-12-01', currentTime: '09:15:00' };
    rerender(<CounterProposeModal {...changed} visible={false} />);
    rerender(<CounterProposeModal {...changed} visible />);

    expect(getByLabelText('Data')).toHaveDisplayValue('01/12/2026');
    expect(getByLabelText('Horário')).toHaveDisplayValue('09:15');
  });

  // Regra 15
  it('mantém a escolha do médico quando a lista de endereços é atualizada com a janela aberta', () => {
    const props = buildProps({ currentAddress: CLINICA });
    const { rerender, getAllByRole, getByRole } = render(<CounterProposeModal {...props} />);

    fireEvent.press(getByRole('radio', { name: 'Posto' }));
    expect(markedLabels(getAllByRole('radio'))).toEqual(['Posto']);

    const novoEndereco = makeAddress(4, 'Hospital');
    const refreshed = [{ ...CLINICA }, { ...CONSULTORIO }, { ...POSTO }, novoEndereco];
    rerender(<CounterProposeModal {...props} doctorAddresses={refreshed} />);

    expect(markedLabels(getAllByRole('radio'))).toEqual(['Posto']);
  });

  // Regra 16
  it('não mostra a seção de local quando o médico não tem endereços carregados', () => {
    const props = buildProps({ doctorAddresses: [] });
    const { rerender, queryAllByRole, queryByText } = render(<CounterProposeModal {...props} />);

    expect(queryByText(/Local de Retirada/)).toBeNull();
    expect(queryAllByRole('radio')).toHaveLength(0);

    rerender(<CounterProposeModal {...props} doctorAddresses={undefined} />);

    expect(queryByText(/Local de Retirada/)).toBeNull();
    expect(queryAllByRole('radio')).toHaveLength(0);
  });

  // Regra 17
  it('pede a troca para o endereço que o médico escolheu', async () => {
    const props = buildProps({ currentDate: FUTURE_DATE, currentAddress: CLINICA });
    const { getByRole } = render(<CounterProposeModal {...props} />);

    fireEvent.press(getByRole('radio', { name: 'Posto' }));
    fireEvent.press(getByRole('button', { name: 'Contrapropor' }));

    const sent = await submittedData(props.onSubmit);
    expect(sent.address_id).toBe(POSTO.id);
  });

  // Regra 18
  it('ao fechar sem enviar e abrir de novo, volta a marcar o local atual', () => {
    const props = buildProps({ currentAddress: CLINICA });
    const { rerender, getAllByRole, getByRole } = render(<CounterProposeModal {...props} />);

    fireEvent.press(getByRole('radio', { name: 'Posto' }));
    expect(markedLabels(getAllByRole('radio'))).toEqual(['Posto']);

    rerender(<CounterProposeModal {...props} visible={false} />);
    rerender(<CounterProposeModal {...props} visible />);

    expect(markedLabels(getAllByRole('radio'))).toEqual(['Clínica']);
  });
});

describe('CounterProposeModal (receptor)', () => {
  it('não mostra escolha de local e nunca envia endereço', async () => {
    const props = buildProps({ userRole: 'receptor', currentDate: FUTURE_DATE });
    const { getByRole, queryAllByRole } = render(<CounterProposeModal {...props} />);

    expect(queryAllByRole('radio')).toHaveLength(0);

    fireEvent.press(getByRole('button', { name: 'Contrapropor' }));

    const sent = await submittedData(props.onSubmit);
    expect(sent).toEqual({ scheduled_date: FUTURE_DATE, scheduled_time: '14:30' });
  });
});
