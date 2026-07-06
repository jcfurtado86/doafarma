import React from 'react';
import { Text } from 'react-native';
import { renderRouter, screen } from 'expo-router/testing-library';

describe('renderRouter smoke', () => {
  it('renders a mocked route tree and resolves the initial URL', async () => {
    renderRouter(
      {
        index: () => <Text testID="screen-home">home</Text>,
        about: () => <Text testID="screen-about">about</Text>,
      },
      { initialUrl: '/about' }
    );

    expect(await screen.findByTestId('screen-about')).toBeTruthy();
    expect(screen.queryByTestId('screen-home')).toBeNull();
  });
});
