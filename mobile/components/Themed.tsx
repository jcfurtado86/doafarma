/**
 * Learn more about Light and Dark modes:
 * https://docs.expo.io/guides/color-schemes/
 */

import { Text as DefaultText, View as DefaultView } from 'react-native';

import { useColorScheme } from './useColorScheme';
import { colors } from '@/theme/tokens';

type ThemeProps = {
  lightColor?: string;
  darkColor?: string;
};

export type TextProps = ThemeProps & DefaultText['props'];
export type ViewProps = ThemeProps & DefaultView['props'];

const themeColors = {
  light: {
    text: colors.textPrimary,
    background: colors.background,
    tint: colors.primary,
    tabIconDefault: colors.textMuted,
    tabIconSelected: colors.primary,
  },
  dark: {
    text: colors.textInverted,
    background: colors.black,
    tint: colors.textInverted,
    tabIconDefault: colors.textMuted,
    tabIconSelected: colors.textInverted,
  },
} as const;

export function useThemeColor(
  props: { light?: string; dark?: string },
  colorName: keyof typeof themeColors.light & keyof typeof themeColors.dark
) {
  const theme = useColorScheme() ?? 'light';
  const colorFromProps = props[theme];

  if (colorFromProps) {
    return colorFromProps;
  } else {
    return themeColors[theme][colorName];
  }
}

export function Text(props: TextProps) {
  const { style, lightColor, darkColor, ...otherProps } = props;
  const color = useThemeColor({ light: lightColor, dark: darkColor }, 'text');

  return <DefaultText style={[{ color }, style]} {...otherProps} />;
}

export function View(props: ViewProps) {
  const { style, lightColor, darkColor, ...otherProps } = props;
  const backgroundColor = useThemeColor({ light: lightColor, dark: darkColor }, 'background');

  return <DefaultView style={[{ backgroundColor }, style]} {...otherProps} />;
}
