import { render, screen } from '@testing-library/react-native';
import { StyleSheet, StyleProp, TextStyle, ViewStyle } from 'react-native';
import { colors, spacing, typography } from '@/theme/tokens';
import { Badge } from './index';

const flat = (s: StyleProp<ViewStyle | TextStyle>): ViewStyle & TextStyle =>
  (StyleSheet.flatten(s) ?? {}) as ViewStyle & TextStyle;

describe('Badge', () => {
  describe('variants', () => {
    it('defaults to neutral variant with surfaceSecondary bg and textSecondary color', () => {
      render(<Badge label="Neutral" />);
      const container = screen.getByLabelText('Status: Neutral');
      const text = screen.getByText('Neutral');

      expect(flat(container.props.style).backgroundColor).toBe(colors.surfaceSecondary);
      expect(flat(text.props.style).color).toBe(colors.textSecondary);
    });

    it('renders info variant with infoLight bg and info text color', () => {
      render(<Badge label="Info" variant="info" />);
      const container = screen.getByLabelText('Status: Info');
      const text = screen.getByText('Info');

      expect(flat(container.props.style).backgroundColor).toBe(colors.infoLight);
      expect(flat(text.props.style).color).toBe(colors.info);
    });

    it('renders success variant with successSurface bg and success text color', () => {
      render(<Badge label="Success" variant="success" />);
      const container = screen.getByLabelText('Status: Success');
      const text = screen.getByText('Success');

      expect(flat(container.props.style).backgroundColor).toBe(colors.successSurface);
      expect(flat(text.props.style).color).toBe(colors.success);
    });

    it('renders warning variant with warningSurface bg and warningText text color', () => {
      render(<Badge label="Warning" variant="warning" />);
      const container = screen.getByLabelText('Status: Warning');
      const text = screen.getByText('Warning');

      expect(flat(container.props.style).backgroundColor).toBe(colors.warningSurface);
      expect(flat(text.props.style).color).toBe(colors.warningText);
    });

    it('renders error variant with errorSurface bg and error text color', () => {
      render(<Badge label="Error" variant="error" />);
      const container = screen.getByLabelText('Status: Error');
      const text = screen.getByText('Error');

      expect(flat(container.props.style).backgroundColor).toBe(colors.errorSurface);
      expect(flat(text.props.style).color).toBe(colors.error);
    });
  });

  describe('sizes', () => {
    it('renders md size (default) with label typography and spacing.md horizontal padding', () => {
      render(<Badge label="Default" />);
      const container = screen.getByLabelText('Status: Default');
      const text = screen.getByText('Default');

      expect(flat(container.props.style).paddingHorizontal).toBe(spacing.md);
      expect(flat(container.props.style).paddingVertical).toBe(spacing.xs);
      expect(flat(text.props.style).fontSize).toBe(typography.label.fontSize);
    });

    it('renders sm size with caption typography and spacing.sm horizontal padding', () => {
      render(<Badge label="Small" size="sm" />);
      const container = screen.getByLabelText('Status: Small');
      const text = screen.getByText('Small');

      expect(flat(container.props.style).paddingHorizontal).toBe(spacing.sm);
      expect(flat(container.props.style).paddingVertical).toBe(2);
      expect(flat(text.props.style).fontSize).toBe(typography.caption.fontSize);
    });
  });

  describe('label truncation', () => {
    it('sets numberOfLines=1 and ellipsizeMode="tail" on the text node', () => {
      render(<Badge label="A very long label that should be truncated" />);
      const text = screen.getByText('A very long label that should be truncated');

      expect(text.props.numberOfLines).toBe(1);
      expect(text.props.ellipsizeMode).toBe('tail');
    });
  });

  describe('accessibility', () => {
    it('exposes a Status: <label> accessibility label on the container', () => {
      render(<Badge label="Pendente" variant="warning" />);
      expect(screen.getByLabelText('Status: Pendente')).toBeTruthy();
    });
  });
});
