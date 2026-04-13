import { createRef } from 'react';
import { act, fireEvent, render, screen } from '@testing-library/react-native';
import { Platform } from 'react-native';
import { Select, type SelectHandle } from './select';
import { SelectItem } from './selectItem';
import { SIZE_HEIGHTS } from './styles';

jest.mock('@react-native-picker/picker', () => {
  // eslint-disable-next-line @typescript-eslint/no-require-imports
  const React = require('react');
  // eslint-disable-next-line @typescript-eslint/no-require-imports
  const RN = require('react-native');
  const Picker = React.forwardRef(
    (
      {
        children,
        enabled,
        onValueChange,
        selectedValue,
        onFocus,
        onBlur,
      }: {
        children?: React.ReactNode;
        enabled?: boolean;
        onValueChange?: (value: string | number, index: number) => void;
        selectedValue?: string | number;
        onFocus?: () => void;
        onBlur?: () => void;
      },
      ref: React.Ref<unknown>
    ) => {
      React.useImperativeHandle(ref, () => ({ focus: jest.fn(), blur: jest.fn() }));
      return React.createElement(
        RN.View,
        {
          accessibilityLabel: 'mocked-picker',
          accessibilityState: { disabled: enabled === false },
          onAccessibilityAction: (event: { nativeEvent: { actionName: string } }) => {
            if (event.nativeEvent.actionName === 'focus') onFocus?.();
            if (event.nativeEvent.actionName === 'blur') onBlur?.();
          },
          // expose the picker's key props so tests can assert on them
          testID: `mocked-picker-enabled-${enabled !== false}-selected-${selectedValue ?? ''}`,
          onPress: () => onValueChange?.(selectedValue ?? '', 0),
        },
        children
      );
    }
  );
  Picker.displayName = 'Picker';
  const Item = ({ label }: { label: string }) =>
    React.createElement(RN.Text, null, `item:${label}`);
  (Picker as unknown as { Item: typeof Item }).Item = Item;
  return { Picker };
});

const renderSelect = (overrides: Partial<React.ComponentProps<typeof Select>> = {}) =>
  render(
    <Select
      value=""
      onValueChange={jest.fn()}
      placeholder="Selecione o estado"
      accessibilityLabel="uf-select"
      {...overrides}
    >
      <SelectItem label="Santa Catarina" value="SC" />
      <SelectItem label="Paraná" value="PR" />
      <SelectItem label="Rio Grande do Sul" value="RS" />
    </Select>
  );

