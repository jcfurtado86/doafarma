import { fireEvent, render, screen } from '@testing-library/react-native';
import { StyleSheet, StyleProp, TextStyle, ViewStyle } from 'react-native';
import { colors, spacing, typography } from '@/theme/tokens';
import { EmptyState } from './index';

jest.mock('@expo/vector-icons', () => {
  const React = jest.requireActual<typeof import('react')>('react');
  const { Text } = jest.requireActual<typeof import('react-native')>('react-native');
  return {
    Ionicons: (props: { name: string; size?: number; color?: string; testID?: string }) =>
      React.createElement(Text, props, props.name),
  };
});

const TEST_ID = 'empty-state';

const flat = (s: StyleProp<ViewStyle | TextStyle>): ViewStyle & TextStyle =>
  (StyleSheet.flatten(s) ?? {}) as ViewStyle & TextStyle;

describe('EmptyState', () => {
  describe('informative variant (no action)', () => {
    it('renders icon, title and description', () => {
      render(
        <EmptyState
          icon="file-tray-outline"
          title="Nenhuma oferta cadastrada"
          description="Suas ofertas de medicamentos aparecerão aqui."
          testID={TEST_ID}
        />
      );

      expect(screen.getByTestId(`${TEST_ID}-icon`)).toBeTruthy();
      expect(screen.getByText('Nenhuma oferta cadastrada')).toBeTruthy();
      expect(screen.getByText('Suas ofertas de medicamentos aparecerão aqui.')).toBeTruthy();
    });

    it('does not render an action button when onAction is absent', () => {
      render(
        <EmptyState
          icon="file-tray-outline"
          title="Nenhum agendamento"
          description="Seus agendamentos aparecerão aqui."
        />
      );

      expect(screen.queryByRole('button')).toBeNull();
    });

    it('centers content and styles title and description with tokens', () => {
      render(
        <EmptyState
          icon="file-tray-outline"
          title="Título"
          description="Descrição"
          testID={TEST_ID}
        />
      );

      const container = screen.getByTestId(TEST_ID);
      const title = screen.getByText('Título');
      const description = screen.getByText('Descrição');

      expect(flat(container.props.style).alignItems).toBe('center');
      expect(flat(container.props.style).justifyContent).toBe('center');
      expect(flat(title.props.style).fontSize).toBe(typography.heading4.fontSize);
      expect(flat(title.props.style).color).toBe(colors.textPrimary);
      expect(flat(title.props.style).textAlign).toBe('center');
      expect(flat(description.props.style).fontSize).toBe(typography.body.fontSize);
      expect(flat(description.props.style).color).toBe(colors.textSecondary);
      expect(flat(description.props.style).textAlign).toBe('center');
    });

    it('renders the icon with muted color and 2xl size', () => {
      render(
        <EmptyState
          icon="file-tray-outline"
          title="Título"
          description="Descrição"
          testID={TEST_ID}
        />
      );

      const icon = screen.getByTestId(`${TEST_ID}-icon`);

      expect(icon.props.color).toBe(colors.textMuted);
      expect(icon.props.size).toBe(spacing['2xl']);
    });
  });

  describe('CTA variant', () => {
    it('renders an action button when actionLabel and onAction are provided', () => {
      render(
        <EmptyState
          icon="medkit-outline"
          title="Nenhuma oferta cadastrada"
          description="Comece cadastrando seu primeiro medicamento."
          actionLabel="Cadastrar medicamento"
          onAction={jest.fn()}
        />
      );

      expect(screen.getByLabelText('Cadastrar medicamento')).toBeTruthy();
    });

    it('calls onAction when the action button is pressed', () => {
      const onAction = jest.fn();
      render(
        <EmptyState
          icon="medkit-outline"
          title="Nenhuma oferta cadastrada"
          description="Comece cadastrando seu primeiro medicamento."
          actionLabel="Cadastrar medicamento"
          onAction={onAction}
        />
      );

      fireEvent.press(screen.getByLabelText('Cadastrar medicamento'));

      expect(onAction).toHaveBeenCalledTimes(1);
    });

    it('does not render the button when actionLabel is provided without onAction', () => {
      render(
        <EmptyState
          icon="medkit-outline"
          title="Título"
          description="Descrição"
          actionLabel="Ação sem handler"
        />
      );

      expect(screen.queryByLabelText('Ação sem handler')).toBeNull();
    });
  });
});
