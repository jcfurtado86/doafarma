import { ReactNode } from 'react';
import { Pressable, StyleProp, View, ViewStyle } from 'react-native';

import { styles } from './styles';

export type CardVariant = 'default' | 'elevated' | 'outlined';
export type CardAccent = 'primary' | 'error' | 'info';

export interface CardProps {
  children: ReactNode;
  variant?: CardVariant;
  accent?: CardAccent;
  onPress?: () => void;
  style?: StyleProp<ViewStyle>;
  testID?: string;
  accessibilityLabel?: string;
}

const PRESSED_STYLE = { opacity: 0.8 } as const;

export function Card({
  children,
  variant = 'default',
  accent,
  onPress,
  style,
  testID,
  accessibilityLabel,
}: CardProps) {
  const composed: StyleProp<ViewStyle> = [
    styles.base,
    styles[variant],
    accent ? styles[`accent_${accent}` as const] : null,
  ];

  if (onPress) {
    return (
      <Pressable
        style={({ pressed }) => [composed, style, pressed && PRESSED_STYLE]}
        onPress={onPress}
        testID={testID}
        accessibilityRole="button"
        accessibilityLabel={accessibilityLabel}
      >
        {children}
      </Pressable>
    );
  }

  return (
    <View style={[composed, style]} testID={testID}>
      {children}
    </View>
  );
}