describe('Select (iOS path)', () => {
  beforeAll(() => {
    Object.defineProperty(Platform, 'OS', { get: () => 'ios', configurable: true });
  });

  describe('default render', () => {
    it('shows the placeholder text when value is empty', () => {
      renderSelect();
      expect(screen.getByText('Selecione o estado')).toBeTruthy();
    });

    it('shows the matching option label when value is set', () => {
      renderSelect({ value: 'PR' });
      expect(screen.getByText('Paraná')).toBeTruthy();
      expect(screen.queryByText('Selecione o estado')).toBeNull();
    });
  });

  describe('label', () => {
    it('renders the label above the trigger when provided', () => {
      renderSelect({ label: 'UF' });
      expect(screen.getByText('UF')).toBeTruthy();
    });

    it('renders no label text when absent', () => {
      renderSelect();
      expect(screen.queryByText('UF')).toBeNull();
    });
  });

  describe('error & helperText', () => {
    it('renders the error text when error is set', () => {
      renderSelect({ error: 'UF obrigatória' });
      expect(screen.getByText('UF obrigatória')).toBeTruthy();
    });

    it('renders helperText when error is absent', () => {
      renderSelect({ helperText: 'Escolha o estado de emissão do CRM' });
      expect(screen.getByText('Escolha o estado de emissão do CRM')).toBeTruthy();
    });

    it('error preempts helperText when both are provided', () => {
      renderSelect({ error: 'UF obrigatória', helperText: 'Ajuda' });
      expect(screen.getByText('UF obrigatória')).toBeTruthy();
      expect(screen.queryByText('Ajuda')).toBeNull();
    });
  });

  describe('disabled', () => {
    it('sets the trigger accessibilityState.disabled and does not open the modal on press', () => {
      const onValueChange = jest.fn();
      renderSelect({ disabled: true, onValueChange });
      const trigger = screen.getByLabelText('uf-select');
      expect(trigger.props.accessibilityState).toEqual({ disabled: true });
      fireEvent.press(trigger);
      expect(screen.queryByLabelText('Selecionar opção: Paraná')).toBeNull();
    });
  });

  describe('size', () => {
    it.each([
      ['sm', SIZE_HEIGHTS.sm],
      ['md', SIZE_HEIGHTS.md],
      ['lg', SIZE_HEIGHTS.lg],
    ] as const)('applies the %s height (%i)', (size, expectedHeight) => {
      renderSelect({ size });
      const trigger = screen.getByLabelText('uf-select');
      const flat = Array.isArray(trigger.props.style)
        ? Object.assign({}, ...trigger.props.style.filter(Boolean))
        : trigger.props.style;
      expect(flat.height).toBe(expectedHeight);
    });
  });

  describe('forwardRef', () => {
    it('focus() opens the modal on iOS', () => {
      const ref = createRef<SelectHandle>();
      renderSelect({ ref });
      expect(screen.queryByLabelText('Selecionar opção: Paraná')).toBeNull();
      act(() => {
        ref.current?.focus();
      });
      expect(screen.getByLabelText('Selecionar opção: Paraná')).toBeTruthy();
    });

    it('blur() closes the modal on iOS', () => {
      const ref = createRef<SelectHandle>();
      renderSelect({ ref });
      act(() => {
        ref.current?.focus();
      });
      expect(screen.getByLabelText('Selecionar opção: Paraná')).toBeTruthy();
      act(() => {
        ref.current?.blur();
      });
      expect(screen.queryByLabelText('Selecionar opção: Paraná')).toBeNull();
    });
  });

  describe('selection', () => {
    it('calls onValueChange with the value, closes the modal, and fires onBlur', () => {
      const onValueChange = jest.fn();
      const onBlur = jest.fn();
      renderSelect({ onValueChange, onBlur });

      fireEvent.press(screen.getByLabelText('uf-select'));
      fireEvent.press(screen.getByLabelText('Selecionar opção: Paraná'));

      expect(onValueChange).toHaveBeenCalledWith('PR');
      expect(onBlur).toHaveBeenCalledTimes(1);
      expect(screen.queryByLabelText('Selecionar opção: Paraná')).toBeNull();
    });
  });

  describe('cancel button', () => {
    it('closes the modal and fires onBlur without calling onValueChange', () => {
      const onValueChange = jest.fn();
      const onBlur = jest.fn();
      renderSelect({ onValueChange, onBlur });

      fireEvent.press(screen.getByLabelText('uf-select'));
      fireEvent.press(screen.getByLabelText('Cancelar seleção'));

      expect(onValueChange).not.toHaveBeenCalled();
      expect(onBlur).toHaveBeenCalledTimes(1);
      expect(screen.queryByLabelText('Cancelar seleção')).toBeNull();
    });
  });

  describe('backdrop dismissal', () => {
    it('closes the modal and fires onBlur without calling onValueChange', () => {
      const onValueChange = jest.fn();
      const onBlur = jest.fn();
      renderSelect({ onValueChange, onBlur });

      fireEvent.press(screen.getByLabelText('uf-select'));
      fireEvent.press(screen.getByLabelText('Fechar seleção'));

      expect(onValueChange).not.toHaveBeenCalled();
      expect(onBlur).toHaveBeenCalledTimes(1);
    });
  });
});

describe('Select (Android path)', () => {
  const originalDescriptor = Object.getOwnPropertyDescriptor(Platform, 'OS');

  beforeAll(() => {
    Object.defineProperty(Platform, 'OS', { get: () => 'android', configurable: true });
  });

  afterAll(() => {
    if (originalDescriptor) Object.defineProperty(Platform, 'OS', originalDescriptor);
    else Object.defineProperty(Platform, 'OS', { get: () => 'ios', configurable: true });
  });

  it('disables the Picker when disabled is true', () => {
    renderSelect({ disabled: true });
    const picker = screen.getByLabelText('mocked-picker');
    expect(picker.props.accessibilityState).toEqual({ disabled: true });
  });

  it('enables the Picker when disabled is false', () => {
    renderSelect({ disabled: false });
    const picker = screen.getByLabelText('mocked-picker');
    expect(picker.props.accessibilityState).toEqual({ disabled: false });
  });
});
