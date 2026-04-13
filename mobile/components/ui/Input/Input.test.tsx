import { createRef } from 'react';
import { TextInput } from 'react-native';
import { fireEvent, render, screen } from '@testing-library/react-native';
import { Input } from './index';
import { SIZE_HEIGHTS } from './styles';

describe('Input', () => {
  describe('default render', () => {
    it('renders a TextInput with the placeholder and forwards onChangeText', () => {
      const onChangeText = jest.fn();
      render(<Input placeholder="E-mail" value="" onChangeText={onChangeText} />);

      const input = screen.getByPlaceholderText('E-mail');
      fireEvent.changeText(input, 'ana@example.com');
      expect(onChangeText).toHaveBeenCalledWith('ana@example.com');
    });
  });

  describe('label', () => {
    it('renders the label above the input when provided', () => {
      render(<Input label="Email" value="" onChangeText={jest.fn()} />);
      expect(screen.getByText('Email')).toBeTruthy();
    });

    it('renders the input without any label text when label is not provided', () => {
      render(<Input placeholder="E-mail" value="" onChangeText={jest.fn()} />);
      expect(screen.getByPlaceholderText('E-mail')).toBeTruthy();
      expect(screen.queryByText('E-mail')).toBeNull();
    });
  });

  describe('error & helperText', () => {
    it('renders the error text when error is set', () => {
      render(<Input value="" onChangeText={jest.fn()} error="Email invalido" />);
      expect(screen.getByText('Email invalido')).toBeTruthy();
    });

    it('renders helperText when error is absent', () => {
      render(
        <Input value="" onChangeText={jest.fn()} helperText="Usaremos para recuperar senha" />
      );
      expect(screen.getByText('Usaremos para recuperar senha')).toBeTruthy();
    });

    it('error preempts helperText when both are provided', () => {
      render(
        <Input value="" onChangeText={jest.fn()} error="Campo obrigatorio" helperText="Ajuda" />
      );
      expect(screen.getByText('Campo obrigatorio')).toBeTruthy();
      expect(screen.queryByText('Ajuda')).toBeNull();
    });
  });

  describe('disabled', () => {
    it('passes editable=false and disabled a11y state to the inner TextInput', () => {
      render(<Input placeholder="Desabilitado" value="x" onChangeText={jest.fn()} disabled />);
      const input = screen.getByPlaceholderText('Desabilitado');
      expect(input.props.editable).toBe(false);
      expect(input.props.accessibilityState).toEqual({ disabled: true });
    });
  });

  describe('size', () => {
    it.each([
      ['sm', SIZE_HEIGHTS.sm],
      ['md', SIZE_HEIGHTS.md],
      ['lg', SIZE_HEIGHTS.lg],
    ] as const)('applies the %s height (%i)', (size, expectedHeight) => {
      render(<Input placeholder={`size-${size}`} value="" onChangeText={jest.fn()} size={size} />);
      const input = screen.getByPlaceholderText(`size-${size}`);
      const flatStyle = Array.isArray(input.props.style)
        ? Object.assign({}, ...input.props.style.filter(Boolean))
        : input.props.style;
      expect(flatStyle.height).toBe(expectedHeight);
    });
  });

  describe('forwardRef', () => {
    it('exposes the inner TextInput through the ref', () => {
      const ref = createRef<TextInput>();
      render(<Input ref={ref} placeholder="ref" value="" onChangeText={jest.fn()} />);
      expect(ref.current).toBeTruthy();
    });
  });

  describe('onBlur', () => {
    it('fires the provided onBlur callback', () => {
      const onBlur = jest.fn();
      render(<Input placeholder="blur" value="" onChangeText={jest.fn()} onBlur={onBlur} />);
      fireEvent(screen.getByPlaceholderText('blur'), 'blur');
      expect(onBlur).toHaveBeenCalledTimes(1);
    });
  });
});
