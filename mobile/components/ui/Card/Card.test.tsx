import React from 'react';
import { StyleSheet, Text } from 'react-native';

import { fireEvent, render } from '@testing-library/react-native';

import { colors, elevation, radii, spacing } from '@/theme/tokens';

import { Card } from '.';

describe('Card', () => {
  it('renders children', () => {
    const { getByText } = render(
      <Card>
        <Text>Hello</Text>
      </Card>
    );

    expect(getByText('Hello')).toBeTruthy();
  });

  it('applies base shell tokens by default', () => {
    const { getByTestId } = render(
      <Card testID="card">
        <Text>x</Text>
      </Card>
    );

    const flat = StyleSheet.flatten(getByTestId('card').props.style) ?? {};
    expect(flat.backgroundColor).toBe(colors.surface);
    expect(flat.borderRadius).toBe(radii.lg);
    expect(flat.padding).toBe(spacing.md);
    expect(flat.shadowRadius).toBe(elevation.md.shadowRadius);
    expect(flat.elevation).toBe(elevation.md.elevation);
  });

  it('variant="elevated" uses elevation.lg', () => {
    const { getByTestId } = render(
      <Card testID="card" variant="elevated">
        <Text>x</Text>
      </Card>
    );

    const flat = StyleSheet.flatten(getByTestId('card').props.style) ?? {};
    expect(flat.shadowRadius).toBe(elevation.lg.shadowRadius);
    expect(flat.elevation).toBe(elevation.lg.elevation);
  });

  it('variant="outlined" applies border and no shadow', () => {
    const { getByTestId } = render(
      <Card testID="card" variant="outlined">
        <Text>x</Text>
      </Card>
    );

    const flat = StyleSheet.flatten(getByTestId('card').props.style) ?? {};
    expect(flat.borderWidth).toBe(1);
    expect(flat.borderColor).toBe(colors.border);
    expect(flat.shadowOpacity).toBeUndefined();
  });

  it('accent="primary" adds left border with primary color', () => {
    const { getByTestId } = render(
      <Card testID="card" accent="primary">
        <Text>x</Text>
      </Card>
    );

    const flat = StyleSheet.flatten(getByTestId('card').props.style) ?? {};
    expect(flat.borderLeftWidth).toBe(4);
    expect(flat.borderLeftColor).toBe(colors.primary);
  });

  it('accent="error" uses error color on the left border', () => {
    const { getByTestId } = render(
      <Card testID="card" accent="error">
        <Text>x</Text>
      </Card>
    );

    const flat = StyleSheet.flatten(getByTestId('card').props.style) ?? {};
    expect(flat.borderLeftColor).toBe(colors.error);
  });

  it('accent="info" uses info color on the left border', () => {
    const { getByTestId } = render(
      <Card testID="card" accent="info">
        <Text>x</Text>
      </Card>
    );

    const flat = StyleSheet.flatten(getByTestId('card').props.style) ?? {};
    expect(flat.borderLeftColor).toBe(colors.info);
  });

  it('has no left border when accent is omitted', () => {
    const { getByTestId } = render(
      <Card testID="card">
        <Text>x</Text>
      </Card>
    );

    const flat = StyleSheet.flatten(getByTestId('card').props.style) ?? {};
    expect(flat.borderLeftWidth).toBeUndefined();
    expect(flat.borderLeftColor).toBeUndefined();
  });

  it('merges external style prop with base shell', () => {
    const { getByTestId } = render(
      <Card testID="card" style={{ marginBottom: 12 }}>
        <Text>x</Text>
      </Card>
    );

    const flat = StyleSheet.flatten(getByTestId('card').props.style) ?? {};
    expect(flat.marginBottom).toBe(12);
    expect(flat.backgroundColor).toBe(colors.surface);
  });

  it('renders as button and fires onPress when provided', () => {
    const onPress = jest.fn();
    const { getByRole } = render(
      <Card onPress={onPress} accessibilityLabel="open details">
        <Text>x</Text>
      </Card>
    );

    const pressable = getByRole('button');
    expect(pressable.props.accessibilityLabel).toBe('open details');
    fireEvent.press(pressable);
    expect(onPress).toHaveBeenCalledTimes(1);
  });

  it('does not render as button when onPress is omitted', () => {
    const { queryByRole } = render(
      <Card>
        <Text>x</Text>
      </Card>
    );

    expect(queryByRole('button')).toBeNull();
  });
});
