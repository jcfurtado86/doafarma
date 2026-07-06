import React from 'react';
import { Text } from 'react-native';
import { Redirect, Stack, type Href } from 'expo-router';
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

  it('supports real expo-router components (Stack layout and Redirect)', async () => {
    renderRouter(
      {
        _layout: () => <Stack />,
        // Cast needed: typedRoutes only knows the app's real routes, not this mocked tree
        index: () => <Redirect href={'/about' as Href} />,
        about: () => <Text testID="screen-about">about</Text>,
      },
      { initialUrl: '/' }
    );

    expect(await screen.findByTestId('screen-about')).toBeTruthy();
  });
});
